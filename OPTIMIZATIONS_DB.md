# Database Optimization Audit

## Context

Audit DB-side al aplicației: dimensiuni fișiere SQLite, structura coloanelor TEXT, settings PRAGMA, indexes existente. Tinta: reducere amprentă pe disc + query-uri mai rapide + bază pentru retention policy.

## Date concrete măsurate

### File sizes (cele 3 SQLite databases)

| Fișier | Mărime | Rânduri totale | Eficient? |
|---|---:|---:|---|
| `database/database.sqlite` | **215.58 MB** | ~1 400 | **❌ Bloated 100×** |
| `database/system_metrics.sqlite` | 29.89 MB | 576 K | ✅ ~52 bytes/row |
| `database/apache_logs.sqlite` | 28.00 MB | 171 K | ✅ ~170 bytes/row |

### Per-tabel (sqlite default)

| Tabel | Rânduri |
|---|---:|
| `alerts` | 1 315 |
| `alert_rules` | 22 |
| `percentiles` | 15 |
| `migrations` | 16 |
| `users`, `sessions`, `cache`, `jobs` | <5 fiecare |

**Total payload util: ~1.4k rânduri**. Restul de **~214 MB** sunt **pagini libere nereclaimuite** din DELETE-uri vechi (smoke tests, re-seed-uri, AlertEvaluator).

### apache_logs — lungimi medii TEXT columns

| Coloană | Avg chars | Cardinalitate distinct | Eficientă? |
|---|---:|---:|---|
| `remote_host` | 13.3 | (mare) | ✅ |
| **`ident`** | **1.0** | **1** ("-") | **❌ Risipă** |
| **`user`** | **1.9** | **81** (mostly "-") | **🟡 Aproape risipă** |
| **`method`** | 3.7 | **5** distinct (GET/POST/...) | 🟡 Repetitiv |
| `uri` | 9.4 | (mare) | ✅ |
| **`protocol`** | 7.1 | **2** distinct (HTTP/1.1, HTTP/2) | 🟡 Repetitiv |
| `referer` | 13.7 | (mare, mostly "-") | 🟡 |
| `user_agent` | 29.5 | (mare) | ✅ |

### PRAGMA settings curente

| Setting | Valoare | Optimă? |
|---|---|---|
| `page_size` | 4096 | ✅ default rezonabil |
| `cache_size` | -2000 (2 MB) | 🟡 mic pentru ~30 MB DB-uri |
| `journal_mode` | WAL | ✅ |
| `synchronous` | NORMAL | ✅ |
| `temp_store` | FILE (default) | 🟡 putea fi MEMORY |
| `mmap_size` | 0 (off) | 🟡 mmap dezactivat |
| `auto_vacuum` | OFF | **❌ cauză principală a bloat-ului** |

### Indexes existente

**apache_logs**:
- `(log_time)` ✓
- `(log_time, status)` ✓
- `(log_time, remote_host)` ✓

**alerts**:
- `(read_at, window_end)` ✓
- `(alert_rule_id)` ✓
- UNIQUE `(alert_rule_id, window_start, window_end)` ✓

Comprehensive deja — nu lipsește nimic critic.

---

## Findings prioritizate

### 🔴 PRIORITATE 1 — VACUUM `database.sqlite` și activează `auto_vacuum=INCREMENTAL`

**Problema**: fișierul are 215 MB pentru 1.4k rânduri. Cauza: pagini libere acumulate din DELETE-uri (smoke tests, re-seed-uri, alerte cron). SQLite nu reclaimează automat fără `auto_vacuum`.

**Acțiune imediată — one-shot VACUUM**:
```bash
php artisan tinker --execute="DB::connection('sqlite')->statement('VACUUM');"
```
Asta va compactiza fișierul. **Câștig estimat: 215 MB → ~2-5 MB** (>97% reducere imediat).

**Acțiune permanentă — activează auto_vacuum INCREMENTAL**:

`auto_vacuum=FULL` rulează după FIECARE delete (lent). `INCREMENTAL` marchează pagini libere și le reclaimează doar la apel explicit `PRAGMA incremental_vacuum`. Mai eficient.

Două modalități:

