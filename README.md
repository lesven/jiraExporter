# User Stories & Akzeptanzkriterien Projekt JiraExporter

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

---

### A2 — Job manuell ausführen (UI)
**Als** Admin  
**möchte ich** einen Job per Klick starten  
**und** das Ergebnis direkt herunterladen,  
**damit** ich spontan Exporte erzeugen kann.

**Akzeptanzkriterien**
- Button „Jetzt ausführen“ startet Export.
- Kein Spinner; nach Abschluss startet der CSV-Download.
- UI speichert keine CSV-Historie.

**Gherkin**
```gherkin
Feature: Manueller Export im UI
Scenario: CSV-Download
  Given ich öffne die Job-Detailseite
  When ich "Jetzt ausführen" klicke
  Then erhalte ich eine CSV-Datei zum Download
```

---

### A3 — CLI-Ausführung für Cron
**Als** Betreiber  
**möchte ich** Jobs per CLI starten und Pfad/Dateiname überschreiben,  
**damit** ich Exporte zeitgesteuert fahren kann.

**Akzeptanzkriterien**
- Command: `app:run-job --job-id <ID> [--export-dir <pfad>] [--filename <name.csv>]`
- Exitcodes: `0` bei Erfolg, `1` bei Fehler.
- Keine Locks; parallele Läufe erlaubt.

**Gherkin**
```gherkin
Feature: CLI-Export
Scenario: Erfolgreicher Lauf
  Given ein Job mit gültiger JQL
  When ich "app:run-job --job-id 12" ausführe
  Then endet der Prozess mit Exitcode 0
  And eine CSV wird erzeugt (ggf. im überschriebenen Pfad/Dateinamen)

Scenario: Fehlender Job
  When ich "app:run-job --job-id 9999" ausführe
  Then endet der Prozess mit Exitcode 1
```

---

### A4 — CSV-Export (Format)
**Als** Admin  
**möchte ich** CSVs im festgelegten Format erhalten,  
**damit** Folgesysteme sie robust verarbeiten.

**Akzeptanzkriterien**
- Delimiter `,`, UTF-8 **ohne BOM**, Zeilenende `
`.
- RFC4180-ähnliches Escaping (Quotes nur bei Bedarf; Verdopplung).
- Leere Werte bleiben leer (`,,`).
- Rich / Mehrfachfelder → Plaintext, Mehrfachwerte mit `|`.
- Spaltenreihenfolge wie von Jira geliefert.
- Spaltenköpfe: Standardfelder mit Anzeigenamen; **Custom Fields als IDs** (z. B. `customfield_10016`).
- Keine Metadaten-/Kommentarzeilen.
- Kollisionen überschreiben bestehende Dateien.

**Gherkin**
```gherkin
Feature: CSV-Format
Scenario: Escaping und Encoding
  Given Issues mit Kommas, Quotes und Zeilenumbrüchen
  When der Export läuft
  Then sind problematische Felder gemäß CSV-Regeln korrekt gequotet
  And die Datei ist UTF-8 ohne BOM mit 
 als Zeilenende

Scenario: Mehrfachfelder
  Given ein Issue mit Labels "a" und "b"
  When der Export läuft
  Then steht im Feld "a|b"
```

---

### A5 — Pagination (alle Seiten)
**Als** Admin  
**möchte ich** dass alle Treffer exportiert werden,  
**damit** nichts fehlt.

**Akzeptanzkriterien**
- Nutzung `/rest/api/2/search` mit `startAt`/`maxResults` bis `total`.
- Kein Streaming: Sammlung im Speicher, dann Datei schreiben.

**Gherkin**
```gherkin
Feature: Pagination
Scenario: Export über mehrere Seiten
  Given eine JQL mit > maxResults Treffern
  When der Export läuft
  Then enthält die CSV alle Treffer gemäß total
```

---

### A6 — Verhalten bei 0 Treffern / Fehlern
**Als** Admin  
**möchte ich** definierte Ergebnisse bei 0 Treffern und Fehlern,  
**damit** Cron/Jenkins sauber reagieren kann.

**Akzeptanzkriterien**
- 0 Treffer → leere CSV **mit Header**.
- Fehler (Timeout/Auth/etc.) → keine Datei, Logeintrag, Exitcode `1`.

**Gherkin**
```gherkin
Feature: Ergebnisfälle
Scenario: Nulltreffer
  Given eine JQL mit 0 Treffern
  When der Export läuft
  Then erhalte ich eine CSV mit nur der Headerzeile
  And der Logeintrag zeigt Status "ok" und issue_count 0

Scenario: Fehler
  Given falsche Jira-Credentials
  When der Export läuft
  Then wird keine CSV erzeugt
  And der CLI-Prozess endet mit Exitcode 1
  And ein Fehler wird in der DB geloggt
```

