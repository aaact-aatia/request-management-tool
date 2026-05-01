<?php

class Logger {
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_INFO = 'INFO';
    
    private $logFile;

    public function __construct($logFilePath) {
        // Ensure the log file path is absolute
        if (substr($logFilePath, 0, 1) !== '/') {
            $logFilePath = '/' . $logFilePath;
        }
        $this->logFile = $logFilePath;
    }




    private function getColoredString($string, $color) {
        $colors = [
            'red' => "\033[31m",
            'green' => "\033[32m",
            'yellow' => "\033[33m",
            'reset' => "\033[0m"
        ];

        return isset($colors[$color]) ? $colors[$color] . $string . $colors['reset'] : $string;
    }

    private function formatMessage($level, $id, $message) {
        $timestamp = date('Y-m-d H:i:s');
        return "[$timestamp] [$level] [ID: $id] $message";
    }

    private function writeLog($level, $id, $message) {
        $formattedMessage = $this->formatMessage($level, $id, $message);
        $color = $level == self::LEVEL_ERROR ? 'red' : ($level == self::LEVEL_WARNING ? 'yellow' : 'green');
        $coloredMessage = $this->getColoredString($formattedMessage, $color);
        
        file_put_contents($this->logFile, $formattedMessage . PHP_EOL, FILE_APPEND);
        
    }

    public function error($id, $message) {
        $this->writeLog(self::LEVEL_ERROR, $id, $message);
    }

    public function warning($id, $message) {
        $this->writeLog(self::LEVEL_WARNING, $id, $message);
    }

    public function info($id, $message) {
        $this->writeLog(self::LEVEL_INFO, $id, $message);
    }
}
?>
