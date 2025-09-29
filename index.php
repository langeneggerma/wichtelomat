<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Datei für Teilnehmer und Zuordnungen (korrekte Windows-Pfade)
$baseDir = __DIR__;
$participantsFile = $baseDir . DIRECTORY_SEPARATOR . 'participants.txt';
$assignmentsFile = $baseDir . DIRECTORY_SEPARATOR . 'assignments.txt';

// Funktionen
function getParticipants() {
    global $participantsFile;
    if (!file_exists($participantsFile)) {
        return [];
    }
    $content = file_get_contents($participantsFile);
    return array_filter(explode("\n", trim($content)));
}

function addParticipant($name) {
    global $participantsFile;
    $name = trim($name);
    if (empty($name)) return false;
    
    $participants = getParticipants();
    if (in_array($name, $participants)) {
        return false; // Name bereits vorhanden
    }
    
    $result = file_put_contents($participantsFile, $name . "\n", FILE_APPEND | LOCK_EX);
    
    // Debug: Log the attempt
    error_log("addParticipant: name='$name', file='$participantsFile', result=" . ($result !== false ? 'SUCCESS' : 'FAILED') . ", file_exists=" . (file_exists($participantsFile) ? 'YES' : 'NO'));
    
    return $result !== false;
}

function createAssignments() {
    global $assignmentsFile;
    $participants = getParticipants();
    
    if (count($participants) < 2) {
        return false;
    }
    
    $givers = $participants;
    $receivers = $participants;
    
    // Mische die Empfänger
    shuffle($receivers);
    
    // Stelle sicher, dass niemand sich selbst zugeordnet wird
    for ($i = 0; $i < count($givers); $i++) {
        if ($givers[$i] === $receivers[$i]) {
            // Tausche mit dem nächsten (oder ersten, wenn am Ende)
            $swapWith = ($i + 1) % count($receivers);
            $temp = $receivers[$i];
            $receivers[$i] = $receivers[$swapWith];
            $receivers[$swapWith] = $temp;
        }
    }
    
    // Speichere die Zuordnungen
    $assignments = [];
    for ($i = 0; $i < count($givers); $i++) {
        $assignments[] = $givers[$i] . ' => ' . $receivers[$i];
    }
    
    file_put_contents($assignmentsFile, implode("\n", $assignments));
    return true;
}

function getAssignments() {
    global $assignmentsFile;
    if (!file_exists($assignmentsFile)) {
        return [];
    }
    $content = file_get_contents($assignmentsFile);
    return array_filter(explode("\n", trim($content)));
}

function resetWichtelomat() {
    global $participantsFile, $assignmentsFile;
    if (file_exists($participantsFile)) unlink($participantsFile);
    if (file_exists($assignmentsFile)) unlink($assignmentsFile);
}

// Aktionen verarbeiten
$message = '';
$error = '';

if ($_POST['action'] ?? '' === 'add_participant') {
    $name = $_POST['name'] ?? '';
    if (addParticipant($name)) {
        $message = "Teilnehmer '$name' wurde hinzugefügt!";
    } else {
        $error = "Name ist bereits vorhanden oder ungültig!";
    }
    // Debug: Überprüfe ob Datei existiert
    if (file_exists($participantsFile)) {
        $message .= " (Datei: $participantsFile existiert)";
    } else {
        $error .= " (FEHLER: Datei $participantsFile wurde nicht erstellt!)";
    }
}

if ($_POST['action'] ?? '' === 'start_wichtelomat') {
    if (createAssignments()) {
        $message = "Wichtelomat wurde gestartet! Die Zuordnungen sind bereit.";
    } else {
        $error = "Mindestens 2 Teilnehmer erforderlich!";
    }
}

if ($_POST['action'] ?? '' === 'reset') {
    resetWichtelomat();
    $message = "Wichtelomat wurde zurückgesetzt!";
}

$participants = getParticipants();
$assignments = getAssignments();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wichtelomat</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #c41e3a;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        button {
            background-color: #c41e3a;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        button:hover {
            background-color: #a01729;
        }
        .reset-btn {
            background-color: #666;
        }
        .reset-btn:hover {
            background-color: #555;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .participants-list {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .participants-list h3 {
            margin-top: 0;
            color: #495057;
        }
        .participant {
            padding: 5px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .participant:last-child {
            border-bottom: none;
        }
        .assignments {
            background-color: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border: 1px solid #ffeaa7;
        }
        .assignments h3 {
            margin-top: 0;
            color: #856404;
        }
        .assignment {
            padding: 8px 0;
            border-bottom: 1px solid #ffeaa7;
            font-weight: bold;
        }
        .assignment:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎁 Wichtelomat 🎁</h1>
        
        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (empty($assignments)): ?>
            <form method="post">
                <div class="form-group">
                    <label for="name">Name eingeben:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <button type="submit" name="action" value="add_participant">Teilnehmer hinzufügen</button>
            </form>
        <?php endif; ?>
        
        <?php if (!empty($participants)): ?>
            <div class="participants-list">
                <h3>Teilnehmer (<?= count($participants) ?>):</h3>
                <?php foreach ($participants as $participant): ?>
                    <div class="participant"><?= htmlspecialchars($participant) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($participants) && empty($assignments)): ?>
            <form method="post">
                <button type="submit" name="action" value="start_wichtelomat">Wichtelomat starten!</button>
            </form>
        <?php endif; ?>
        
        <?php if (!empty($assignments)): ?>
            <div class="assignments">
                <h3>🎯 Wichtel-Zuordnungen:</h3>
                <?php foreach ($assignments as $assignment): ?>
                    <div class="assignment"><?= htmlspecialchars($assignment) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($participants) || !empty($assignments)): ?>
            <form method="post" style="margin-top: 30px;">
                <button type="submit" name="action" value="reset" class="reset-btn" 
                        onclick="return confirm('Wirklich alles zurücksetzen?')">Zurücksetzen</button>
            </form>
        <?php endif; ?>
        
        <!-- Debug Information -->
        <div style="margin-top: 30px; padding: 10px; background: #f8f9fa; border-radius: 5px; font-size: 12px; color: #666;">
            <strong>Debug Info:</strong><br>
            Verzeichnis: <?= htmlspecialchars($baseDir) ?><br>
            Participants-Datei: <?= htmlspecialchars($participantsFile) ?> 
            <?= file_exists($participantsFile) ? '✅ existiert' : '❌ existiert nicht' ?><br>
            Assignments-Datei: <?= htmlspecialchars($assignmentsFile) ?> 
            <?= file_exists($assignmentsFile) ? '✅ existiert' : '❌ existiert nicht' ?><br>
            Schreibberechtigung: <?= is_writable($baseDir) ? '✅ OK' : '❌ Fehler' ?>
        </div>
    </div>
</body>
</html>