---

## EPIC B — Jira-Anbindung

### B1 — Jira-Konfiguration (global)
**Als** Admin  
**möchte ich** Jira-URL, Credentials, TLS-Prüfung und Exportpfad konfigurieren,  
**damit** die App gegen unsere Instanz läuft.

**Akzeptanzkriterien**
- Genau eine Instanz: `base_url`, `username`, `password(plain)`, `verify_tls` (bool), `export_base_dir`.
- Editierbar im UI; initial via `.env` möglich.
- **Basic Auth** über HTTPS; TLS-Prüfung abschaltbar.

**Gherkin**
```gherkin
Feature: Jira-Konfiguration
Scenario: TLS-Prüfung deaktivieren
  Given ich öffne die Konfigurationsseite
  When ich "Zertifikatsprüfung deaktivieren" aktiviere und speichere
  Then akzeptiert der Jira-Client selbstsignierte Zertifikate
```

---

### B2 — Timeouts
**Als** Betreiber  
**möchte ich** feste Timeouts,  
**damit** Hänger begrenzt werden.

**Akzeptanzkriterien**
- Connect 5s / Read 30s (nicht konfigurierbar).

**Gherkin**
```gherkin
Feature: HTTP-Timeouts
Scenario: Read-Timeout
  Given Jira antwortet sehr langsam
  When der Export läuft
  Then bricht der Request nach 30s Lesedauer mit Fehler ab
```

---

### B3 — REST-API Nutzung
**Als** Entwickler  
**möchte ich** REST v2 ohne `expand` nutzen,  
**damit** die Implementierung einfach bleibt.

**Akzeptanzkriterien**
- Endpoint `/rest/api/2/search` ohne `expand`.
- Custom Fields erscheinen als Feld-IDs in CSV-Headern.

**Gherkin**
```gherkin
Feature: REST-API-Auswahl
Scenario: Export ohne expand
  When der Export läuft
  Then werden Standardfelder per Anzeigenamen, Custom Fields per ID exportiert
```

---

## EPIC C — Admin-UI (DE)

### C1 — Auth & Sessions
**Als** Admin  
**möchte ich** mich einloggen,  
**damit** ich die App verwalten kann.

**Akzeptanzkriterien**
- Login via Username/Passwort (bcrypt).
- Standard-Session-Cookie, gültig bis Browser geschlossen wird.
- Keine Passwort-Regeln, kein Rate-Limit, kein Timeout.

**Gherkin**
```gherkin
Feature: Login
Scenario: Erfolgreicher Login
  Given ein Admin-Account existiert
  When ich die korrekten Zugangsdaten eingebe
  Then werde ich eingeloggt und sehe die Startseite

Scenario: Fehlerhafter Login
  When ich falsche Zugangsdaten eingebe
  Then erhalte ich eine generische Fehlermeldung
```

---

### C2 — Job-Liste & Details (einfach)
**Als** Admin  
**möchte ich** eine einfache Liste von Jobs,  
**damit** ich schnell arbeiten kann.

**Akzeptanzkriterien**
- Liste ohne Filter/Pagination, fixe Reihenfolge (z. B. ID).
- Detailseite mit CRUD & „Jetzt ausführen“.

**Gherkin**
```gherkin
Feature: Job-Liste
Scenario: Anzeige
  Given mehrere Jobs existieren
  When ich die Job-Liste öffne
  Then sehe ich alle Jobs in fester Reihenfolge ohne Filter/Pagination
```

---

### C3 — Logs (minimal)
**Als** Admin  
**möchte ich** die letzten Läufe sehen,  
**damit** ich Probleme erkenne.

**Akzeptanzkriterien**
- Tabelle mit Start/Ende, Jobname, Status (ok/Fehler), Issue-Anzahl.
- Keine Download-Logs, keine Charts.

**Gherkin**
```gherkin
Feature: Logs
Scenario: Anzeige eines erfolgreichen Laufs
  Given ein erfolgreicher Lauf existiert
  When ich die Logs öffne
  Then sehe ich Startzeit, Endzeit, Jobname, Status ok und Issue-Anzahl
```

---

### C4 — Health-Endpoint
**Als** Operator  
**möchte ich** einen Health-Check,  
**damit** ich die App überwachen kann.

**Akzeptanzkriterien**
- `GET /health` → `OK`, wenn App & DB erreichbar.

**Gherkin**
```gherkin
Feature: Health-Check
Scenario: Gesund
  Given App und DB sind erreichbar
  When ich /health aufrufe
  Then erhalte ich HTTP 200 mit "OK"
```

