# Command Output Examples

Real output from a Laravel 13 application with Filament 5, Horizon, Telescope, and 96 migrations.

All examples captured with `skylence/laravel-artisan-agent-output` installed. JSON mode is the default when an AI agent is detected. Cleaned text mode activates when `ARTISAN_AGENT_OUTPUT_JSON=false` is set.

---

## `php artisan about`

### JSON mode (default)

```json
{
  "environment": {
    "application_name": "Laravel",
    "laravel_version": "13.2.0",
    "php_version": "8.4.19",
    "composer_version": "2.9.5",
    "environment": "local",
    "debug_mode": true,
    "url": "btb-filament5-demo.test",
    "maintenance_mode": false,
    "timezone": "Europe/Brussels",
    "locale": "en"
  },
  "cache": {
    "config": false,
    "events": false,
    "routes": false,
    "views": false
  },
  "drivers": {
    "broadcasting": "log",
    "cache": "redis",
    "database": "pgsql",
    "logs": ["single"],
    "mail": "log",
    "queue": "redis",
    "scout": "collection",
    "session": "redis"
  },
  "storage": {
    "/users/xve/webdev/btb-filament5-demo/public/storage": false
  },
  "shield": {
    "auth_provider": "Skylence\\Erp\\Models\\System\\Admin|CONFIGURED",
    "tenancy": "DISABLED",
    "tenant_model": null,
    "translations": "NOT PUBLISHED",
    "views": "NOT PUBLISHED",
    "version": "4.2.0"
  },
  "filament": {
    "version": "v5.4.3",
    "packages": "filament, forms, notifications, support, tables, actions, infolists, schemas, widgets",
    "views": "NOT PUBLISHED",
    "blade_icons": "NOT CACHED",
    "panel_components": "NOT CACHED"
  },
  "livewire": {
    "livewire": "v4.2.3"
  },
  "spatie_permissions": {
    "features_enabled": "Default",
    "version": "7.2.4"
  }
}
```

### Cleaned text mode

```
 Environment
 Application Name  Laravel
 Laravel Version  13.2.0
 PHP Version  8.4.19
 Composer Version  2.9.5
 Environment  local
 Debug Mode  ENABLED
 URL  btb-filament5-demo.test
 Maintenance Mode  OFF
 Timezone  Europe/Brussels
 Locale  en

 Cache
 Config  NOT CACHED
 Events  NOT CACHED
 Routes  NOT CACHED
 Views  NOT CACHED

 Drivers
 Broadcasting  log
 Cache  redis
 Database  pgsql
 Logs  stack / single
 Mail  log
 Queue  redis
 Scout  collection
 Session  redis

 Storage
 public/storage  NOT LINKED

 Shield
 Auth Provider  Skylence\Erp\Models\System\Admin|CONFIGURED
 Tenancy  DISABLED
 Tenant Model
 Translations  NOT PUBLISHED
 Version  4.2.0
 Views  NOT PUBLISHED

 Filament
 Blade Icons  NOT CACHED
 Packages  filament, forms, notifications, support, tables, actions, infolists, schemas, widgets
 Panel Components  NOT CACHED
 Version  v5.4.3
 Views  NOT PUBLISHED

 Livewire
 Livewire  v4.2.3

 Spatie Permissions
 Features Enabled  Default
 Version  7.2.4
```

---

## `php artisan migrate:status`

### JSON mode (default)

```json
{
  "total": 96,
  "ran": 96,
  "pending": 0,
  "migrations": [
    {"name": "0001_01_01_000000_create_users_table", "status": "ran", "batch": 1},
    {"name": "0001_01_01_000001_create_cache_table", "status": "ran", "batch": 1},
    {"name": "0001_01_01_000002_create_jobs_table", "status": "ran", "batch": 1},
    {"name": "2024_01_01_000000_add_erp_columns_to_users_table", "status": "ran", "batch": 1},
    {"name": "2024_01_01_000001_create_dayrate_products_table", "status": "ran", "batch": 1}
  ]
}
```

*(96 migrations total — truncated for brevity)*

### Cleaned text mode

```
 Migration name  Batch / Status
 0001_01_01_000000_create_users_table  [1] Ran
 0001_01_01_000001_create_cache_table  [1] Ran
 0001_01_01_000002_create_jobs_table  [1] Ran
 2024_01_01_000000_add_erp_columns_to_users_table  [1] Ran
 2024_01_01_000001_create_dayrate_products_table  [1] Ran
 2024_01_01_000002_create_exchange_rates_table  [1] Ran
```

*(96 migrations total — truncated for brevity)*

