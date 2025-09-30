<?php
require_once 'config.php';
require_once 'WichtelomatSession.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Enable CORS for local development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = $_REQUEST['action'] ?? '';
$response = ['success' => false, 'message' => ''];

try {
    switch ($action) {
        case 'get_session_data':
            $sessionId = $_GET['session_id'] ?? '';
            $username = $_GET['username'] ?? '';
            
            if (empty($sessionId)) {
                throw new Exception('Session ID required');
            }
            
            $session = new WichtelomatSession($sessionId);
            if (!$session->exists()) {
                throw new Exception('Session not found');
            }
            
            $participants = $session->getParticipants();
            $onlineUsers = $session->getOnlineUsers();
            $status = $session->getStatus();
            
            // Only include user's personal assignment if username provided and assignments exist
            $userAssignment = null;
            if (!empty($username) && $status === 'started') {
                $userAssignment = $session->getAssignmentForUser($username);
            }
            
            $response = [
                'success' => true,
                'participants' => $participants,
                'online_users' => $onlineUsers,
                'status' => $status,
                'user_assignment' => $userAssignment,
                'assignments_ready' => $status === 'started',
                'stats' => [
                    'participant_count' => count($participants),
                    'online_count' => count($onlineUsers),
                    'status' => $status
                ]
            ];
            break;
            
        case 'heartbeat':
            $sessionId = $_POST['session_id'] ?? '';
            $username = $_POST['username'] ?? '';
            
            if (empty($sessionId) || empty($username)) {
                throw new Exception('Session ID and username required');
            }
            
            $session = new WichtelomatSession($sessionId);
            if ($session->exists()) {
                $session->setUserOnline($username);
                $response = ['success' => true, 'message' => 'Heartbeat updated'];
            } else {
                throw new Exception('Session not found');
            }
            break;
            
        case 'get_assignment':
            $sessionId = $_POST['session_id'] ?? '';
            $username = $_POST['username'] ?? '';
            
            if (empty($sessionId) || empty($username)) {
                throw new Exception('Session ID and username required');
            }
            
            $session = new WichtelomatSession($sessionId);
            if (!$session->exists()) {
                throw new Exception('Session not found');
            }
            
            $assignment = $session->getAssignmentForUser($username);
            $response = [
                'success' => true,
                'assignment' => $assignment,
                'message' => $assignment ? "Du beschenkst: $assignment" : 'Keine Zuordnung gefunden'
            ];
            break;
            
        case 'remove_participant':
            $sessionId = $_POST['session_id'] ?? '';
            $username = $_POST['username'] ?? '';
            
            if (empty($sessionId) || empty($username)) {
                throw new Exception('Session ID and username required');
            }
            
            $session = new WichtelomatSession($sessionId);
            if (!$session->exists()) {
                throw new Exception('Session not found');
            }
            
            // Only allow removal if assignments haven't been created
            if ($session->getStatus() === 'waiting') {
                $session->removeParticipant($username);
                $response = ['success' => true, 'message' => 'Teilnehmer entfernt'];
            } else {
                throw new Exception('Cannot remove participant after assignments are created');
            }
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);
?>