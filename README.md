# Wichtelomat

Ein einfacher PHP-basierter Wichtelomat für die Weihnachtszeit.

## Funktionen

- **Teilnehmer hinzufügen**: Jeder kann seinen Namen eingeben
- **Automatische Zufallszuteilung**: Startet die Wichtel-Zuordnung
- **Schutz vor Selbstzuteilung**: Niemand wird sich selbst zugeordnet
- **Einfaches Reset**: Alles zurücksetzen für eine neue Runde

## Installation

1. Dateien auf einen Webserver mit PHP-Unterstützung kopieren
2. Sicherstellen, dass das Verzeichnis schreibbar ist (für participants.txt und assignments.txt)
3. Die index.php im Browser aufrufen

## Verwendung

1. **Namen eingeben**: Jeder Teilnehmer gibt seinen Namen ein
2. **Teilnehmer sammeln**: Warten bis alle Namen eingegeben sind
3. **Wichtelomat starten**: Button klicken für die Zufallszuteilung
4. **Ergebnisse anschauen**: Wer beschenkt wen wird angezeigt
5. **Zurücksetzen**: Für eine neue Runde alles löschen

## Technische Details

- PHP-basiert (keine Datenbank erforderlich)
- Speichert Daten in einfachen Textdateien
- Responsive Design für mobile Geräte
- Einfaches, weihnachtliches Design

## Sicherheitshinweise

- Die Textdateien sollten außerhalb des Webroot-Verzeichnisses gespeichert werden (für Produktivumgebung)
- Für öffentliche Server sollten zusätzliche Sicherheitsmaßnahmen implementiert werden

## Dateien

- `index.php` - Hauptdatei mit der gesamten Logik
- `participants.txt` - Wird automatisch erstellt für Teilnehmerliste
- `assignments.txt` - Wird automatisch erstellt für Zuordnungen