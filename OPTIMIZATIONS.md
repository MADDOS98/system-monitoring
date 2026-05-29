# Memory Optimization Audit

## Context

Audit RAM end-to-end al aplicației: benchmark pe scenarii reale + identificare hotspots + propuneri concrete de optimizare. Tinta: reducere ferma a peak memory pentru request-uri HTTP frecvente (poll-uri Livewire + endpoint-uri JSON) și pentru `alerts:evaluate` cron.

## Date de bază în DB la momentul benchmark-ului

| Tabel | Rânduri |
|---|---|
| `alerts` | 1226 (247 active) |
| `apache_logs` | 171 417 |
| `cpu_metrics` | 105 702 |
| `system_metrics` (toate) | ~500k cumulat |

## Rezultate benchmark (peak memory delta + execuție)

| # | Scenariu | Peak avg | Peak max | Time avg |
|---|---|---:|---:|---:|
| 1 | **AlertsList::render (fără filtru)** | **2.67 MB** | **6.00 MB** | 217 ms |
| 2 | AlertsList::render (level=critical filtru) | 0.67 MB | 2.00 MB | 136 ms |
| 3 | PercentilesPage (14 carduri, toate accordion-uri open) | <1 MB | <1 MB | 93 ms |
| 4 | PercentileCalculator (cpu P95, 60min window) | <1 MB | <1 MB | 0.2 ms |
| 5 | PercentileCard render (single card) | <1 MB | <1 MB | 4 ms |
| 6 | topIps (5 min window) | <1 MB | <1 MB | 13 ms |
| 7 | **topIps (24h window — multe IP-uri distinct)** | **1.33 MB** | **4.00 MB** | **465 ms** |
| 8 | AlertEvaluator::run (full pass) | <1 MB | <1 MB | 156 ms |
| 9 | ApacheLogsQuery::paginate (20 logs) | <1 MB | <1 MB | 0.4 ms |
| 10 | NetworkMetricsQuery::snapshot (24h) | <1 MB | <1 MB | 44 ms |
| 11 | CpuMetricsQuery::snapshot (24h) | <1 MB | <1 MB | 83 ms |
| 12 | Ram+Disk metrics combined (24h) | <1 MB | <1 MB | 78 ms |
| 13 | TabAlerts render (cpu tab) | 0.67 MB | 2.00 MB | 49 ms |
| 14 | TabPercentiles render (cpu tab) | <1 MB | <1 MB | 18 ms |
| 15 | /poll/apache-logs snapshot simulation | <1 MB | <1 MB | 14 ms |

**Peak total proces:** 36 MB. Pentru o aplicație Laravel + Livewire + 500k+ rânduri în DB, e excelent. Hotspots-urile reale sunt limitate la 2-3 puncte și sunt fixabile prin paginare/limitare.

## Findings ranked după impact

---

### 🔴 PRIORITATE 1 — `AlertsList::render` încarcă TOATE alertele filtrate fără paginare

