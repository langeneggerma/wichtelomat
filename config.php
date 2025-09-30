<?php
// Configuration file for Wichtelomat
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Base directory and data storage
define('BASE_DIR', __DIR__);
define('DATA_DIR', BASE_DIR . DIRECTORY_SEPARATOR . 'data');
define('SESSIONS_DIR', DATA_DIR . DIRECTORY_SEPARATOR . 'sessions');

// Create data directories if they don't exist
if (!file_exists(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}
if (!file_exists(SESSIONS_DIR)) {
    mkdir(SESSIONS_DIR, 0755, true);
}

// Session cleanup interval (remove inactive sessions after 24 hours)
define('SESSION_TIMEOUT', 24 * 60 * 60); // 24 hours

// Function to generate unique session ID
function generateSessionId() {
    return bin2hex(random_bytes(16));
}

// Function to clean up old sessions
function cleanupOldSessions() {
    $sessions = glob(SESSIONS_DIR . DIRECTORY_SEPARATOR . '*.json');
    $now = time();
    
    foreach ($sessions as $sessionFile) {
        $lastModified = filemtime($sessionFile);
        if ($now - $lastModified > SESSION_TIMEOUT) {
            unlink($sessionFile);
        }
    }
}

// Run cleanup occasionally
if (rand(1, 100) <= 5) { // 5% chance on each request
    cleanupOldSessions();
}
?>