---

## EPIC D — CLI & Setup

### D1 — CLI-Kommandos
**Als** Operator  
**möchte ich** klar getrennte Commands,  
**damit** ich Exporte und Validierungen automatisiere.

**Akzeptanzkriterien**
- `app:run-job --job-id <ID> [--export-dir] [--filename]`
- `app:validate-jql --job-id <ID>` → nur Erfolg/Fehler, ohne Trefferzahl.
- Exitcodes: `0` Erfolg, `1` Fehler.

**Gherkin**
```gherkin
Feature: CLI-Validierung
Scenario: Gültige JQL
  Given ein Job mit gültiger JQL
  When ich "app:validate-jql --job-id X" ausführe
  Then endet der Prozess mit Exitcode 0

Scenario: Ungültige JQL
  Given ein Job mit ungültiger JQL
  When ich "app:validate-jql --job-id X" ausführe
  Then endet der Prozess mit Exitcode 1
```

### D2 — Installation/Initialisierung
**Als** Admin  
**möchte ich** eine einfache Erstinstallation,  
**damit** die App schnell läuft.

**Akzeptanzkriterien**
- `app:install` (interaktiv): legt DB-Schema per Symfony Migrations an und erzeugt ersten Admin (Username/Passwort).
- Keine Auto-Prüfung offener Migrationen beim Start (nur manuell).

**Gherkin**
```gherkin
Feature: Setup
Scenario: Erstinstallation
  When ich "app:install" ausführe
  Then wird das DB-Schema erzeugt
  And ich kann einen ersten Admin interaktiv anlegen
```

---

## EPIC E — REST-API (Basis)

### E1 — Jobs per API ausführen (ungesichert, intern)
**Als** System  
**möchte ich** Exporte per REST auslösen,  
**damit** externe Systeme triggern können.

**Akzeptanzkriterien**
- `POST /api/jobs/{id}/run` startet Export und liefert CSV als Download.
- Keine Auth, kein Rate-Limit; Fehler nur per HTTP-Status (200/400/500), kein JSON-Body.

**Gherkin**
```gherkin
Feature: API-Run
Scenario: Erfolg
  When ich POST /api/jobs/12/run aufrufe
  Then erhalte ich HTTP 200 mit einer CSV-Antwort

Scenario: Unbekannter Job
  When ich POST /api/jobs/9999/run aufrufe
  Then erhalte ich HTTP 400 oder 404
```

---

## EPIC F — Logging

### F1 — Laufprotokolle in DB (minimal)
**Als** Admin  
**möchte ich** Laufprotokolle,  
**damit** ich Exporte nachvollziehen kann.

**Akzeptanzkriterien**
- Log erfasst: `job_id`, `started_at`, `finished_at`, `status (ok/error)`, `issue_count`.
- Keine Dauerberechnung (wird aus Start/Ende ableitbar), kein Download-Log.

**Gherkin**
```gherkin
Feature: Lauf-Logging
Scenario: Erfolgreicher Lauf
  When ein Export erfolgreich endet
  Then wird ein Logeintrag mit Status ok und Issue-Anzahl gespeichert

Scenario: Fehlerhafter Lauf
  When ein Export fehlschlägt
  Then wird ein Logeintrag mit Status error gespeichert
```

---

## Nicht-Funktionale Anforderungen (NFRs)
- **Tech-Stack:** PHP 8.3, Symfony 7.2, MariaDB, Twig+Bootstrap UI.
- **Security:** Admin-Passwörter via **bcrypt** gehasht; Jira-Passwort **plain** in DB (bewusst einfach), DB-Transport abgesichert (Infrastruktur).
- **Performance:** Pagination über alle Treffer; Export im Speicher; HTTP-Timeouts 5s/30s.
- **Deploy:** Docker-Compose (Nginx+PHP-FPM+MariaDB), `APP_ENV` umschaltbar; Health-Endpoint.
- **Tests:** Basis-Unit-Tests (Jira-Client, CSV-Writer). UI manuell.
- **Doku:** kurzes `README` + CHANGELOG.

---

## Definition of Done (DoD)
- `app:install` erstellt Schema und ersten Admin interaktiv.  
- UI auf Port **8087**, Login & Konfiguration funktionieren.  
- Job-CRUD & „Jetzt ausführen“-Download funktionieren.  
- CLI-Export & Exitcodes wie definiert.  
- CSV-Format exakt wie spezifiziert.  
- Pagination exportiert 100 % der Treffer.  
- `/health` liefert **OK**, wenn App & DB erreichbar.  
- Logs: Start, Ende, Status, Issue-Anzahl je Lauf.