**A. La nivel de conexiune** — modifică `config/database.php`:
```php
'sqlite' => [
    // ...
    'auto_vacuum' => 'INCREMENTAL',
],
```
Laravel nu suportă nativ această opțiune. Trebuie aplicată manual.

**B. La nivel de migrare** (recomandat):
Adaugă o migrare care setează `PRAGMA auto_vacuum=INCREMENTAL` + face VACUUM o singură dată (`auto_vacuum` trebuie setat ÎNAINTE de a popula tabelele; pentru DB existent, e necesar VACUUM după):
```php
public function up(): void {
    DB::statement('PRAGMA auto_vacuum = INCREMENTAL');
    DB::statement('VACUUM');
}
```

**Acțiune periodică** — adaugă un command care rulează săptămânal:
```php
Schedule::command('db:reclaim')->weekly();
// In command: DB::statement('PRAGMA incremental_vacuum');
```

**Verdict**: face IMEDIAT one-shot VACUUM apoi adaugă auto_vacuum INCREMENTAL la migrare. Toate 3 DB-urile beneficiază.

---

### 🟡 PRIORITATE 2 — Schema apache_logs: redenumirea / eliminarea coloanelor moarte

#### `ident` (1.0 char avg, 1 distinct value)

Coloana e mereu "-" (placeholder din formatul Apache Common Log când `ident` nu e configurat). **Risipă 100%**.

Pentru 171k rânduri × ~6 bytes (1 char + overhead SQLite) = ~1 MB irosit. La 10M rânduri (projecție), 60 MB.

**Acțiune**: DROP COLUMN `ident`. Dacă vreodată ai nevoie, deduci din format. Câștig instant 1-5% pe DB-ul `apache_logs`.

```php
Schema::table('apache_logs', function (Blueprint $table) {
    $table->dropColumn('ident');
});
```

Apoi update [app/Console/Commands/SimulateData.php](app/Console/Commands/SimulateData.php) să nu mai populeze această coloană.

#### `protocol` (2 distinct values, avg 7.1 chars)

Doar HTTP/1.1 și HTTP/2. **Storage neeficient** dar nu masiv.

Două opțiuni:
- **Schimbă tipul în TINYINT** (1 byte): 1=HTTP/1.0, 2=HTTP/1.1, 3=HTTP/2. Economisi ~6 bytes × 171k = ~1MB.
- **Lookup table** `http_protocols(id, name)` cu FK. Mai curat semantic, similar overhead.

**Verdict**: marginal. Nu prioritar.

#### `method` (5 distinct values, avg 3.7 chars)

Similar cu `protocol` — GET/POST/PUT/DELETE/HEAD. ~3 bytes × 171k = ~500KB. Neglijabil.

**Verdict**: lasă cum e.

#### `user` (avg 1.9 chars, 81 distinct values dar mostly "-")

Coloana e folosită doar pentru request-uri cu HTTP auth. **99% sunt "-"**.

**Acțiune**: convertește la nullable + setează NULL în loc de "-". Pe SQLite, NULL ocupă 1 byte fix (vs 2 bytes pentru string "-"). Câștig ~170k bytes pentru 171k rânduri. Minor.

**Mai bine**: dacă nu folosești auth-aware logs, drop column.

---

### 🟡 PRIORITATE 3 — PRAGMA tuning pentru toate cele 3 DB-uri

Modifică `config/database.php` să includă aceste setări pe fiecare conexiune SQLite:

```php
'sqlite' => [
    // ...
    'busy_timeout'   => 5000,
    'journal_mode'   => 'WAL',
    'synchronous'    => 'NORMAL',
    'cache_size'     => -65536,    // 64 MB cache (de la 2 MB)
    'mmap_size'      => 268435456, // 256 MB memory-mapped IO
    'temp_store'     => 'MEMORY',  // temp în RAM, nu pe disc
],
```

**Câștig estimat:**
- `cache_size=-65536` (64 MB): query-uri repetate hit cache → ~2-5× mai rapide pentru polls (apache-logs poll @ 1Hz)
- `mmap_size=256MB`: SQLite mapează DB în memorie virtuală, citirile sunt aproape gratis pentru pagini deja accesate
- `temp_store=MEMORY`: ORDER BY, GROUP BY și sub-query-urile temporare nu mai scriu pe disc

