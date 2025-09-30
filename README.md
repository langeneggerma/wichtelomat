# Wichtelomat 2.0 - Moderne Wichtel-Verteilung

Ein moderner, session-basierter Wichtelomat für Teams und Gruppen mit Echtzeit-Features und modernem UI.

## 🎯 Neue Features in Version 2.0

### 🔗 Session-basierte Funktionalität
- **Unique Sessions**: Jede Wichtel-Runde hat eine eindeutige Session-ID
- **Link-Sharing**: Einfaches Teilen des Session-Links mit allen Teilnehmern
- **Multi-Session**: Mehrere parallele Wichtel-Runden möglich

### 👥 Real-time Features
- **Live Online-Status**: Sehen Sie, wer gerade auf der Webseite ist
- **Auto-Updates**: Automatische Aktualisierung der Teilnehmerliste alle 5 Sekunden
- **Aktivitätsverfolgung**: Heartbeat-System für genauen Online-Status

### 🎨 Modernes UI/UX
- **Responsive Design**: Funktioniert perfekt auf Desktop und Mobile
- **Moderne Optik**: Clean, professionelles Design mit sanften Animationen
- **Bessere Benutzerführung**: Intuitive Navigation und klare Call-to-Actions
- **Dark Mode Support**: Automatische Anpassung an System-Einstellungen

### 🔒 Maximale Privatsphäre
- **Persönliche Sicht**: Jeder Teilnehmer sieht nur seine eigene Zuordnung
- **Keine Spoiler**: Auch Organisatoren können keine anderen Zuordnungen einsehen
- **Sichere API**: Technisch unmöglich, alle Zuordnungen abzurufen
- **Garantierte Überraschung**: Komplette Geheimhaltung bis zum Wichtel-Tag

## 🚀 Installation

1. **Dateien hochladen**: Alle Dateien auf einen Webserver mit PHP 7.4+ kopieren
2. **Berechtigungen setzen**: Sicherstellen, dass das Verzeichnis schreibbar ist
3. **Browser öffnen**: Die `index.php` aufrufen

## 📋 Verwendung

### Session starten
1. **Neue Session**: Beim ersten Aufruf wird automatisch eine neue Session erstellt
2. **Link teilen**: Den angezeigten Session-Link an alle Teilnehmer verteilen
3. **Teilnehmer sammeln**: Jeder trägt sich über den Link ein

### Wichteln
1. **Online-Status**: Sehen Sie in Echtzeit, wer teilnimmt und online ist
2. **Wichtelomat starten**: Wenn alle da sind, den Start-Button klicken
3. **Persönliche Zuordnung**: Jeder sieht automatisch nur seine eigene Zuordnung
4. **Maximale Privatsphäre**: Niemand (auch nicht der Organisator) kann andere Zuordnungen einsehen
5. **Überraschung garantiert**: Alle Zuordnungen bleiben geheim bis zum Wichtel-Tag

### Verwaltung
- **Reset-Funktion**: Komplettes Zurücksetzen für neue Runden
- **Auto-Cleanup**: Alte Sessions werden automatisch nach 24h gelöscht

## 🛠 Technische Details

### Systemanforderungen
- **PHP**: Version 7.4 oder höher
- **Webserver**: Apache mit mod_rewrite (empfohlen)
- **Speicher**: Schreibberechtigungen im Projektverzeichnis

### Architektur
```
wichtelomat/
├── index.php              # Hauptdatei mit UI
├── api.php                # REST API für AJAX-Requests
├── config.php             # Konfiguration und Konstanten
├── WichtelomatSession.php # Session-Management-Klasse
├── style.css              # Modernes CSS-Framework
├── script.js              # JavaScript für Echtzeit-Features
├── .htaccess              # Sicherheitskonfiguration
└── data/                  # Session-Datenverzeichnis
    └── sessions/          # JSON-Session-Dateien
```

### Session-Management
- **Format**: JSON-Dateien pro Session
- **Daten**: Teilnehmer, Online-Status, Zuordnungen, Metadaten
- **Cleanup**: Automatische Bereinigung nach 24 Stunden Inaktivität

### Real-time Updates
- **Polling**: 5-Sekunden-Intervall für Updates
- **Heartbeat**: Aktivitätsverfolgung über JavaScript
- **Optimiert**: Nur notwendige Daten werden übertragen

## 🔧 Konfiguration

### Session-Timeout anpassen
```php
// In config.php
define('SESSION_TIMEOUT', 24 * 60 * 60); // 24 Stunden
```

### Update-Intervall anpassen
```javascript
// In script.js, Klasse Wichtelomat
this.updateInterval = setInterval(() => {
    this.updatePageData();
}, 5000); // 5 Sekunden
```

## 🛡️ Sicherheitshinweise

### Für Produktivumgebung
- **SSL verwenden**: HTTPS für sichere Übertragung
- **Backup einrichten**: Regelmäßige Sicherung der Session-Daten
- **Monitoring**: Überwachung der Session-Verzeichnisse
- **Firewall**: Zusätzlicher Schutz über Webserver-Konfiguration

### Datenschutz
- **Lokale Speicherung**: Keine externe Datenübertragung
- **Anonymisierung**: Nur Namen werden gespeichert
- **Automatische Löschung**: Sessions werden automatisch bereinigt

## 🔄 Migration von Version 1.0

Die neue Version ist kompatibel mit der alten, aber bietet erweiterte Features:

1. **Bestehende Daten**: Werden nicht automatisch migriert
2. **Parallelbetrieb**: Alte und neue Version können parallel laufen
3. **Gradueller Umstieg**: Schrittweise Migration möglich

## 🐛 Fehlerbehebung

### Häufige Probleme
- **Sessions werden nicht gespeichert**: Schreibberechtigungen prüfen
- **Online-Status nicht aktuell**: JavaScript-Konsole auf Fehler prüfen
- **Layout-Probleme**: Browser-Cache leeren

### Debug-Modus
```php
// In config.php hinzufügen für Debug-Ausgaben
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 📱 Browser-Kompatibilität

- **Chrome**: Version 90+
- **Firefox**: Version 88+
- **Safari**: Version 14+
- **Edge**: Version 90+
- **Mobile**: iOS Safari 14+, Chrome Mobile 90+

## 🤝 Beitragen

Das Projekt ist Open Source. Verbesserungen und Erweiterungen sind willkommen!

### Entwicklung
```bash
# Lokale Entwicklung
php -S localhost:8000
```

## 📄 Lizenz

MIT License - Freie Nutzung für private und kommerzielle Zwecke.

---

**Viel Spaß beim Wichteln! 🎁**