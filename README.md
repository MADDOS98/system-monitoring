# system-monitoring

Aplicație web pentru monitorizarea în timp real a unei mașini Linux/Windows — CPU, RAM, disc, rețea, procese și loguri Apache — cu vizualizări istorice, alerte pe ferestre glisante, percentile statistice și o pagină de detaliu pentru fiecare entitate observată.

Stack: **Laravel 12** + **Livewire 4** + **Alpine.js** + **Tailwind CSS** + **Chart.js**, peste **SQLite** (WAL) cu mai multe baze de date pe domeniu funcțional.

---

## Funcționalități

### Dashboard
Pagină de tip *overview* cu șase carduri sintetice (CPU, RAM, disc, debit rețea, procese active, alerte deschise) colorate în funcție de pragul de severitate, plus trei widget-uri secundare: cele mai recente alerte, top procese după CPU și ocuparea spațiului pe disc. Toate componentele se actualizează prin polling adaptiv.

### Metrics (`/metrics?tab=…`)
Patru taburi independente — **CPU**, **RAM**, **Network**, **Disk** — fiecare cu propriul grafic istoric și statistici curent / mediu / vârf. Pe tabul Network sunt afișate simultan debitul agregat și un tabel cu adresele IP locale, sortate după numărul de conexiuni. Click pe un IP duce la pagina de detaliu a conexiunilor.

### Network connections (`/network/connections?key=…`)
Pagină dedicată evoluției în timp a numărului de conexiuni pentru o adresă IP locală sau un grup logic. Suportă **gruparea mai multor adrese sub o singură etichetă** (ex. `127.0.0.1`, `::1` și `0.0.0.0` apar consolidat ca `localhost`) prin tabela `connection_ip_groups`.

### Processes (`/processes` + `/processes/{name}`)
Lista proceselor monitorizate, cu indicator de stare (`running` / `stopped`, calculat pe baza activității în ultima minută) și pagină de detaliu pe patru taburi:
- **Info** — graficul instanțelor în timp + lista comenzilor distincte rulate
- **CPU**, **RAM**, **Disk** — serii de timp per metric

### Apache Logs (`/apache-logs`)
Tabel paginat al cererilor HTTP capturate (timestamp, IP, metodă, URL, status, user-agent) cu căutare locală, filtrare după status și widget *Top IPs* cu integrare la un serviciu de reputație de adrese (`host_reputations`).

### Alerts (`/alerts`)
- **Reguli de alertare** definite în tabela `alert_rules` (metric, operator, prag, fereastră, raport `count_matching / total`, severitate).
- **Evaluator pe ferestre glisante non-overlapping** care scanează intervalul `[last_evaluated_at, now]` și creează o alertă atunci când raportul de eșantioane care îndeplinesc condiția depășește pragul configurat.
- **Suprimare ierarhică**: regulile grupate după `(metric, operator)` sunt evaluate în ordine `critical → warning → info`; odată ce un nivel mai sever se declanșează, cele inferioare sunt suprimate pentru aceeași fereastră.
- Badge de notificare în topbar + counter în sidebar pentru alerte necitite.

### Percentiles (`/percentiles`)
Card-uri configurabile cu percentile statistice (P50, P90, P95, P99 etc.) calculate peste o fereastră de timp aleasă, pentru orice metric înregistrată.

### Settings (`/settings`)
- Configurare **retenție de date pe categorie** (`METRICS`, `PROCESSES`, `APACHE_LOGS`) prin tabela `retention_settings`. Valorile (în minute) sunt destinate să fie consultate de un job de curățare care șterge înregistrările expirate, fără ca scriitorul de date să fie implicat.
- Gestionare grupuri logice de adrese IP (vezi *Network connections*).

---

## Arhitectură

### Multi-database SQLite

Pentru a izola domeniile funcționale și a evita contenția pe scriere, aplicația folosește **patru baze de date SQLite separate** (configurate în [config/database.php](config/database.php)), toate cu `journal_mode = WAL`:

