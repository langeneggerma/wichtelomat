<?php
require_once 'config.php';
require_once 'WichtelomatSession.php';

session_start();

// Handle session creation and management
$sessionId = $_GET['session'] ?? $_POST['session_id'] ?? '';
$isNewSession = false;

if (empty($sessionId)) {
    // Create new session
    $session = new WichtelomatSession();
    $sessionId = $session->getSessionId();
    $isNewSession = true;
} else {
    // Load existing session
    $session = new WichtelomatSession($sessionId);
    if (!$session->exists()) {
        // Session doesn't exist, create new one
        $session = new WichtelomatSession();
        $sessionId = $session->getSessionId();
        $isNewSession = true;
    }
}

// Process form actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_participant':
            $name = trim($_POST['name'] ?? '');
            if (empty($name)) {
                $error = "Bitte geben Sie einen Namen ein!";
            } elseif (strlen($name) < 2) {
                $error = "Der Name muss mindestens 2 Zeichen haben!";
            } elseif (strlen($name) > 50) {
                $error = "Der Name darf höchstens 50 Zeichen haben!";
            } elseif ($session->addParticipant($name)) {
                $message = "Teilnehmer '$name' wurde hinzugefügt!";
                
                // Set user as online
                $session->setUserOnline($name);
                
                // Store username in session for activity tracking
                $_SESSION['wichtel_username'] = $name;
            } else {
                $error = "Name ist bereits vorhanden!";
            }
            break;
            
        case 'start_wichtelomat':
            if ($session->createAssignments()) {
                $message = "🎉 Wichtelomat wurde gestartet! Die Zuordnungen sind bereit.";
            } else {
                $error = "Mindestens 2 Teilnehmer erforderlich!";
            }
            break;
            
        case 'reset':
            $session->reset();
            $message = "Wichtelomat wurde zurückgesetzt!";
            unset($_SESSION['wichtel_username']);
            break;
    }
}

// Update user online status if logged in
if (isset($_SESSION['wichtel_username'])) {
    $session->setUserOnline($_SESSION['wichtel_username']);
}

// Get current data
$participants = $session->getParticipants();
$assignments = $session->getAssignments();
$onlineUsers = $session->getOnlineUsers();
$sessionInfo = $session->getSessionInfo();
$status = $session->getStatus();