---

## `php artisan route:list`

### JSON mode (default)

```json
{
  "total": 406,
  "routes": [
    {
      "method": "GET|HEAD",
      "uri": "tenant/dashboard",
      "name": "tenant.dashboard",
      "action": "Closure",
      "middleware": ["web"],
      "domain": "{tenant}.btb-filament5-demo.test"
    },
    {
      "method": "GET|HEAD",
      "uri": "login",
      "name": "filament.app.auth.login",
      "action": "Filament\\Auth\\Pages\\Login",
      "middleware": [
        "Filament\\Http\\Middleware\\SetUpPanel:app",
        "Illuminate\\Cookie\\Middleware\\EncryptCookies",
        "Illuminate\\Cookie\\Middleware\\AddQueuedCookiesToResponse",
        "Illuminate\\Session\\Middleware\\StartSession",
        "Illuminate\\View\\Middleware\\ShareErrorsFromSession",
        "Filament\\Http\\Middleware\\AuthenticateSession",
        "Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken",
        "Illuminate\\Routing\\Middleware\\SubstituteBindings",
        "Filament\\Http\\Middleware\\DisableBladeIconComponents",
        "Filament\\Http\\Middleware\\DispatchServingFilamentEvent"
      ]
    },
    {
      "method": "GET|HEAD",
      "uri": "admin/reports/{type}",
      "name": "admin.reports.show",
      "action": "Closure",
      "middleware": ["web"],
      "wheres": {"type": "sales|inventory|finance"}
    },
    {
      "method": "GET|HEAD",
      "uri": "light",
      "name": "light",
      "action": "Closure",
      "middleware": ["web", "App\\Http\\Middleware\\LogRequests"],
      "without_middleware": [
        "App\\Http\\Middleware\\CheckApiToken",
        "App\\Http\\Middleware\\EnsureTeamAccess"
      ]
    }
  ]
}
```

*(406 routes total — truncated for brevity. `domain`, `wheres`, and `without_middleware` only appear when present.)*

### Cleaned text mode

```
 GET|HEAD / filament.app.pages.dashboard > Filament\Pages > Dashboard
 POST _boost/browser-logs boost.browser-logs > vendor/laravel/boost/src
 GET|HEAD admin admin.home > routes/web.php:63
 GET|HEAD admin/fields custom-fields.index > Skylence\LaravelCustomFields
 GET|HEAD admin/reports admin.reports > routes/web.php:64
 GET|HEAD admin/reports/{type} admin.reports.show > routes/web.php:65
 GET|HEAD admin/settings admin.settings.index > routes/web.php:69
 GET|HEAD admin/settings/billing admin.settings.billing > routes/web.php:71
```

*(406 routes total — truncated for brevity)*

---

## `php artisan db:show`

### JSON mode (default)

```json
{
  "platform": {
    "connection": "pgsql",
    "name": "PostgreSQL",
    "server_version": "18.0 (Laravel Herd)"
  },
  "tables": [
    {"name": "billing_recurring_contracts", "schema": "public", "size": 40960, "engine": null, "collation": null, "comment": null},
    {"name": "bunkering_vessels", "schema": "public", "size": 319488, "engine": null, "collation": null, "comment": null},
    {"name": "catalog_products", "schema": "public", "size": 262144, "engine": null, "collation": null, "comment": null},
    {"name": "telescope_entries", "schema": "public", "size": 99000320, "engine": null, "collation": null, "comment": null},
    {"name": "users", "schema": "public", "size": 24576, "engine": null, "collation": null, "comment": null}
  ]
}
```

*(182 tables total — truncated for brevity)*

### Cleaned text mode

```
 PostgreSQL  18.0 (Laravel Herd)
 Connection  pgsql
 Database  btb_filament_demo
 Host  127.0.0.1
 Port  5432
 Username  root
 URL
 Open Connections  9
 Tables  182
 Total Size  115.85 MB

 Schema / Table  Size
 public / billing_recurring_contract_product  32.00 KB
 public / billing_recurring_contracts  40.00 KB
 public / bunkering_berths  16.00 KB
```

*(182 tables total — truncated for brevity)*

---

## `php artisan db:table users`

### JSON mode (default)

