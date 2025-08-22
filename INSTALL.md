# JiraExporter Installation & Setup Guide

## Schnellstart mit Docker

1. **Repository klonen und in das Verzeichnis wechseln:**
```bash
git clone <repository-url>
cd jiraExporter
```

2. **Umgebungsvariablen konfigurieren:**
```bash
cp .env.local.example .env.local
```
Bearbeiten Sie `.env.local` und tragen Sie Ihre Jira-Konfiguration ein:
- `JIRA_BASE_URL`: URL Ihrer Jira-Instanz
- `JIRA_USERNAME`: Jira-Benutzername  
- `JIRA_PASSWORD`: Jira-Passwort
- `JIRA_VERIFY_TLS`: `true` oder `false`
- `EXPORT_BASE_DIR`: Pfad für CSV-Exporte (z.B. `./exports`)

3. **Docker-Container starten:**
```bash
docker-compose up -d
```

4. **Abhängigkeiten installieren:**
```bash
docker-compose exec php composer install
```

5. **Datenbank-Schema erstellen:**
```bash
docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction
```

6. **Anwendung initialisieren:**
```bash
docker-compose exec php php bin/console app:install
```
Folgen Sie den Anweisungen zur Erstellung des ersten Admin-Benutzers.

7. **Zugriff auf die Anwendung:**
- Web-UI: http://localhost:8087
- Health-Check: http://localhost:8087/health

## CLI-Verwendung

### Job ausführen
```bash
docker-compose exec php php bin/console app:run-job --job-id=1
docker-compose exec php php bin/console app:run-job --job-id=1 --export-dir=/path/to/exports --filename=custom.csv
```

### JQL validieren
```bash
docker-compose exec php php bin/console app:validate-jql --job-id=1
```

## API-Verwendung

### Job per API ausführen
```bash
curl -X POST http://localhost:8087/api/jobs/1/run -o export.csv
```

## Entwicklung

### Tests ausführen
```bash
docker-compose exec php php bin/phpunit
```

### Code-Qualität prüfen
```bash
# SonarQube (falls konfiguriert)
# docker-compose exec php vendor/bin/sonar-scanner
```

## Verzeichnisstruktur

```
jiraExporter/
├── config/          # Symfony-Konfiguration
├── docker/          # Docker-Konfiguration
├── migrations/      # Datenbank-Migrationen
├── public/          # Web-Root
├── src/
│   ├── Command/     # CLI-Kommandos
│   ├── Controller/  # Web-Controller
│   ├── Entity/      # Doctrine-Entities
│   ├── Form/        # Symfony-Forms
│   ├── Repository/  # Doctrine-Repositories
│   └── Service/     # Business-Logic
├── templates/       # Twig-Templates
├── tests/           # PHPUnit-Tests
└── var/             # Cache, Logs, Sessions
```

## Konfiguration

### Jira-Konfiguration
Die Jira-Konfiguration kann über:
1. Umgebungsvariablen (`.env.local`)
2. Admin-UI (geplant für zukünftige Version)

### Export-Verzeichnis
Standardmäßig werden CSV-Dateien in `./exports/` gespeichert. Dies kann über:
- `EXPORT_BASE_DIR` Umgebungsvariable
- `--export-dir` CLI-Parameter
überschrieben werden.

## Troubleshooting

### Container starten nicht
```bash
docker-compose down
docker-compose up -d --build
```

### Datenbank-Verbindung fehlschlägt
Prüfen Sie die `DATABASE_URL` in `.env.local` und stellen Sie sicher, dass die MariaDB-Container läuft:
```bash
docker-compose ps
```

### Jira-Verbindung fehlschlägt
- Prüfen Sie die Jira-Credentials in `.env.local`
- Bei selbstsignierten Zertifikaten: `JIRA_VERIFY_TLS=false`
- Testen Sie mit: `docker-compose exec php php bin/console app:validate-jql --job-id=1`

### Berechtigungen (Linux/Mac)
```bash
sudo chown -R $(id -u):$(id -g) var/
chmod -R 775 var/
```

### Logs anzeigen
```bash
docker-compose logs php
docker-compose logs nginx
docker-compose logs mariadb
```

## Security Notes

- Jira-Passwort wird bewusst als Klartext gespeichert (gemäß Anforderungen)
- Datenbankverbindung sollte verschlüsselt erfolgen
- Ändern Sie `APP_SECRET` in der Produktion
- Verwenden Sie starke Passwörter für Admin-Benutzer

## Produktions-Deployment

Für Produktions-Deployments:
1. `APP_ENV=prod` setzen
2. `APP_SECRET` durch sicheren Wert ersetzen
3. Sichere Datenbank-Credentials verwenden
4. HTTPS konfigurieren
5. Regelmäßige Backups einrichten
