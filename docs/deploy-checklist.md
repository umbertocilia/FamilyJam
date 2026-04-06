# Deploy Checklist

## Pre-Deploy

- PHP 8.3 disponibile
- MySQL 8 disponibile
- `mod_rewrite` attivo
- estensioni PHP: `intl`, `mbstring`, `mysqli`, `fileinfo`
- accesso Composer oppure build locale con `vendor/`
- accesso cron o pannello cron del provider

## Release Package

- codice applicativo aggiornato
- cartella `vendor/` presente
- file `.env` creato dal template `.env.example`
- `CI_ENVIRONMENT = production`
- `app.baseURL` corretto con trailing slash

## Database

- database creato con `utf8mb4` / `utf8mb4_unicode_ci`
- utente DB con permessi su schema applicativo
- eseguito `php spark migrate`
- eseguito `php spark db:seed DatabaseSeeder`

## Apache / Webroot

- document root su `public/`
- `AllowOverride All` se gestito dal provider
- [public/.htaccess](c:/Users/Umberto/Documents/Code/FamilyJam/public/.htaccess) presente e non sovrascritto
- se il provider non supporta document root custom, usare la strategia fallback descritta nel [README.md](c:/Users/Umberto/Documents/Code/FamilyJam/README.md)

## Writable

- `writable/cache` scrivibile
- `writable/logs` scrivibile
- `writable/session` scrivibile
- `writable/uploads` scrivibile
- [writable/.htaccess](c:/Users/Umberto/Documents/Code/FamilyJam/writable/.htaccess) presente

## Cron

- `recurring:expenses-run` ogni 15 minuti
- `chores:occurrences-run` ogni 15 minuti
- `chores:reminders-run` ogni ora
- output cron rediretto in `writable/logs`

## Smoke Test

- home page risponde
- login e logout funzionano
- creazione household funziona
- creazione expense funziona
- upload ricevuta funziona
- notification dropdown si apre
- dashboard household carica dati

## Sicurezza

- HTTPS attivo
- `cookie.secure = true`
- `app.forceGlobalSecureRequests = true`
- directory `writable/` non pubblica
- nessun file `.env` esposto via web
- backup di DB e upload pianificati

## Post-Deploy

- verifica log in `writable/logs`
- verifica cron dopo la prima esecuzione
- verifica spazio disco per upload e log
- salva dump iniziale del database dopo bootstrap