```json
{
  "table": "users",
  "columns": [
    {"name": "id", "type": "int8", "nullable": false, "default": "nextval('users_id_seq'::regclass)", "auto_increment": true},
    {"name": "name", "type": "varchar", "nullable": false, "default": null, "auto_increment": false},
    {"name": "email", "type": "varchar", "nullable": false, "default": null, "auto_increment": false},
    {"name": "email_verified_at", "type": "timestamp", "nullable": true, "default": null, "auto_increment": false},
    {"name": "password", "type": "varchar", "nullable": false, "default": null, "auto_increment": false},
    {"name": "remember_token", "type": "varchar", "nullable": true, "default": null, "auto_increment": false},
    {"name": "created_at", "type": "timestamp", "nullable": true, "default": null, "auto_increment": false},
    {"name": "updated_at", "type": "timestamp", "nullable": true, "default": null, "auto_increment": false}
  ],
  "indexes": [
    {"name": "users_email_unique", "columns": ["email"], "unique": true, "primary": false},
    {"name": "users_pkey", "columns": ["id"], "unique": true, "primary": true}
  ],
  "foreign_keys": []
}
```

### Cleaned text mode

```
 public.users
 Columns  8
 Size  24.00 KB

 Column  Type
 id int8, autoincrement  nextval('users_id_seq'::regclass) bigint
 name varchar  character varying(255)
 email varchar  character varying(255)
 email_verified_at timestamp, nullable  timestamp(0) without time zone
 password varchar  character varying(255)
 remember_token varchar, nullable  character varying(100)
 created_at timestamp, nullable  timestamp(0) without time zone
 updated_at timestamp, nullable  timestamp(0) without time zone

 Index
 users_email_unique email  btree, unique
 users_pkey id  btree, primary
```

---

## `php artisan schedule:list`

### JSON mode (default)

```json
{
  "total": 14,
  "tasks": [
    {"command": "backup:run --only-db", "expression": "0 1 * * *", "description": "", "next_run": "2026-04-10 01:00:00"},
    {"command": "backup:clean", "expression": "0 2 * * *", "description": "", "next_run": "2026-04-10 02:00:00"},
    {"command": "backup:monitor", "expression": "15 3 * * *", "description": "", "next_run": "2026-04-10 03:15:00"},
    {"command": "exact:refresh-tokens", "expression": "*/5 * * * *", "description": "exact:refresh-tokens", "next_run": "2026-04-09 14:00:00"},
    {"command": "exact:monitor-refresh-tokens", "expression": "0 8 * * *", "description": "exact:monitor-refresh-tokens", "next_run": "2026-04-10 08:00:00"},
    {"command": "dayrates:exchange-rate", "expression": "0 7 * * *", "description": "dayrates:exchange-rate", "next_run": "2026-04-10 07:00:00"},
    {"command": "dayrates:crawl", "expression": "0 8 * * *", "description": "dayrates:crawl", "next_run": "2026-04-10 08:00:00"},
    {"command": "pricing:monthly-averages", "expression": "0 6 1 * *", "description": "pricing:monthly-averages", "next_run": "2026-05-01 06:00:00"},
    {"command": "star-schema:sync-dimensions", "expression": "30 2 * * *", "description": "star-schema:sync-dimensions", "next_run": "2026-04-10 02:30:00"},
    {"command": "star-schema:aggregate-daily", "expression": "0 3 * * *", "description": "star-schema:aggregate-daily", "next_run": "2026-04-10 03:00:00"},
    {"command": "star-schema:aggregate-weekly", "expression": "30 3 * * 1", "description": "star-schema:aggregate-weekly", "next_run": "2026-04-13 03:30:00"},
    {"command": "star-schema:aggregate-monthly", "expression": "0 4 1 * *", "description": "star-schema:aggregate-monthly", "next_run": "2026-05-01 04:00:00"},
    {"command": "star-schema:prune", "expression": "30 4 * * *", "description": "star-schema:prune", "next_run": "2026-04-10 04:30:00"},
    {"command": "horizon:snapshot", "expression": "*/5 * * * *", "description": "", "next_run": "2026-04-09 14:00:00"}
  ]
}
```

### Cleaned text mode

```
 0 1 * * *  php artisan backup:run --only-db  Next Due: 11 hours from now
 0 2 * * *  php artisan backup:clean  Next Due: 12 hours from now
 15 3 * * *  php artisan backup:monitor  Next Due: 13 hours from now
 */5 * * * *  exact:refresh-tokens  Next Due: 40 seconds from now
 0 8 * * *  exact:monitor-refresh-tokens  Next Due: 18 hours from now
 0 7 * * *  php artisan dayrates:import-exchange-rate  Next Due: 17 hours from now
 0 8 * * *  php artisan dayrates:crawl  Next Due: 18 hours from now
 0 6 1 * *  php artisan pricing:calculate-monthly-averages  Next Due: 3 weeks from now
 30 2 * * *  php artisan star-schema:sync-dimensions  Next Due: 12 hours from now
 0 3 * * *  php artisan star-schema:aggregate --grain=daily  Next Due: 13 hours from now
 30 3 * * 1  php artisan star-schema:aggregate --grain=weekly  Next Due: 3 days from now
 0 4 1 * *  php artisan star-schema:aggregate --grain=monthly  Next Due: 3 weeks from now
 30 4 * * *  php artisan star-schema:prune  Next Due: 14 hours from now
 */5 * * * *  php artisan horizon:snapshot  Next Due: 40 seconds from now
```

