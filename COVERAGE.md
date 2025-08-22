# Test Coverage

Dieses Dokument erklärt, wie Test Coverage Reports für das JiraExporter-Projekt generiert werden können.

## Quick Start

```bash
# 1. PCOV installieren (einmalig)
make coverage-setup

# 2. Coverage Report generieren
make coverage
```

## Verfügbare Commands

### `make coverage`
Generiert Test Coverage Reports in verschiedenen Formaten:
- **HTML Report**: `coverage-html/index.html` - Interaktiver HTML-Bericht
- **Text Report**: `coverage.txt` - Textbasierter Bericht  
- **XML Report**: `coverage.xml` - Clover XML für CI/CD

### `make coverage-setup`
Installiert PCOV (PHP Code Coverage) Extension für Coverage-Reports.

## Coverage Driver

Das Projekt unterstützt zwei Coverage Driver:

### PCOV (Empfohlen)
- ✅ **Schneller** als Xdebug
- ✅ **Speziell für Coverage** entwickelt
- ✅ **Geringere Speichernutzung**

```bash
make coverage-setup
```

### Xdebug (Alternative)
- ⚠️ **Langsamer** als PCOV
- ✅ **Zusätzliche Debug-Features**

```bash
docker-compose exec php pecl install xdebug
docker-compose exec php docker-php-ext-enable xdebug
docker-compose restart php
```

## Coverage Reports

### HTML Report (`coverage-html/index.html`)
- Interaktiver Browser-basierter Report
- Zeigt Line-by-Line Coverage
- Farbkodierte Darstellung
- Navigierbare Datei-Struktur

### Text Report (`coverage.txt`)
- Kommandozeilen-freundlich
- Übersicht über alle Klassen
- Prozentuale Coverage-Werte
- Zeigt ungetestete Dateien

### XML Report (`coverage.xml`)
- Clover XML Format
- Für CI/CD Integration
- Maschinell lesbar

## Coverage Metriken

Die Reports zeigen folgende Metriken:
- **Lines**: Prozent der ausgeführten Code-Zeilen
- **Functions/Methods**: Prozent der aufgerufenen Funktionen
- **Classes**: Prozent der verwendeten Klassen
- **Branches**: Prozent der durchlaufenen Code-Pfade

## Aktuelle Testabdeckung

Das Projekt hat **60 Tests** mit **248 Assertions**:

### Getestete Komponenten
- ✅ **Entities**: Job, JobLog, User, JiraConfig
- ✅ **Services**: JiraClient, CsvExporter  
- ✅ **Commands**: ValidateJql, RunJob
- ✅ **Repository**: JobLogRepository
- ✅ **Integration Tests**: End-to-End Workflows

### Test-Kategorien
- **Unit Tests**: 28 Tests
- **Integration Tests**: 32 Tests

## Troubleshooting

### "No code coverage driver available"
```bash
# PCOV installieren
make coverage-setup

# Oder manuell:
docker-compose exec php pecl install pcov
docker-compose exec php docker-php-ext-enable pcov
docker-compose restart php
```

### Hoher Speicherverbrauch
```bash
# Speicher-Limit erhöhen
docker-compose exec php php -d memory_limit=1G bin/phpunit --coverage-html coverage-html
```

### Coverage-Dateien löschen
```bash
rm -rf coverage-html coverage.txt coverage.xml
```

## CI/CD Integration

Für kontinuierliche Integration kann der XML-Report verwendet werden:

```yaml
# GitHub Actions Beispiel
- name: Generate Coverage
  run: make coverage

- name: Upload Coverage  
  uses: codecov/codecov-action@v3
  with:
    file: coverage.xml
```

## Konfiguration

Die Coverage-Konfiguration befindet sich in `phpunit.xml.dist`:

```xml
<coverage>
    <report>
        <html outputDirectory="coverage-html" lowUpperBound="50" highLowerBound="80"/>
        <text outputFile="coverage.txt" showUncoveredFiles="true"/>
        <clover outputFile="coverage.xml"/>
    </report>
</coverage>
```

## Ausschlüsse

Folgende Dateien sind von Coverage ausgeschlossen:
- Tests (`tests/` Verzeichnis)
- Vendor-Dateien (`vendor/`)
- Generated Code

## Ziele

- 🎯 **Minimum**: 80% Line Coverage
- 🎯 **Ideal**: 90%+ Line Coverage
- 🎯 **Kritische Komponenten**: 100% Coverage