| Conexiune | Conține |
|---|---|
| `sqlite` (default) | utilizatori, sesiuni, cache, reguli & alerte, percentile, setări de retenție, grupuri IP |
| `system_metrics` | metrici CPU, RAM, rețea, conexiuni, I/O disc, ocupare disc |
| `apache_logs` | log-uri Apache + reputație IP |
| `process_metrics` | nume procese, comenzi distincte, serii de timp per proces |

Fiecare bază are propriul folder de migrații în [database/migrations/](database/migrations/).

### Realtime fără WebSocket

Realtime-ul folosește **HTTP polling adaptiv** prin endpoint-uri dedicate sub prefixul `/poll/*` (vezi [routes/web.php](routes/web.php#L70-L76)). Intervalul de poll este derivat din bucket-ul ferestrei curente — de la 1 secundă (zoom maxim) până la o zi întreagă (ferestre largi). Nu este necesar un server Reverb / Pusher / Soketi separat.

### Layere

- **Models** (Eloquent) — fiecare metric e un model pe conexiunea sa SQLite.
- **Services** — [app/Services/Monitoring/](app/Services/Monitoring/) (interogări agregate per modul, `BucketResolver` pentru downsampling), [app/Services/Alerts/](app/Services/Alerts/) (evaluator), [app/Services/Percentiles/](app/Services/Percentiles/).
- **Livewire components** — un component pentru fiecare modul UI (vezi [app/Livewire/](app/Livewire/)). Polling-ul este orchestrat client-side prin `window.createPoller(...)`.
- **Poll controllers** — [app/Http/Controllers/Poll/](app/Http/Controllers/Poll/) returnează JSON-uri compacte direct din serviciile de query.

### Date simulate

Aplicația vine cu un generator de date realiste — `php artisan data:simulate` — care produce metrici cu inerție, spike-uri ocazionale, leak-uri RAM simulate, burst-uri de I/O și log-uri Apache cu IP-uri variate. Util pentru dezvoltare, demo și testare a regulilor de alertare fără a depinde de un colector real.

---

## Cerințe

- **PHP ≥ 8.2** cu extensiile uzuale (`pdo_sqlite`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`)
- **Composer ≥ 2.x**
- **Node.js ≥ 20** + **npm**
- **SQLite ≥ 3.35** (inclus uzual cu PHP)
- Sistem de operare: Linux, macOS sau Windows

---

## Pornire

### 1. Clonare repository și intrare în director

```bash
git clone <repo-url> system-monitoring
cd system-monitoring
```

### 2. Instalare dependențe PHP

```bash
composer install
```

### 3. Configurare environment

```bash
php -r "file_exists('.env') || copy('.env.example', '.env');"
php artisan key:generate
```

Fișierul `.env` este pre-configurat pentru SQLite. Dacă vrei să schimbi căile către bazele de date sau alte setări, editează-l acum (cheile relevante: `DB_DATABASE`, `DB_METRICS_DATABASE`, `DB_APACHE_LOGS_DATABASE`, `DB_PROCESS_METRICS_DATABASE`).

### 4. Crearea bazelor de date și rularea migrațiilor

Cele patru baze SQLite trebuie migrate separat, pe conexiunile lor:

```bash
php artisan migrate --force
php artisan migrate --force --database=system_metrics --path=database/migrations/system_metrics
php artisan migrate --force --database=apache_logs    --path=database/migrations/apache_logs
php artisan migrate --force --database=process_metrics --path=database/migrations/process_metrics
```

### 5. Date inițiale (seed)

```bash
php artisan db:seed
```

Seederul populează regulile de alertă implicite, setările de retenție, un set de date istorice realiste pentru toate metricile și un utilizator demo (`test@example.com` / `password`).

### 6. Build assets frontend

```bash
npm install
npm run build
```

Pentru dezvoltare folosește `npm run dev` în loc de `npm run build`.

### 7. Pornire server

```bash
php artisan serve
```

Aplicația devine disponibilă la `http://localhost:8000`. Autentifică-te cu utilizatorul demo creat de seeder:

- **Email:** `test@example.com`
- **Parolă:** `password`

> Aplicația nu expune un flux public de înregistrare — accesul este destinat unui set închis de operatori. Pentru a adăuga utilizatori suplimentari, folosește `php artisan tinker`:
>
> ```php
> \App\Models\User::create([
>     'name'              => 'Operator',
>     'email'             => 'operator@example.com',
>     'password'          => \Hash::make('parola-aleasa'),
>     'email_verified_at' => now(),
> ]);
> ```

---

## Procese auxiliare

Aplicația rulează corect cu un singur proces `php artisan serve`, dar pentru a obține datele în timp real și alertele automate este recomandat să rulezi în paralel:

### Generator de date (pentru dezvoltare / demo)

```bash
php artisan data:simulate --loop
```

Inserează 1 punct pe secundă în fiecare tabel de metrici și produce log-uri Apache plauzibile.

### Evaluator de alerte (scheduler)

```bash
php artisan schedule:work
```

Lansează `alerts:evaluate` la fiecare 30 de minute. Pentru o evaluare imediată, fără scheduler:

```bash
php artisan alerts:evaluate
```

---

## Structură de proiect

Arborele de mai jos include doar fișierele dezvoltate în cadrul acestui proiect — fișierele rămase la valorile implicite Laravel / Breeze (ex. `artisan`, `public/index.php`, `tests/TestCase.php`, fișierele de configurare nemodificate) sunt omise pentru claritate. `vendor/`, `node_modules/`, `storage/` și fișierele de tip dată locală sunt de asemenea excluse.

```
system-monitoring/
├── .env.example                                  # variabile de mediu
├── composer.json                                 # dependențe PHP + scripturi
├── package.json                                  # dependențe JS (Tailwind, Alpine, Chart.js, Vite)
├── tailwind.config.js                            # paleta culori + custom colors
├── postcss.config.js
├── vite.config.js
├── phpunit.xml
├── README.md
├── OPTIMIZATIONS.md                              # note de optimizare aplicație
├── OPTIMIZATIONS_DB.md                           # note de optimizare bază de date
│
├── app/
│   ├── Console/Commands/
│   │   ├── SimulateData.php                      # artisan data:simulate [--loop]
│   │   └── EvaluateAlerts.php                    # artisan alerts:evaluate
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── ProfileController.php             # /profile (CRUD)
│   │   │   ├── Controller.php                    # base controller
│   │   │   ├── Auth/                             # controllers Breeze (fără register)
│   │   │   │   ├── AuthenticatedSessionController.php
│   │   │   │   ├── ConfirmablePasswordController.php
│   │   │   │   ├── EmailVerificationNotificationController.php
│   │   │   │   ├── EmailVerificationPromptController.php
│   │   │   │   ├── NewPasswordController.php
│   │   │   │   ├── PasswordController.php
│   │   │   │   ├── PasswordResetLinkController.php
│   │   │   │   └── VerifyEmailController.php
│   │   │   └── Poll/                             # endpoint-uri JSON pentru polling
│   │   │       ├── MetricsController.php         # /poll/metrics?type=cpu|ram|net|disk
│   │   │       ├── ApacheLogsController.php      # /poll/apache-logs
│   │   │       ├── ConnectionsController.php     # /poll/connection
│   │   │       └── ProcessMetricsController.php  # /poll/process-metrics
│   │   ├── Middleware/
│   │   │   └── SetUserTimezone.php               # aplică TZ-ul din cookie pe request
│   │   └── Requests/
│   │       ├── Auth/LoginRequest.php
│   │       └── ProfileUpdateRequest.php
│   │
│   ├── Livewire/                                 # componente UI, organizate pe pagina-țintă
│   │   ├── Dashboard/
│   │   │   └── DashboardOverview.php             # carduri + widgets /dashboard
│   │   ├── Metrics/                              # /metrics?tab=...
│   │   │   ├── CpuMetrics.php
│   │   │   ├── RamMetrics.php
│   │   │   ├── NetworkMetrics.php
│   │   │   ├── DiskMetrics.php
│   │   │   ├── DiskGrowthForecast.php            # prognoză umplere disc
│   │   │   ├── ConnectionIpGroupsManager.php     # CRUD pentru grupuri IP
│   │   │   ├── TabAlerts.php                     # widget alerte per tab
│   │   │   └── TabPercentiles.php                # widget percentile per tab
│   │   ├── Network/
│   │   │   └── ConnectionChart.php               # /network/connections (per IP/grup)
│   │   ├── Processes/
│   │   │   ├── ProcessesPage.php                 # /processes
│   │   │   ├── ProcessChart.php                  # /processes/{name} CPU/RAM/Disc
│   │   │   └── ProcessCommandsList.php           # tabul Info — lista comenzilor
│   │   ├── ApacheLogs/
│   │   │   ├── ApacheLogsTable.php               # tabel paginat cu căutare
│   │   │   ├── TopIpsTable.php
│   │   │   ├── StatusTable.php
│   │   │   ├── HostReputations.php               # CRUD reputație IP
│   │   │   └── PeakTrafficTimeline.php           # timeline trafic
│   │   ├── Alerts/
│   │   │   ├── AlertsList.php                    # lista alerte cu filtre
│   │   │   └── AlertRulesManager.php             # CRUD reguli
│   │   ├── Percentiles/
│   │   │   ├── PercentilesPage.php
│   │   │   └── PercentilesManager.php            # CRUD percentile
│   │   ├── Settings/
│   │   │   └── SettingsPage.php                  # retenție + TZ
│   │   ├── TimeRangePicker.php                   # shared — selector interval timp
│   │   └── PercentileCard.php                    # shared — card P50/P90/P95/P99
│   │
│   ├── Models/                                   # eloquent models pe 4 conexiuni
│   │   ├── User.php
│   │   ├── Alert.php
│   │   ├── AlertRule.php
│   │   ├── Percentile.php
│   │   ├── RetentionSetting.php
│   │   ├── ConnectionIpGroup.php
│   │   ├── CpuMetric.php
│   │   ├── RamMetric.php
│   │   ├── NetworkMetric.php
│   │   ├── ConnectionMetric.php
│   │   ├── DiskIoMetric.php
│   │   ├── DiskUsageMetric.php
│   │   ├── ApacheLog.php
│   │   ├── HostReputation.php
│   │   ├── ProcessName.php
│   │   ├── ProcessCommand.php
│   │   └── ProcessMetric.php
│   │
│   ├── Providers/
│   │   └── AppServiceProvider.php                # view composers + bindings
│   │
│   ├── Services/
│   │   ├── Alerts/
│   │   │   ├── AlertEvaluator.php                # evaluator pe ferestre glisante
│   │   │   └── MetricSampleFetcher.php           # citire eșantioane pentru reguli
│   │   ├── Monitoring/
│   │   │   ├── BucketResolver.php                # ales bucket pentru downsampling
│   │   │   ├── CpuMetricsQuery.php
│   │   │   ├── RamMetricsQuery.php
│   │   │   ├── NetworkMetricsQuery.php
│   │   │   ├── DiskMetricsQuery.php
│   │   │   ├── ApacheLogsQuery.php
│   │   │   └── ProcessDetailQuery.php
│   │   └── Percentiles/
│   │       └── PercentileCalculator.php
│   │
│   ├── Support/
│   │   └── Breadcrumb.php                        # mapare rută → breadcrumb
│   │
│   └── View/Components/                          # x-app-layout / x-guest-layout (Breeze)
│       ├── AppLayout.php
│       └── GuestLayout.php
│
├── bootstrap/
│   └── app.php                                   # înregistrare middleware (SetUserTimezone)
│
├── config/
│   └── database.php                              # 4 conexiuni SQLite (WAL) — singurul config modificat
│
├── database/
│   ├── factories/
│   │   └── UserFactory.php
│   ├── migrations/                               # bază default (conexiunea sqlite)
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   ├── 2026_05_22_000000_create_alert_rules_table.php
│   │   ├── 2026_05_22_000001_create_alerts_table.php
│   │   ├── 2026_05_26_000001_add_unique_window_to_alerts.php
│   │   ├── 2026_05_26_000002_create_percentiles_table.php
│   │   │
│   │   ├── system_metrics/                       # conexiunea system_metrics
│   │   │   ├── 2026_05_11_000000_create_ram_metrics_table.php
│   │   │   ├── 2026_05_11_000001_create_network_metrics_table.php
│   │   │   ├── 2026_05_11_000002_create_disk_io_metrics_table.php
│   │   │   ├── 2026_05_11_000003_create_disk_usage_metrics_table.php
│   │   │   ├── 2026_05_11_000004_create_cpu_metrics_table.php
│   │   │   ├── 2026_05_11_000005_create_connection_metrics_table.php
│   │   │   ├── 2026_05_11_000006_add_collected_at_indexes.php
│   │   │   ├── 2026_06_15_000000_create_retention_settings_table.php
│   │   │   └── 2026_06_15_000001_create_connection_ip_groups_table.php
│   │   │
│   │   ├── apache_logs/                          # conexiunea apache_logs
│   │   │   ├── 2026_05_04_084817_create_apache_logs_table.php
│   │   │   ├── 2026_05_11_000007_create_host_reputations_table.php
│   │   │   └── 2026_05_11_000008_add_apache_logs_indexes.php
│   │   │
│   │   └── process_metrics/                      # conexiunea process_metrics
│   │       ├── 2026_06_02_000000_create_process_names_table.php
│   │       ├── 2026_06_02_000001_create_process_commands_table.php
│   │       ├── 2026_06_02_000002_create_process_metrics_table.php
│   │       └── 2026_06_02_000003_add_process_metrics_indexes.php
│   │
│   └── seeders/
│       ├── DatabaseSeeder.php                    # entry point — user demo + delegate
│       ├── CpuMetricsSeeder.php
│       ├── RamMetricsSeeder.php
│       ├── NetworkMetricsSeeder.php
│       ├── DiskMetricsSeeder.php
│       ├── ConnectionMetricsSeeder.php
│       ├── ApacheLogsSeeder.php
│       ├── ProcessMetricsSeeder.php
│       ├── AlertRulesSeeder.php
│       ├── PercentilesSeeder.php
│       └── RetentionSettingsSeeder.php
│
├── resources/
│   ├── css/
│   │   └── app.css                               # Tailwind entry + utilities custom
│   ├── js/
│   │   ├── app.js                                # bootstrap Livewire + Alpine + Chart.js
│   │   ├── bootstrap.js
│   │   └── poller.js                             # createPoller() adaptiv (interval = bucket)
│   │
│   └── views/
│       ├── welcome.blade.php                     # landing → redirect /dashboard
│       ├── dashboard.blade.php
│       │
│       ├── layouts/
│       │   ├── app.blade.php                     # layout principal (sidebar + topbar)
│       │   ├── guest.blade.php                   # layout login/forgot
│       │   ├── sidebar.blade.php
│       │   ├── topbar.blade.php
│       │   └── navigation.blade.php
│       │
│       ├── components/                           # x-* Blade components (button, input, modal etc.)
│       │   ├── application-logo.blade.php
│       │   ├── auth-session-status.blade.php
│       │   ├── primary-button.blade.php
│       │   ├── secondary-button.blade.php
│       │   ├── danger-button.blade.php
│       │   ├── dropdown.blade.php
│       │   ├── dropdown-link.blade.php
│       │   ├── input-error.blade.php
│       │   ├── input-label.blade.php
│       │   ├── text-input.blade.php
│       │   ├── modal.blade.php
│       │   ├── nav-link.blade.php
│       │   └── responsive-nav-link.blade.php
│       │
│       ├── auth/                                 # view-uri Breeze (fără register)
│       │   ├── login.blade.php
│       │   ├── forgot-password.blade.php
│       │   ├── reset-password.blade.php
│       │   ├── verify-email.blade.php
│       │   └── confirm-password.blade.php
│       │
│       ├── profile/
│       │   ├── edit.blade.php
│       │   └── partials/
│       │       ├── update-profile-information-form.blade.php
│       │       ├── update-password-form.blade.php
│       │       └── delete-user-form.blade.php
│       │
│       ├── metrics/index.blade.php               # taburi CPU/RAM/Network/Disk
│       ├── alerts/index.blade.php
│       ├── apache_logs/index.blade.php
│       ├── network/connection-show.blade.php
│       ├── percentiles/index.blade.php
│       ├── processes/
│       │   ├── index.blade.php
│       │   └── show.blade.php
│       ├── settings/index.blade.php
│       │
│       └── livewire/                             # oglindă a structurii din app/Livewire/
│           ├── dashboard/
│           │   └── dashboard-overview.blade.php
│           ├── metrics/
│           │   ├── cpu-metrics.blade.php
│           │   ├── ram-metrics.blade.php
│           │   ├── network-metrics.blade.php
│           │   ├── disk-metrics.blade.php
│           │   ├── disk-growth-forecast.blade.php
│           │   ├── connection-ip-groups-manager.blade.php
│           │   ├── tab-alerts.blade.php
│           │   └── tab-percentiles.blade.php
│           ├── network/
│           │   └── connection-chart.blade.php
│           ├── processes/
│           │   ├── processes-page.blade.php
│           │   ├── process-chart.blade.php
│           │   └── process-commands-list.blade.php
│           ├── apache-logs/
│           │   ├── apache-logs-table.blade.php
│           │   ├── top-ips-table.blade.php
│           │   ├── status-table.blade.php
│           │   ├── host-reputations.blade.php
│           │   └── peak-traffic-timeline.blade.php
│           ├── alerts/
│           │   ├── alerts-list.blade.php
│           │   └── alert-rules-manager.blade.php
│           ├── percentiles/
│           │   ├── percentiles-page.blade.php
│           │   └── percentiles-manager.blade.php
│           ├── settings/
│           │   └── settings-page.blade.php
│           ├── time-range-picker.blade.php       # shared
│           └── percentile-card.blade.php         # shared
│
├── routes/
│   ├── web.php                                   # rute UI + grup /poll JSON
│   └── auth.php                                  # login / forgot-password / reset-password
│
└── tests/
    └── Feature/
        ├── ProfileTest.php                       # CRUD profil
        └── Auth/                                 # teste pentru login / password reset / verify
```

---

## Testare

```bash
php artisan test
```

Sau scriptul Composer:

```bash
composer test
```

---

## Comenzi utile

| Comandă | Descriere |
|---|---|
| `php artisan serve` | Pornește serverul de dezvoltare. |
| `npm run dev` | Vite în watch mode. |
| `npm run build` | Build de producție pentru assets. |
| `php artisan data:simulate --loop` | Generator continuu de date demo. |
| `php artisan alerts:evaluate` | Evaluare imediată a regulilor de alertă. |
| `php artisan schedule:work` | Pornește scheduler-ul de alerte. |
| `php artisan migrate:fresh --seed` | Reset complet al bazei *default* + seed. |

---
