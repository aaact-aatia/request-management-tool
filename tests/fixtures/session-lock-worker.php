<?php

if ($argc !== 5) {
    fwrite(STDERR, "Usage: session-lock-worker.php SESSION_ID CLIENT_REVISION HOLD_MS OUTPUT_FILE\n");
    exit(2);
}

$sessionId = $argv[1];
$clientRevision = (int) $argv[2];
$holdMilliseconds = (int) $argv[3];
$outputFile = $argv[4];

$link = mysqli_connect(
    getenv('RMT_TEST_DB_HOST') ?: '127.0.0.1',
    getenv('RMT_TEST_DB_USER') ?: 'root',
    getenv('RMT_TEST_DB_PASS') ?: '',
    getenv('RMT_TEST_DB_NAME') ?: 'rmt_session_test',
    (int) (getenv('RMT_TEST_DB_PORT') ?: 3306)
);

if (!$link) {
    fwrite(STDERR, 'Database connection failed: ' . mysqli_connect_error() . "\n");
    exit(3);
}

require_once __DIR__ . '/../../app/includes/MySQLSessionHandler.php';
require_once __DIR__ . '/../../app/includes/intake-flow-helpers.php';

$handler = new MySQLSessionHandler($link, 86400, 10);
$startedAt = microtime(true);
$data = $handler->read($sessionId);
$acquiredAt = microtime(true);

if ($data === false) {
    fwrite(STDERR, "Session read failed\n");
    exit(4);
}

$revision = 0;
if ($data !== '' && preg_match('/revision\|i:(\d+);/', $data, $matches)) {
    $revision = (int) $matches[1];
}

$accepted = rmt_intake_revision_is_current(['revision' => $revision], $clientRevision);
$newRevision = $revision;
$written = true;

if ($accepted) {
    if ($holdMilliseconds > 0) {
        usleep($holdMilliseconds * 1000);
    }

    $newRevision = $revision + 1;
    $written = $handler->write($sessionId, 'revision|i:' . $newRevision . ';');
}

$closed = $handler->close();
$finishedAt = microtime(true);
mysqli_close($link);

$result = [
    'read_revision' => $revision,
    'written_revision' => $newRevision,
    'accepted' => $accepted,
    'error' => $accepted ? null : 'stale_revision',
    'wait_ms' => (int) round(($acquiredAt - $startedAt) * 1000),
    'duration_ms' => (int) round(($finishedAt - $startedAt) * 1000),
    'write_ok' => $written,
    'close_ok' => $closed,
];

if (file_put_contents($outputFile, json_encode($result, JSON_THROW_ON_ERROR)) === false) {
    fwrite(STDERR, "Could not write worker output\n");
    exit(5);
}

exit(($written && $closed) ? 0 : 6);