---

## `php artisan model:show Skylence\Erp\Models\System\Admin`

### JSON mode (default)

```json
{
  "class": "Skylence\\Erp\\Models\\System\\Admin",
  "database": "pgsql",
  "table": "system_admins",
  "policy": "Skylence\\Erp\\Policies\\AdminPolicy",
  "attributes": [
    {"name": "id", "type": "bigint", "increments": true, "nullable": false, "default": "nextval('system_admins_id_seq'::regclass)", "unique": true, "fillable": false, "hidden": false, "appended": null, "cast": "int"},
    {"name": "name", "type": "character varying(255)", "increments": false, "nullable": false, "default": null, "unique": false, "fillable": true, "hidden": false, "appended": null, "cast": null},
    {"name": "email", "type": "character varying(255)", "increments": false, "nullable": false, "default": null, "unique": true, "fillable": true, "hidden": false, "appended": null, "cast": null},
    {"name": "password", "type": "character varying(255)", "increments": false, "nullable": true, "default": null, "unique": false, "fillable": false, "hidden": true, "appended": null, "cast": "hashed"},
    {"name": "is_active", "type": "boolean", "increments": false, "nullable": false, "default": "true", "unique": false, "fillable": true, "hidden": false, "appended": null, "cast": "boolean"},
    {"name": "failed_login_count", "type": "integer", "increments": false, "nullable": false, "default": "0", "unique": false, "fillable": true, "hidden": false, "appended": null, "cast": "integer"},
    {"name": "locked_until", "type": "timestamp(0) without time zone", "increments": false, "nullable": true, "default": null, "unique": false, "fillable": true, "hidden": false, "appended": null, "cast": "datetime"},
    {"name": "last_failed_login_at", "type": "timestamp(0) without time zone", "increments": false, "nullable": true, "default": null, "unique": false, "fillable": true, "hidden": false, "appended": null, "cast": "datetime"}
  ],
  "relations": [
    {"name": "notifications", "type": "MorphMany", "related": "Skylence\\Erp\\Models\\System\\DatabaseNotification"},
    {"name": "socialiteUsers", "type": "HasMany", "related": "Skylence\\Erp\\Models\\System\\SocialiteUser"},
    {"name": "approverGroups", "type": "BelongsToMany", "related": "Skylence\\Erp\\Models\\Relations\\ApprovalGroup"},
    {"name": "notificationPreferences", "type": "MorphMany", "related": "Skylence\\Erp\\Models\\System\\NotificationPreference"},
    {"name": "roles", "type": "MorphToMany", "related": "Spatie\\Permission\\Models\\Role"},
    {"name": "permissions", "type": "MorphToMany", "related": "Spatie\\Permission\\Models\\Permission"}
  ],
  "events": [],
  "observers": [
    {"event": "deleting", "observer": ["Closure", "Closure"]}
  ],
  "collection": "Illuminate\\Database\\Eloquent\\Collection",
  "builder": "Illuminate\\Database\\Eloquent\\Builder",
  "resource": null
}
```

*(some attributes truncated for brevity)*

### Cleaned text mode

```
 Skylence\Erp\Models\System\Admin
 Database  pgsql
 Table  system_admins
 Policy  Skylence\Erp\Policies\AdminPolicy

 Attributes  type / cast
 id increments, unique  bigint / int
 name fillable  character varying(255)
 email unique, fillable  character varying(255)
 email_verified_at nullable  timestamp(0) without time zone / datetime
 password nullable, hidden  character varying(255) / hashed
 is_active fillable  boolean / boolean
 failed_login_count fillable  integer / integer
 locked_until nullable, fillable  timestamp(0) without time zone / datetime
 last_failed_login_at nullable, fillable  timestamp(0) without time zone / datetime
 remember_token nullable, hidden  character varying(100)
 created_at nullable  timestamp(0) without time zone / datetime
 updated_at nullable  timestamp(0) without time zone / datetime

 Relations
 notifications MorphMany  Skylence\Erp\Models\System\DatabaseNotification
 socialiteUsers HasMany  Skylence\Erp\Models\System\SocialiteUser
 approverGroups BelongsToMany  Skylence\Erp\Models\Relations\ApprovalGroup
 notificationPreferences MorphMany  Skylence\Erp\Models\System\NotificationPreference
 roles MorphToMany  Spatie\Permission\Models\Role
 permissions MorphToMany  Spatie\Permission\Models\Permission

 Events

 Observers
 deleting  Closure, Closure
```