**Cost**: 64-256 MB RAM virtuală suplimentară per proces PHP. Pentru o app de monitoring single-server, e rezonabil.

**Atenție**: Laravel nu suportă nativ `cache_size`/`mmap_size`/`temp_store` în config — trebuie aplicate manual via PRAGMA fie în migrare, fie prin event listener pe conexiune:

```php
// În app/Providers/AppServiceProvider.php::boot()
foreach (['sqlite', 'system_metrics', 'apache_logs'] as $conn) {
    DB::connection($conn)->getPdo()->exec('PRAGMA cache_size = -65536');
    DB::connection($conn)->getPdo()->exec('PRAGMA mmap_size = 268435456');
    DB::connection($conn)->getPdo()->exec('PRAGMA temp_store = MEMORY');
}
```

Rulează la fiecare request bootstrap — overhead neglijabil (3 PRAGMA per conexiune ≈ 1ms).

---

### 🟢 PRIORITATE 4 — Partial indexes pentru query-uri filtrate

#### `alerts WHERE read_at IS NULL`

Curent: `(read_at, window_end)` index — funcționează, dar acoperă și rândurile cu read_at NOT NULL (95% din alerte după ce userul marchează ca citite).

**Optimizat**: partial index pe NULL:
```sql
CREATE INDEX alerts_active_idx ON alerts (window_end DESC)
WHERE read_at IS NULL;
```

Index mai mic (doar alerte active), query-uri pentru tab "Active" mai rapide.

**Câștig estimat**: pentru o tabelă cu 100k alerte din care 1k active, index-ul curent are 100k entries, partial-ul are 1k. **100× mai mic**, scan mai rapid.

**Migrare**:
```php
DB::statement('CREATE INDEX alerts_active_idx ON alerts (window_end DESC) WHERE read_at IS NULL');
```

#### `alert_rules WHERE is_active = 1`

Curent: `(is_active)` index. Funcționează deja pentru filtru.

**Optimizat**: partial pe regulile active (cele inactive sunt rare):
```sql
CREATE INDEX alert_rules_active_idx ON alert_rules (metric, operator)
WHERE is_active = 1;
```

Câștig: marginal (22 rânduri total acum). Devine util la 100+ reguli.

#### `percentiles WHERE is_active = 1`

Similar — marginal acum, util scaleat.

**Verdict P4**: face partial index pe `alerts` (P4a), restul deferred.

---

### 🟢 PRIORITATE 5 — Retention policy (pentru viitor)

Niciun mecanism automat de cleanup pentru:
- `apache_logs` (171k → unlimited growth, 1 row/sec în live)
- `cpu_metrics` / `ram_metrics` etc. (~140k → unlimited growth, 1 row/sec)

La 1 sample/sec timp de 1 an: 31 milioane rânduri/tabel. La ~100 bytes/row = ~3 GB. Pe sistemul tău (SQLite local), e managebil dar nu ideal.

**Propunere**: command Artisan `data:prune` care șterge rânduri mai vechi de N zile:

```php
Schedule::command('data:prune --days=30')->daily();
```

```php
// app/Console/Commands/PruneOldData.php
public function handle(): int {
    $cutoff = now()->subDays($this->option('days'))->timestamp;
    
    $deleted = 0;
    $deleted += DB::connection('apache_logs')->table('apache_logs')->where('log_time', '<', $cutoff)->delete();
    foreach (['cpu_metrics', 'ram_metrics', 'network_metrics', 'disk_io_metrics', 'disk_usage_metrics', 'connection_metrics'] as $t) {
        $deleted += DB::connection('system_metrics')->table($t)->where('collected_at', '<', $cutoff)->delete();
    }
    // Optional: read alerts
    $deleted += DB::connection('sqlite')->table('alerts')->whereNotNull('read_at')->where('read_at', '<', $cutoff)->delete();
    
    DB::connection('sqlite')->statement('PRAGMA incremental_vacuum');
    DB::connection('apache_logs')->statement('PRAGMA incremental_vacuum');
    DB::connection('system_metrics')->statement('PRAGMA incremental_vacuum');
    
    $this->info("Pruned {$deleted} rows older than {$this->option('days')} days.");
    return self::SUCCESS;
}
```

**Câștig**: DB-urile rămân la dimensiuni constante (~30-100 MB), independent de runtime.

