# JiraExporter Makefile
# Vereinfacht die Verwaltung der Docker-Container und Anwendung

.PHONY: help deploy build up down clean test install migrate logs shell composer-install composer-update health

# Standard-Target
.DEFAULT_GOAL := help

# Hilfeanzeige
help: ## Zeigt diese Hilfe an
	@echo "JiraExporter - Verfügbare Make-Targets:"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# Deployment (kompletter Setup)
deploy: ## Komplettes Deployment: Build + Start + Install + Migrate + Setup
	@echo "🚀 Starte komplettes Deployment..."
	$(MAKE) build
	$(MAKE) up
	@echo "⏳ Warte 10 Sekunden auf Container-Start..."
	@timeout /t 10 /nobreak > nul 2>&1 || sleep 10
	$(MAKE) composer-install
	$(MAKE) migrate
	@echo "✅ Deployment abgeschlossen!"
	@echo "🌐 Anwendung verfügbar unter: http://localhost:8087"
	@echo "💡 Führen Sie 'make install' aus, um den ersten Admin-User zu erstellen"

# Container bauen
build: ## Baut die Docker-Container neu
	@echo "🔨 Baue Docker-Container..."
	docker-compose build --no-cache

# Container hochfahren
up: ## Startet alle Container im Hintergrund
	@echo "⬆️  Starte Container..."
	docker-compose up -d

# Container runterfahren
down: ## Stoppt alle Container
	@echo "⬇️  Stoppe Container..."
	docker-compose down

# Container runterfahren und alles löschen
clean: ## Stoppt Container und entfernt alle Daten (Volumes, Images, etc.)
	@echo "🧹 Stoppe Container und lösche alle Daten..."
	docker-compose down -v --rmi all --remove-orphans
	docker system prune -f
	@echo "✅ Alle Container-Daten gelöscht"

# Tests ausführen
test: ## Führt alle PHPUnit-Tests aus
	@echo "🧪 Führe Tests aus..."
	docker-compose exec php php bin/phpunit

# Abhängigkeiten installieren
composer-install: ## Installiert Composer-Abhängigkeiten
	@echo "📦 Installiere Composer-Abhängigkeiten..."
	docker-compose exec php composer install

# Abhängigkeiten aktualisieren
composer-update: ## Aktualisiert Composer-Abhängigkeiten
	@echo "🔄 Aktualisiere Composer-Abhängigkeiten..."
	docker-compose exec php composer update

# Datenbank-Migrationen
migrate: ## Führt Datenbank-Migrationen aus
	@echo "🗃️  Führe Datenbank-Migrationen aus..."
	docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction

# Anwendung initialisieren (Admin-User erstellen)
install: ## Initialisiert die Anwendung und erstellt Admin-User
	@echo "⚙️  Initialisiere Anwendung..."
	docker-compose exec php php bin/console app:install

# Container-Logs anzeigen
logs: ## Zeigt Logs aller Container an
	docker-compose logs -f

# Logs nur vom PHP-Container
logs-php: ## Zeigt nur PHP-Container-Logs an
	docker-compose logs -f php

# Logs nur vom Nginx-Container
logs-nginx: ## Zeigt nur Nginx-Container-Logs an
	docker-compose logs -f nginx

# Logs nur von der Datenbank
logs-db: ## Zeigt nur MariaDB-Container-Logs an
	docker-compose logs -f mariadb

# Shell im PHP-Container
shell: ## Öffnet eine Shell im PHP-Container
	docker-compose exec php sh

# Shell in der Datenbank
db-shell: ## Öffnet MySQL-Shell in der Datenbank
	docker-compose exec mariadb mysql -u jira_user -p jira_exporter

# Health-Check
health: ## Prüft den Health-Status der Anwendung
	@echo "🏥 Prüfe Application Health..."
	@curl -s http://localhost:8087/health || echo "❌ Health-Check fehlgeschlagen"

# Container-Status anzeigen
status: ## Zeigt Status aller Container an
	docker-compose ps

# Entwicklungs-Setup
dev-setup: ## Setup für Entwicklung (mit Dev-Dependencies)
	@echo "💻 Setup für Entwicklung..."
	$(MAKE) build
	$(MAKE) up
	@echo "⏳ Warte auf Container-Start..."
	@timeout /t 10 /nobreak > nul 2>&1 || sleep 10
	docker-compose exec php composer install --dev
	$(MAKE) migrate
	@echo "✅ Entwicklungsumgebung bereit!"

# Produktions-Setup
prod-setup: ## Setup für Produktion (ohne Dev-Dependencies)
	@echo "🏭 Setup für Produktion..."
	$(MAKE) build
	$(MAKE) up
	@echo "⏳ Warte auf Container-Start..."
	@timeout /t 10 /nobreak > nul 2>&1 || sleep 10
	docker-compose exec php composer install --no-dev --optimize-autoloader
	$(MAKE) migrate
	@echo "✅ Produktionsumgebung bereit!"

# Cache leeren
cache-clear: ## Leert den Symfony-Cache
	@echo "🗑️  Leere Cache..."
	docker-compose exec php php bin/console cache:clear

# Symfony-Console-Befehle
console: ## Öffnet Symfony-Console (Verwendung: make console CMD="command")
	docker-compose exec php php bin/console $(CMD)

# Job ausführen (Beispiel)
run-job: ## Führt einen Job aus (Verwendung: make run-job JOB_ID=1)
	@echo "▶️  Führe Job $(JOB_ID) aus..."
	docker-compose exec php php bin/console app:run-job --job-id=$(JOB_ID)

# JQL validieren (Beispiel)
validate-jql: ## Validiert JQL eines Jobs (Verwendung: make validate-jql JOB_ID=1)
	@echo "✅ Validiere JQL für Job $(JOB_ID)..."
	docker-compose exec php php bin/console app:validate-jql --job-id=$(JOB_ID)

# Backup der Datenbank
backup-db: ## Erstellt Backup der Datenbank
	@echo "💾 Erstelle Datenbank-Backup..."
	docker-compose exec mariadb mysqldump -u jira_user -pjira_password jira_exporter > backup_$(shell date +%Y%m%d_%H%M%S).sql
	@echo "✅ Backup erstellt: backup_$(shell date +%Y%m%d_%H%M%S).sql"

# Vollständiger Neustart
restart: ## Startet alle Container neu
	@echo "🔄 Starte Container neu..."
	$(MAKE) down
	$(MAKE) up

# Schneller Restart ohne Down/Up
reload: ## Startet nur den PHP-Container neu
	@echo "🔄 Starte PHP-Container neu..."
	docker-compose restart php