// Generate session link
$sessionLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
    . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?session=" . $sessionId;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wichtelomat - Moderne Wichtel-Verteilung</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🎁</text></svg>">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🎁 Wichtelomat</h1>
            <p class="subtitle">Moderne Wichtel-Verteilung für Teams</p>
            
            <?php if (!$isNewSession): ?>
            <div class="session-info">
                <div>
                    <strong>Session:</strong> 
                    <span class="session-id"><?= htmlspecialchars($sessionId) ?></span>
                    <button id="copy-session-link" class="copy-btn" title="Session-Link kopieren">📋 Kopieren</button>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success"><?= $message ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($isNewSession): ?>
                <!-- New Session Welcome -->
                <div class="alert alert-info">
                    <strong>🎉 Neue Session erstellt!</strong><br>
                    Teilen Sie diesen Link mit allen Teilnehmern:<br>
                    <code><?= htmlspecialchars($sessionLink) ?></code>
                    <button id="copy-session-link" class="copy-btn" style="margin-left: 10px;">📋 Link kopieren</button>
                </div>
            <?php endif; ?>

            <!-- Statistics Bar -->
            <div class="stats-bar">
                <div class="stat-item">
                    <span class="stat-number" id="participant-count"><?= count($participants) ?></span>
                    <span class="stat-label">Teilnehmer</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" id="online-count"><?= count($onlineUsers) ?></span>
                    <span class="stat-label">Online</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number status-<?= $status ?>"><?= ucfirst($status) ?></span>
                    <span class="stat-label">Status</span>
                </div>
            </div>

            <!-- Add Participant Form -->
            <?php if ($status === 'waiting'): ?>
            <div class="card">
                <div class="card-header">
                    <span>👤 Neuen Teilnehmer hinzufügen</span>
                </div>
                <div class="card-body">
                    <form method="post" id="add-participant-form">
                        <input type="hidden" name="session_id" value="<?= htmlspecialchars($sessionId) ?>">
                        <div class="form-group">
                            <label for="participant-name" class="form-label">Dein Name:</label>
                            <input type="text" 
                                   id="participant-name" 
                                   name="name" 
                                   class="form-control" 
                                   placeholder="Gib deinen Namen ein..."
                                   required 
                                   minlength="2" 
                                   maxlength="50"
                                   autocomplete="name">
                        </div>
                        <button type="submit" name="action" value="add_participant" class="btn btn-primary">
                            👋 Ich bin dabei!
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Participants List -->
            <?php if (!empty($participants)): ?>
            <div class="card">
                <div class="card-header">
                    <span>👥 Teilnehmer (<?= count($participants) ?>)</span>
                    <small style="float: right;">Letztes Update: <span id="last-update"><?= date('H:i:s') ?></span></small>
                </div>
                <div class="card-body">
                    <div class="participants-grid" id="participants-list">
                        <?php foreach ($participants as $participant): ?>
                            <?php $isOnline = isset($onlineUsers[$participant['name']]); ?>
                            <div class="participant-item <?= $isOnline ? 'online' : 'offline' ?>">
                                <div class="participant-name">
                                    <span class="status-indicator <?= $isOnline ? 'status-online' : 'status-offline' ?>"></span>
                                    <?= htmlspecialchars($participant['name']) ?>
                                </div>
                                <div class="participant-status">
                                    <?= $isOnline ? 'Online' : 'Offline' ?>
                                    <?php if (!$isOnline && isset($participant['joined'])): ?>
                                        <br><small>Beigetreten: <?= date('H:i', $participant['joined']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Control Buttons -->
            <?php if (!empty($participants) && $status === 'waiting'): ?>
            <div class="card" id="waiting-section">
                <div class="card-body">
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="session_id" value="<?= htmlspecialchars($sessionId) ?>">
                        <button type="submit" 
                                name="action" 
                                value="start_wichtelomat" 
                                class="btn btn-success"
                                <?= count($participants) < 2 ? 'disabled' : '' ?>
                                onclick="return confirm('Wichtelomat jetzt starten? Danach können keine weiteren Teilnehmer hinzugefügt werden!')">
                            🎯 Wichtelomat starten!
                        </button>
                        <?php if (count($participants) < 2): ?>
                            <small class="text-muted">Mindestens 2 Teilnehmer erforderlich</small>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Personal Assignment Display -->
            <div class="card" id="assignment-section" style="display: <?= $status === 'started' ? 'block' : 'none' ?>;">
                <div class="card-header">
                    <span>🎁 Deine Wichtel-Zuordnung</span>
                </div>
                <div class="card-body">
                    <div id="user-assignment">
                        <?php if ($status === 'started' && isset($_SESSION['wichtel_username'])): ?>
                            <?php 
                            $userAssignment = $session->getAssignmentForUser($_SESSION['wichtel_username']);
                            if ($userAssignment): 
                            ?>
                                <div class="alert alert-success">
                                    <strong>🎁 Deine Zuordnung:</strong><br>
                                    Du beschenkst: <strong><?= htmlspecialchars($userAssignment) ?></strong>
                                </div>
                                <div class="alert alert-info" style="margin-top: 1rem;">
                                    <strong>🤫 Pssst!</strong><br>
                                    Das ist nur für dich bestimmt. Jeder andere sieht nur seine eigene Zuordnung - 
                                    so bleibt es für alle eine Überraschung! 🎉
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <strong>⚠️ Keine Zuordnung gefunden</strong><br>
                                    Du musst dich zuerst als Teilnehmer eintragen!
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <strong>⏳ Warten auf Start</strong><br>
                                Der Wichtelomat wurde noch nicht gestartet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Reset Section -->
            <?php if (!empty($participants) || !empty($assignments)): ?>
            <div class="card">
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="session_id" value="<?= htmlspecialchars($sessionId) ?>">
                        <button type="submit" 
                                name="action" 
                                value="reset" 
                                class="btn btn-danger"
                                onclick="return confirm('⚠️ Wirklich alles zurücksetzen? Alle Teilnehmer und Zuordnungen werden gelöscht!')">
                            🔄 Komplett zurücksetzen
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Auto-refresh Toggle -->
            <div class="card">
                <div class="card-body">
                    <label style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" id="auto-refresh" checked>
                        <span>🔄 Automatische Aktualisierung</span>
                    </label>
                    <small class="text-muted">Zeigt neue Teilnehmer und Online-Status in Echtzeit an</small>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Wichtelomat v2.0 - Session: <?= htmlspecialchars($sessionId) ?> | 
            Erstellt: <?= date('d.m.Y H:i', $sessionInfo['created']) ?></p>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
        // Pass session data to JavaScript
        window.wichtelomatData = {
            sessionId: '<?= htmlspecialchars($sessionId) ?>',
            username: '<?= htmlspecialchars($_SESSION['wichtel_username'] ?? '') ?>',
            status: '<?= htmlspecialchars($status) ?>'
        };
    </script>
</body>
</html>