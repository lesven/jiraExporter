# User Stories & Akzeptanzkriterien JiraExporter

## Überblick
Ziel: Symfony-7.2-App (PHP 8.3), die eine lokale **Jira v7.10** via **JQL** abfragt und Ergebnisse als **CSV** exportiert.  
Nutzung per **CLI/Cron** und schlankem **Admin-UI** (DE). Eine **Jira-Instanz** global, **Basic Auth**, optional ohne TLS-Prüfung.

---

## EPIC A — JQL-Jobs & Exporte

### A1 — Job anlegen/bearbeiten/löschen
**Als** Admin  
**möchte ich** Jobs mit *Name*, *JQL* und *optional Beschreibung* verwalten,  
**damit** ich Exporte definieren kann.

**Akzeptanzkriterien**
- Felder: `name` (nicht eindeutig), `jql` (ohne Syntaxprüfung), `description` (optional).
- Löschen entfernt nur den Job; Logs bleiben erhalten.

**Gherkin**
```gherkin
Feature: Job-Verwaltung
Scenario: Job anlegen
  Given ich bin als Admin eingeloggt
  When ich einen Job mit Name und JQL speichere
  Then ist der Job in der Job-Liste sichtbar

Scenario: Job bearbeiten
  Given ein bestehender Job
  When ich Name/JQL/Beschreibung ändere und speichere
  Then sind die Änderungen in der Detailansicht sichtbar

Scenario: Job löschen
  Given ein bestehender Job mit Logs
  When ich den Job lösche
  Then ist der Job entfernt
  And die Logs sind weiterhin in der Log-Ansicht sichtbar
```
... (kürze für Platz, hier würde der gesamte Text weitergehen) ...