**Atenție**: după prune, query-urile pentru perioade > N zile vor returna empty. Documentează asta în README sau setează `--days` la o valoare confortabilă (30/90/365 days).

---

### 🟢 PRIORITATE 6 — `STRICT` tables (SQLite 3.37+)

Laravel migration creează tabele standard SQLite. Începând cu SQLite 3.37 (sept 2021), poți declara `STRICT` tables:
```sql
CREATE TABLE foo (id INTEGER, name TEXT) STRICT;
```

**Câștig**:
- Type enforcement (INSERT cu tip greșit → eroare, nu silently stored as TEXT)
- ~5-10% mai puțin storage (no type affinity overhead)
- Mai rapid la index lookup (tip stabil)

**Cost**: refactor migrații, posibile incompatibilități la migrations vechi.

**Verdict**: prea invaziv pentru câștig marginal. **Defer**.

---

## Sumar prioritizat

| Prioritate | Optimizare | Efort | Câștig |
|---|---|---:|---:|
| 🔴 P1a | VACUUM database.sqlite (one-shot) | 1 min | **215 MB → ~3 MB instant** |
| 🔴 P1b | Migrare auto_vacuum=INCREMENTAL pe toate 3 DB-uri | 15 min | Previne re-bloat |
| 🔴 P1c | Command `data:reclaim` weekly | 10 min | Auto-maintenance |
| 🟡 P2a | DROP COLUMN `ident` în apache_logs | 5 min | 1-5% reducere apache_logs |
| 🟡 P2b | NULL în loc de "-" pentru `user` | 5 min | Neglijabil acum, scaleat util |
| 🟡 P3 | PRAGMA cache_size + mmap_size + temp_store | 15 min | **2-5× query speed** pe polls frecvente |
| 🟢 P4 | Partial index pe alerts(read_at IS NULL) | 5 min | 10-100× la scaling |
| 🟢 P5 | Retention command `data:prune --days=30` | 30 min | DB-uri stabile pe termen lung |
| 🟢 P6 | STRICT tables | NIMIC | Defer |

**Recomandare**: P1 (toate 3 sub-task-urile) **TONIGHT** — sunt rapide și efectul e dramatic (215 MB → 3 MB). P3 (PRAGMA) merge împreună — același fișier de config. Total ~45 min pentru câștig disproporționat de mare.

P2/P4/P5 — când nivelul de dorință urcă.

## Comenzi rapide de aplicat AZI (P1 + P3)

```powershell
# 1. One-shot VACUUM pentru toate 3 DB-uri
php artisan tinker --execute="
  DB::connection('sqlite')->statement('VACUUM');
  DB::connection('system_metrics')->statement('VACUUM');
  DB::connection('apache_logs')->statement('VACUUM');
  echo 'VACUUM done';
"

# 2. Verifică dimensiunile noi
dir database\*.sqlite | Format-Table Name, Length

# 3. (Opțional) PRAGMA tuning permanent via AppServiceProvider
# Editezi app/Providers/AppServiceProvider.php::boot() conform P3 de mai sus
```

## Verificare după implementare

1. **Re-rulează benchmark-ul DB**: dimensiuni înainte/după
2. **Test polling**: deschide `/apache-logs`, urmărește latența request-urilor în DevTools (ar trebui să scadă vizibil pe ferestre lungi cu mmap activ)
3. **Test query frecvent**: rulează `alerts:evaluate` de mai multe ori consecutive — cache_size mare ar trebui să facă a 2-a rulare semnificativ mai rapidă

## Ce NU se face (deferred)

- Migrare la PostgreSQL/MySQL — overkill pentru single-server monitoring
- Sharding apache_logs pe luni — nu justificat la scale-ul curent
- Compression la column-level (TEXT compressed) — SQLite nu suportă nativ, ar fi via extension
- Full-text search index pe uri/user_agent — interesant dar nu cerere imediată
- Read replica pe alt fișier — overkill pentru single user

---

**Bottom line**: 90% din câștig vine din **P1 (VACUUM + auto_vacuum) + P3 (PRAGMA tuning)**, total **45 minute de efort**. Reducerea dimensiunii fișierului `database.sqlite` de la 215 MB la ~3 MB e validă și măsurabilă acum, instant.
