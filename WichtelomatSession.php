<?php
require_once 'config.php';

class WichtelomatSession {
    private $sessionId;
    private $sessionFile;
    private $data;
    
    public function __construct($sessionId = null) {
        if ($sessionId) {
            $this->sessionId = $sessionId;
        } else {
            $this->sessionId = generateSessionId();
        }
        
        $this->sessionFile = SESSIONS_DIR . DIRECTORY_SEPARATOR . $this->sessionId . '.json';
        $this->loadSession();
    }
    
    private function loadSession() {
        if (file_exists($this->sessionFile)) {
            $json = file_get_contents($this->sessionFile);
            $this->data = json_decode($json, true) ?: [];
        } else {
            $this->data = [
                'id' => $this->sessionId,
                'created' => time(),
                'participants' => [],
                'assignments' => [],
                'online_users' => [],
                'status' => 'waiting', // waiting, started, finished
                'last_activity' => time()
            ];
            $this->saveSession();
        }
    }
    
    private function saveSession() {
        $this->data['last_activity'] = time();
        file_put_contents($this->sessionFile, json_encode($this->data, JSON_PRETTY_PRINT), LOCK_EX);
    }
    
    public function getSessionId() {
        return $this->sessionId;
    }
    
    public function exists() {
        return file_exists($this->sessionFile);
    }
    
    public function addParticipant($name) {
        $name = trim($name);
        if (empty($name)) return false;
        
        // Check if name already exists
        foreach ($this->data['participants'] as $participant) {
            if (strcasecmp($participant['name'], $name) === 0) {
                return false;
            }
        }
        
        $this->data['participants'][] = [
            'name' => $name,
            'joined' => time(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $this->saveSession();
        return true;
    }
    
    public function removeParticipant($name) {
        $this->data['participants'] = array_filter($this->data['participants'], function($p) use ($name) {
            return strcasecmp($p['name'], $name) !== 0;
        });
        $this->data['participants'] = array_values($this->data['participants']); // Reindex
        $this->saveSession();
    }
    
    public function getParticipants() {
        return $this->data['participants'];
    }
    
    public function setUserOnline($username, $ip = null) {
        $ip = $ip ?: ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $this->data['online_users'][$username] = [
            'last_seen' => time(),
            'ip' => $ip
        ];
        $this->saveSession();
    }
    
    public function getOnlineUsers($timeoutMinutes = 5) {
        $timeout = $timeoutMinutes * 60;
        $now = time();
        $onlineUsers = [];
        
        foreach ($this->data['online_users'] as $username => $data) {
            if ($now - $data['last_seen'] <= $timeout) {
                $onlineUsers[$username] = $data;
            }
        }
        
        return $onlineUsers;
    }
    
    public function createAssignments() {
        $participants = $this->data['participants'];
        
        if (count($participants) < 2) {
            return false;
        }
        
        $names = array_column($participants, 'name');
        $givers = $names;
        $receivers = $names;
        
        // Shuffle receivers
        shuffle($receivers);
        
        // Ensure nobody gets themselves
        $maxAttempts = 100;
        $attempts = 0;
        
        do {
            $hasConflict = false;
            for ($i = 0; $i < count($givers); $i++) {
                if ($givers[$i] === $receivers[$i]) {
                    $hasConflict = true;
                    // Swap with next person (or first if at end)
                    $swapWith = ($i + 1) % count($receivers);
                    $temp = $receivers[$i];
                    $receivers[$i] = $receivers[$swapWith];
                    $receivers[$swapWith] = $temp;
                    break;
                }
            }
            $attempts++;
        } while ($hasConflict && $attempts < $maxAttempts);
        
        // Create assignments
        $assignments = [];
        for ($i = 0; $i < count($givers); $i++) {
            $assignments[] = [
                'giver' => $givers[$i],
                'receiver' => $receivers[$i],
                'created' => time()
            ];
        }
        
        $this->data['assignments'] = $assignments;
        $this->data['status'] = 'started';
        $this->saveSession();
        
        return true;
    }
    
    public function getAssignments() {
        return $this->data['assignments'];
    }
    
    public function getAssignmentForUser($username) {
        foreach ($this->data['assignments'] as $assignment) {
            if (strcasecmp($assignment['giver'], $username) === 0) {
                return $assignment['receiver'];
            }
        }
        return null;
    }
    
    public function getStatus() {
        return $this->data['status'];
    }
    
    public function reset() {
        $this->data['participants'] = [];
        $this->data['assignments'] = [];
        $this->data['status'] = 'waiting';
        $this->saveSession();
    }
    
    public function getData() {
        return $this->data;
    }
    
    public function getSessionInfo() {
        return [
            'id' => $this->sessionId,
            'status' => $this->data['status'],
            'participant_count' => count($this->data['participants']),
            'online_count' => count($this->getOnlineUsers()),
            'created' => $this->data['created'],
            'last_activity' => $this->data['last_activity']
        ];
    }
}