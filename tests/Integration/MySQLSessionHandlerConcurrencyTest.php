<?php

require_once __DIR__ . '/../../app/includes/MySQLSessionHandler.php';

final class MySQLSessionHandlerConcurrencyTest
{
    private mysqli $link;
    private string $sessionId;
    private string $workerPath;
    private array $temporaryFiles = [];

    public function run(): void
    {
        $this->connect();
        $this->sessionId = 'parallel-' . bin2hex(random_bytes(12));
        $this->workerPath = realpath(__DIR__ . '/../fixtures/session-lock-worker.php');

        if ($this->workerPath === false) {
            throw new RuntimeException('Session lock worker was not found.');
        }

        try {
            $this->createSessionTable();
            $this->runRepeatedParallelRequests(5);
            echo "PASS: one of two parallel submissions with the same client revision is rejected as stale across five repetitions\n";
        } finally {
            $this->deleteTestSession();
            foreach ($this->temporaryFiles as $file) {
                @unlink($file);
            }
            mysqli_close($this->link);
        }
    }

    private function connect(): void
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $this->link = mysqli_connect(
            getenv('RMT_TEST_DB_HOST') ?: '127.0.0.1',
            getenv('RMT_TEST_DB_USER') ?: 'root',
            getenv('RMT_TEST_DB_PASS') ?: '',
            getenv('RMT_TEST_DB_NAME') ?: 'rmt_session_test',
            (int) (getenv('RMT_TEST_DB_PORT') ?: 3306)
        );
    }

    private function createSessionTable(): void
    {
        mysqli_query($this->link, <<<'SQL'
CREATE TABLE IF NOT EXISTS tblphp_sessions (
    id VARCHAR(128) PRIMARY KEY,
    data LONGBLOB NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    accessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY accessed_at (accessed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
    }

    private function runRepeatedParallelRequests(int $repetitions): void
    {
        for ($iteration = 1; $iteration <= $repetitions; $iteration++) {
            $this->seedRevision(0);
            [$first, $second] = $this->runParallelPair();

            $this->assertSame(0, $first['read_revision'], "iteration {$iteration}: first request revision");
            $this->assertSame(1, $second['read_revision'], "iteration {$iteration}: second request sees committed revision");
            $this->assertTrue($first['accepted'], "iteration {$iteration}: first request accepted");
            $this->assertSame(false, $second['accepted'], "iteration {$iteration}: duplicate request rejected");
            $this->assertSame('stale_revision', $second['error'], "iteration {$iteration}: stale error code");
            $this->assertSame(1, $this->readStoredRevision(), "iteration {$iteration}: final revision");
            $this->assertTrue($second['wait_ms'] >= 250, "iteration {$iteration}: second request waited for the lock");
            $this->assertTrue($first['write_ok'] && $first['close_ok'], "iteration {$iteration}: first request completed");
            $this->assertTrue($second['write_ok'] && $second['close_ok'], "iteration {$iteration}: second request completed");
        }
    }

    private function seedRevision(int $revision): void
    {
        $stmt = mysqli_prepare(
            $this->link,
            'INSERT INTO tblphp_sessions (id, data, accessed_at) VALUES (?, ?, CURRENT_TIMESTAMP)
             ON DUPLICATE KEY UPDATE data = VALUES(data), accessed_at = CURRENT_TIMESTAMP'
        );
        $data = 'revision|i:' . $revision . ';';
        mysqli_stmt_bind_param($stmt, 'ss', $this->sessionId, $data);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    private function runParallelPair(): array
    {
        $firstOutput = $this->newTemporaryFile();
        $secondOutput = $this->newTemporaryFile();
        $environment = array_merge($_ENV, [
            'RMT_TEST_DB_HOST' => getenv('RMT_TEST_DB_HOST') ?: '127.0.0.1',
            'RMT_TEST_DB_PORT' => getenv('RMT_TEST_DB_PORT') ?: '3306',
            'RMT_TEST_DB_USER' => getenv('RMT_TEST_DB_USER') ?: 'root',
            'RMT_TEST_DB_PASS' => getenv('RMT_TEST_DB_PASS') ?: '',
            'RMT_TEST_DB_NAME' => getenv('RMT_TEST_DB_NAME') ?: 'rmt_session_test',
        ]);

        $first = $this->startWorker($firstOutput, 0, 400, $environment);
        usleep(100000);
        $second = $this->startWorker($secondOutput, 0, 0, $environment);

        $firstExit = proc_close($first);
        $secondExit = proc_close($second);
        $this->assertSame(0, $firstExit, 'first worker exit code');
        $this->assertSame(0, $secondExit, 'second worker exit code');

        return [$this->readWorkerResult($firstOutput), $this->readWorkerResult($secondOutput)];
    }

    private function startWorker(
        string $outputFile,
        int $clientRevision,
        int $holdMilliseconds,
        array $environment
    )
    {
        $command = [
            PHP_BINARY,
            $this->workerPath,
            $this->sessionId,
            (string) $clientRevision,
            (string) $holdMilliseconds,
            $outputFile,
        ];
        $process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes, null, $environment);
        if (!is_resource($process)) {
            throw new RuntimeException('Could not start session lock worker.');
        }
        return $process;
    }

    private function readWorkerResult(string $file): array
    {
        $contents = file_get_contents($file);
        if ($contents === false) {
            throw new RuntimeException("Could not read worker output {$file}.");
        }
        return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    }

    private function readStoredRevision(): int
    {
        $stmt = mysqli_prepare($this->link, 'SELECT data FROM tblphp_sessions WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 's', $this->sessionId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_stmt_get_result($stmt)->fetch_assoc();
        mysqli_stmt_close($stmt);

        if (!$row || !preg_match('/revision\|i:(\d+);/', (string) $row['data'], $matches)) {
            throw new RuntimeException('Stored session revision was not readable.');
        }
        return (int) $matches[1];
    }

    private function deleteTestSession(): void
    {
        if (!isset($this->link) || !isset($this->sessionId)) {
            return;
        }
        $stmt = mysqli_prepare($this->link, 'DELETE FROM tblphp_sessions WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 's', $this->sessionId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    private function newTemporaryFile(): string
    {
        $file = tempnam(sys_get_temp_dir(), 'rmt-session-lock-');
        if ($file === false) {
            throw new RuntimeException('Could not create worker output file.');
        }
        $this->temporaryFiles[] = $file;
        return $file;
    }

    private function assertSame($expected, $actual, string $message): void
    {
        if ($actual !== $expected) {
            throw new RuntimeException("FAIL: {$message}; expected " . var_export($expected, true)
                . ', got ' . var_export($actual, true));
        }
    }

    private function assertTrue(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new RuntimeException("FAIL: {$message}");
        }
    }
}

try {
    (new MySQLSessionHandlerConcurrencyTest())->run();
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}