**Locație:** [app/Livewire/AlertsList.php:120-124](app/Livewire/AlertsList.php#L120-L124)

```php
$alerts = (clone $base)
    ->with('rule')
    ->when($this->levelFilter !== 'all', fn ($q) => $q->where('level', $this->levelFilter))
    ->orderByDesc('id')
    ->get();   // ← unbounded
```

**Impact măsurat:**
- 247 alerte active fără filtru → 6 MB peak / 217ms render
- Extrapolat la 5000 alerte active → ~120 MB peak / ~5s render
- Combinat cu `wire:poll.5s` → re-randare constantă a întregului set

**De ce e cel mai mare risc:** alertele se acumulează în timp (cron-ul rulează la 30 min, fiecare run poate genera zeci). După câteva luni, tabela poate avea 10k-100k rânduri. Pagina `/alerts` devine inutilizabilă (browser hang + server OOM).

**Soluție propusă:**

1. Adaugă `use Livewire\WithPagination;` în clasă + `use WithPagination;` în clasă
2. În `render()`, înlocuiește `->get()` cu `->paginate(50)`:
   ```php
   $alerts = (clone $base)->with('rule')->...->paginate(50);
   ```
3. În blade `alerts-list.blade.php`, adaugă footer cu `← Newer / Older →` (refolosește pattern-ul din `apache-logs-table.blade.php:175-206`)
4. Resetează pagina la `setTab`, `setLevelFilter`, `clearFilters` (apel `$this->resetPage()`)

**Câștig estimat:** peak per render scade de la O(N alerte) la O(50) — fix, indiferent de cât de mare devine tabela. Reducere ~95% la 5000 alerte.

**Efort:** ~30 min (pattern deja folosit în ApacheLogsTable și acum în TopIpsTable)

---

### 🟡 PRIORITATE 2 — `ApacheLogsQuery::topIps` fetch-uiește TOATE IP-urile distinct apoi paginează în PHP

**Locație:** [app/Services/Monitoring/ApacheLogsQuery.php:82-152](app/Services/Monitoring/ApacheLogsQuery.php#L82-L152)

```php
$allRows = DB::connection(self::CONNECTION)->table('apache_logs')
    ->...->groupBy('remote_host')->orderByDesc('reqs')
    ->get();  // ← TOATE IP-urile distincte cu agregatele
->map(...)->filter(...);  // ← procesate toate în memorie
```

**Impact măsurat:**
- 24h window cu N IP-uri distinct → 4 MB peak / 465ms (cel mai LENT scenariu)
- Pe ferestre live (5min): 13ms, neproblematic
- **Problema reală e timpul** (465ms), care blochează thread-ul la fiecare /poll/apache-logs pentru window-uri lungi

**Cauza:** la refactor-ul pentru paginare am scos `LIMIT 15` la SQL ca să paginăm corect. Asta înseamnă că PENTRU PAGINA 1 (cazul tipic) încărcăm toate IP-urile doar ca să le aruncăm 99%.

**Soluție propusă:**

**Varianta A (recomandată)** — SQL-level pagination cu COUNT separat:

```php
// Total (pentru paginator)
$totalRows = DB::connection(self::CONNECTION)->table('apache_logs')
    ->where(...)->distinct('remote_host')->count('remote_host');

// Pagina curentă (doar perPage rânduri)
$rows = DB::connection(self::CONNECTION)->table('apache_logs')
    ->where(...)
    ->selectRaw('remote_host as ip, COUNT(*) as reqs, ...')
    ->groupBy('remote_host')
    ->orderByDesc('reqs')
    ->limit($perPage)
    ->offset(($page - 1) * $perPage)
    ->get();
```

**Subtilitate**: filtrul de tab (`Whitelisted` / `Suspicious`) depinde de JOIN cu `host_reputations` (alt conexiune logică). Pentru tab=`All` (cazul tipic), nu e necesar — sărim peste el. Pentru `Whitelisted/Suspicious`, păstrăm logica veche (fetch all + filter PHP) ca fallback. În practică, majoritar utilizatorii sunt pe tab `All`.

**Varianta B** — partial denormalization: dacă tab-urile reputation sunt folosite des, considerăm denormalizare prin sincronizare periodică `host_reputations` în DB-ul `apache_logs` (cross-connection FOREIGN KEY nu există în SQLite).

**Câștig estimat:** pentru tab=All (90%+ din cazuri): 4MB → <1MB peak, 465ms → <50ms. Pentru tab=Whitelisted/Suspicious: rămâne ca acum.

**Efort:** ~45 min

---

### 🟡 PRIORITATE 3 — `TabAlerts::render` și `AlertsList::render` fac același `->get()` nelimitat

**Locație:** [app/Livewire/TabAlerts.php:38-44](app/Livewire/TabAlerts.php#L38-L44)

```php
$alerts = empty($metrics) ? collect() : Alert::with('rule')
    ->whereNull('read_at')
    ->whereIn('metric', $metrics)
    ->orderByDesc('id')
    ->get();  // ← unbounded
```

**Impact măsurat:** 0.67 MB peak / 49ms. Mic acum (~113 cpu alerts), dar scalează liniar.

**Soluție propusă:** adaugă `->limit(20)` (sau alt cap configurabil) — TabAlerts e UI compact pe pagina metrics, nu paginează. Cap-ul e suficient.

```php
->orderByDesc('id')->limit(20)->get();
```

În blade, adaugă text "(showing latest 20)" dacă există mai multe alerte decât limita.

**Câștig:** O(rezultate)→O(20) — neglijabil acum dar previne creșterea.

**Efort:** ~5 min

---

### 🟢 PRIORITATE 4 — `HostReputation` se fetch-uiește TOATĂ la fiecare `topIps()`, chiar și pe tab=All

**Locație:** [app/Services/Monitoring/ApacheLogsQuery.php:90-93](app/Services/Monitoring/ApacheLogsQuery.php#L90-L93)

```php
$reputationsByIp = DB::connection(self::CONNECTION)
    ->table('host_reputations')
    ->get(['ip', 'host', 'status'])
    ->keyBy('ip');
```

**Impact:** pentru tab=`All`, reputation lookup-ul e folosit doar pentru afișarea badge-ului per IP — câteva înregistrări per pagină. Pentru tab=`Whitelisted/Suspicious`, e necesar pentru filtrare.

**În practică**, host_reputations are <100 rânduri tipic. Memoria e neglijabilă. Dar pe poll la 1s în live mode, e CPU/alloc redundant.

**Soluție propusă:** cache la nivel de request (request scope) sau memoizare statică în clasă:

```php
private ?\Illuminate\Support\Collection $repCache = null;

private function reputations(): \Illuminate\Support\Collection
{
    return $this->repCache ??= DB::connection(self::CONNECTION)
        ->table('host_reputations')->get(['ip', 'host', 'status'])->keyBy('ip');
}
```

Apoi în `topIps()`: `$reputationsByIp = $this->reputations();`

Hmm dar `ApacheLogsQuery` e injectat ca singleton într-un request — deci memoize-ul ține pentru întregul request. ✓

**Câștig:** un query SQL eliminat per call (apoi de 4-5 ori in cazul unui poll complet).

**Efort:** ~5 min

---

### 🟢 PRIORITATE 5 — `Alert::with('rule')` eager load × N alerte

**Locație:** [app/Livewire/AlertsList.php:121](app/Livewire/AlertsList.php#L121), [app/Livewire/TabAlerts.php:38](app/Livewire/TabAlerts.php#L38)

**Observație:** `with('rule')` face 2 query-uri total (1 pentru alerts, 1 pentru rules INNER JOIN-uite). Bine. Dar fiecare alert primește un *obiect Eloquent* AlertRule atașat, care e folosit doar pentru `$alert->rule->name` în blade.

**Câștig potențial:** dacă N e mare (după ce vine paginarea, N ≤ 50, deci nu mai e o problemă), s-ar putea în-line-a `rule.name` în alerts table ca snapshot la inserare:

```sql
ALTER TABLE alerts ADD COLUMN rule_name VARCHAR(100);
```

Apoi în AlertEvaluator la INSERT salvăm și `'rule_name' => $rule->name`. În UI nu mai facem `with('rule')`.

**Câștig:** ~30% mai puțin memory pe randare blade-ului (un obiect mai puțin per rând).

**Cost:** schemă duplică, dar consistent cu pattern-ul existent (snapshot al lui level, metric, threshold, etc.).

**Verdict:** **NU MERITĂ acum**. Devine merituos doar dacă, după Priority 1 (paginate), încă vedem >5MB pe randări tipice.

---

### 🟢 PRIORITATE 6 — Apache logs in-memory aggregation pentru status mix

**Locație:** [app/Services/Monitoring/ApacheLogsQuery.php:108-114](app/Services/Monitoring/ApacheLogsQuery.php#L108-L114)

Pentru fiecare IP în top, calculează **percentage** la fiecare status class:
```php
's2xx' => (int) round($row->s2xx / $total * 100),
```

E corect SQL-side (SUM CASE WHEN), dar în PHP împărțirea procentajului are mici allocări care se acumulează. Pentru 1000 IPs distincte e ~5KB allocs irelevante.

**Verdict:** zero acțiune necesară.

---

## Optimizări care DEJA SUNT făcute (audit pozitiv)

| Locație | Optimizare aplicată |
|---|---|
| `RamMetricsQuery`, `CpuMetricsQuery`, `NetworkMetricsQuery`, `DiskMetricsQuery` | SQL-side bucket aggregation (CTE + AVG/SUM), nu mai încărcă rânduri brute |
| `ApacheLogsQuery::paginate`, `newSince` | `DB::table()` în loc de Eloquent — fără modele instantiate |
| `AlertEvaluator` | Group-uri evaluate în pipeline, sample-uri citite per fereastră (nu tot setul) |
| `PercentileCalculator` | 2 query-uri SQL agregate, nu încarcă samples-urile în PHP |
| Apache logs indexes | `(log_time, status)`, `(log_time, remote_host)` deja prezente |
| `alerts` UNIQUE constraint | `(alert_rule_id, window_start, window_end)` previne duplicate prin INSERT |

## Sumar prioritizat

| Prioritate | Optimizare | Efort | Câștig peak | Câștig latență |
|---|---|---:|---:|---:|
| **🔴 P1** | Paginate `AlertsList` (50/page) | 30 min | -90% la N>500 alerte | -50% la randare |
| **🟡 P2** | SQL-level pagination la `topIps` (tab=All) | 45 min | -75% pe 24h | -90% latență |
| **🟡 P3** | Cap `TabAlerts` la 20 alerte | 5 min | -O(N→20) | neglijabil |
| **🟢 P4** | Memoizare `HostReputation` per request | 5 min | -1 query/call | -5-10ms |
| **🟢 P5** | Denormalizare `alerts.rule_name` | 1h | -30% pe blade | dec |
| **🟢 P6** | Status mix percentage allocs | NIMIC | NIMIC | NIMIC |

**Recomandare execuție:** P1 + P2 + P3 + P4 într-o singură sesiune (~85 min total). Asta face peak per request **stabil sub 2 MB** indiferent de creșterea DB-ului. Total proces (sub orice load) ar trebui să rămână sub 50 MB.

## Ce NU se face acum (deferred / over-engineering)

- **Cache Redis pentru top IPs / snapshots** — proiectul e single-tenant, traffic mic. Cache-ul ar adăuga complexitate fără câștig real
- **Query result caching** — datele se schimbă la fiecare poll, cache hit rate ar fi <10%
- **Rollup tables pentru metrics** — analizat anterior, scaling-ul nu justifică încă (24h-7zile e fezabil cu SQL aggregation existentă)
- **Migrarea la o DB mai mare (MySQL/PG)** — SQLite WAL gestionează bine workload-ul curent
