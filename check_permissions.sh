#!/bin/bash

echo "=== Server Berechtigungs-Check ==="
echo "Aktueller Benutzer: $(whoami)"
echo "Aktuelles Verzeichnis: $(pwd)"
echo ""

echo "=== Verzeichnis-Berechtigungen ==="
ls -ld .
echo ""

echo "=== Datei-Berechtigungen ==="
ls -la
echo ""

echo "=== Schreibtest ==="
if touch test_write.tmp 2>/dev/null; then
    echo "✅ Schreibberechtigung: OK"
    rm -f test_write.tmp
else
    echo "❌ Schreibberechtigung: FEHLER"
    echo "Versuche Berechtigungen zu setzen..."
    
    # Versuche Berechtigungen zu korrigieren
    chmod 755 . 2>/dev/null || echo "Kann Verzeichnis-Berechtigungen nicht ändern"
    chmod 644 *.php *.txt .htaccess 2>/dev/null || echo "Kann Datei-Berechtigungen nicht ändern"
    chmod +x *.php 2>/dev/null || echo "Kann PHP-Ausführung nicht aktivieren"
fi

echo ""
echo "=== PHP-Test ==="
if php -v >/dev/null 2>&1; then
    echo "✅ PHP ist verfügbar: $(php -r 'echo PHP_VERSION;')"
else
    echo "❌ PHP ist nicht verfügbar"
fi

echo ""
echo "=== Webserver-spezifische Checks ==="
echo "Apache Benutzer könnte sein: www-data, apache, httpd"
echo "Setze Berechtigungen für Webserver..."

# Typische Webserver-Berechtigungen
chmod 755 . 2>/dev/null
chmod 644 *.php .htaccess 2>/dev/null
chmod 666 participants.txt assignments.txt 2>/dev/null || echo "Datendateien existieren noch nicht"

echo "Berechtigungen gesetzt."