---

## `php artisan queue:failed`

### JSON mode (default)

```json
{"total": 0, "jobs": []}
```

### Cleaned text mode

```
  INFO  No failed jobs found.
```

---

## `php artisan event:list`

### JSON mode (default)

```json
{
  "total": 139,
  "events": [
    {
      "event": "Illuminate\\Console\\Events\\CommandFinished",
      "listeners": [
        "Closure",
        "Laravel\\Telescope\\Watchers\\CommandWatcher@recordCommand",
        "Closure",
        "Closure"
      ]
    },
    {
      "event": "Illuminate\\Queue\\Events\\JobProcessing",
      "listeners": ["Closure", "Closure", "Closure", "Closure"]
    },
    {
      "event": "Illuminate\\Database\\Events\\QueryExecuted",
      "listeners": [
        "Closure",
        "Laravel\\Telescope\\Watchers\\QueryWatcher@recordQuery"
      ]
    },
    {
      "event": "Illuminate\\Auth\\Events\\Failed",
      "listeners": ["Skylence\\Erp\\Listeners\\RecordFailedLoginAttempt"]
    },
    {
      "event": "Illuminate\\Auth\\Events\\Login",
      "listeners": ["Skylence\\Erp\\Listeners\\ResetFailedLoginAttempts"]
    }
  ]
}
```

*(139 events total — truncated for brevity)*

### Cleaned text mode

```
 Filament\Events\ServingFilament
 > Closure at: /vendor/filament/filament/src/FilamentServiceProvider.php:119
 Illuminate\Auth\Access\Events\GateEvaluated
 > Laravel\Telescope\Watchers\GateWatcher@handleGateEvaluated
 Illuminate\Auth\Events\Failed
 > Skylence\Erp\Listeners\RecordFailedLoginAttempt
 Illuminate\Auth\Events\Login
 > Skylence\Erp\Listeners\ResetFailedLoginAttempts
 Illuminate\Auth\Events\Registered
 > Illuminate\Auth\Listeners\SendEmailVerificationNotification
 Illuminate\Bus\Events\BatchDispatched
 > Laravel\Telescope\Watchers\BatchWatcher@recordBatch
 Illuminate\Cache\Events\CacheHit
 > Laravel\Telescope\Watchers\CacheWatcher@recordCacheHit
```

*(139 events total — truncated for brevity)*

---

## `php artisan config:show app`

### JSON mode (default)

```json
{
  "key": "app",
  "values": {
    "name": "Laravel",
    "env": "local",
    "debug": true,
    "url": "https://btb-filament5-demo.test",
    "frontend_url": "http://localhost:3000",
    "asset_url": null,
    "timezone": "Europe/Brussels",
    "locale": "en",
    "fallback_locale": "en",
    "faker_locale": "en_US",
    "cipher": "AES-256-CBC",
    "key": "base64:...",
    "previous_keys": [],
    "maintenance": {
      "driver": "file",
      "store": "database"
    },
    "providers": [
      "Illuminate\\Auth\\AuthServiceProvider",
      "Illuminate\\Broadcasting\\BroadcastServiceProvider",
      "Illuminate\\Bus\\BusServiceProvider",
      "..."
    ],
    "aliases": {
      "App": "Illuminate\\Support\\Facades\\App",
      "Arr": "Illuminate\\Support\\Arr",
      "Artisan": "Illuminate\\Support\\Facades\\Artisan",
      "..."
    }
  }
}
```

### Cleaned text mode

```
app
 name  Laravel
 env  local
 debug  true
 url  https://btb-filament5-demo.test
 frontend_url  http://localhost:3000
 asset_url  null
 timezone  Europe/Brussels
 locale  en
 fallback_locale  en
 faker_locale  en_US
 cipher  AES-256-CBC
 key  base64:...
 previous_keys  []
 maintenance > driver  file
 maintenance > store  database
 providers > 0  Illuminate\Auth\AuthServiceProvider
 providers > 1  Illuminate\Broadcasting\BroadcastServiceProvider
```

*(truncated for brevity)*
