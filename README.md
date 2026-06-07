# Wedding Manager

Applicazione Laravel per la gestione di eventi e matrimoni. Il progetto usa Docker Compose per eseguire backend PHP, web server, database, Adminer e toolchain frontend.

## Stack

- Backend: Laravel 11 su PHP 8.2 FPM.
- Admin: Filament 5 esposto su `/admin`.
- Frontend: React 18, Inertia, Vite 5, Tailwind CSS 4.
- Database: MySQL 8 compatibile, via immagine `ciuster/laravel_db`.
- Web server: Nginx Alpine.
- Tooling: Composer 2 nel container PHP, Node 20 Alpine per asset Vite.
- Utility: Adminer per ispezionare il database.

## Struttura

- `app/`: applicazione Laravel.
- `docker-compose.yml`: orchestrazione dei servizi.
- `docker/php/Dockerfile`: immagine PHP con estensioni Laravel, MySQL, GD, Intl, Zip e MongoDB PHP extension.
- `docker/nginx/default.conf`: virtual host Nginx che serve `app/public` e inoltra PHP a `app:9000`.
- `.env.example`: variabili usate da Docker Compose.
- `app/.env.example`: variabili applicative Laravel.
- `Makefile`: comandi operativi per installazione, amministrazione e manutenzione.

## Docker Compose

Servizi definiti:

- `app`: container PHP-FPM, buildato da `docker/php/Dockerfile`, monta `./app` in `/var/www/html`.
- `webserver`: Nginx su porta host configurabile con `WEB_PORT`, default `8080`, serve Laravel da `app/public`.
- `db`: MySQL con volume persistente `db-store`.
- `adminer`: interfaccia database su porta configurabile `ADMINER_PORT`, default consigliato `8081`.
- `node`: Node 20 per sviluppo Vite, espone `5173` e avvia `npm run dev`.

La porta HTTP pubblica si configura in `.env` con `WEB_PORT`. Se non viene valorizzata, Docker Compose usa `8080`.

## Installazione Da Zero Su Ubuntu

Prerequisiti già presenti sul server:

- Docker installato.
- Docker Compose installato come comando `docker-compose`.
- Repository già clonato o appena scaricato con `git pull`.

Entra nella directory del progetto:

```sh
cd /percorso/del/repo/wedding-manager
```

Crea e configura gli environment file:

```sh
cp .env.example .env
cp app/.env.example app/.env
```

Modifica almeno questi valori:

```dotenv
# .env
WEB_PORT=8080
ADMINER_PORT=8081
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=scegli-una-password-sicura
```

```dotenv
# app/.env
APP_NAME="Wedding Manager"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tuo-dominio.it
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Nome database, utente e password si configurano solo nel `.env` principale. `docker-compose.yml` li passa sia al container MySQL sia al container Laravel.

Esegui l'installazione completa:

```sh
make install-prod
```

Questo comando esegue:

- creazione degli `.env` mancanti senza sovrascrivere quelli esistenti;
- build delle immagini Docker;
- avvio di PHP, Nginx, MySQL e Adminer;
- `composer install --no-dev --no-interaction --optimize-autoloader`;
- `npm ci` e `npm run build`;
- generazione `APP_KEY`;
- `storage:link`;
- permessi su `storage` e `bootstrap/cache`;
- attesa del database;
- migrazioni e seed;
- cache applicative Laravel.

A fine installazione:

- Applicazione: `http://IP_SERVER:8080` oppure la porta scelta in `WEB_PORT`
- Admin Filament: `http://IP_SERVER:8080/admin` oppure la porta scelta in `WEB_PORT`
- Adminer: `http://IP_SERVER:8081`

Credenziali seed iniziali, se non cambiate nel codice:

- Email: `test@example.com`
- Password: `password`

Cambiale subito dopo il primo accesso.

## Comandi Principali

Mostra i comandi disponibili:

```sh
make help
```

Avvia ambiente server senza Vite:

```sh
make up-prod
```

Avvia ambiente sviluppo con Vite:

```sh
make up-dev
```

Ferma e rimuove i container:

```sh
make down
```

Ricostruisce e riavvia:

```sh
make build
make restart
```

Log:

```sh
make logs-watch
make log-app
make log-web
make log-db
make log-node
```

Shell nei container:

```sh
make app
make node
make web
make db
```

Artisan:

```sh
make artisan cmd='route:list'
make migrate
make migrate-fresh
make seed
make tinker
```

Frontend:

```sh
make npm-install
make npm-build
make npm-dev
```

Cache Laravel:

```sh
make optimize
make optimize-clear
make cache-clear
```

Test:

```sh
make test
```

## Aggiornamento Sul Server

Per aggiornare il server dopo modifiche al codice puoi usare un comando unico.

Se hai già fatto `git pull`:

```sh
make deploy-update
```

Se vuoi che il Makefile faccia anche il pull:

```sh
make deploy-pull
```

`make deploy-update` esegue:

- avvio dei servizi di produzione se non sono già attivi;
- `composer install --no-dev --no-interaction --optimize-autoloader`;
- `npm ci`;
- `npm run build`;
- migrazioni database con `php artisan migrate --force`;
- `php artisan storage:link`;
- permessi su `storage` e `bootstrap/cache`;
- rimozione di `app/public/hot` per evitare Vite dev server in produzione;
- pulizia e rigenerazione cache Laravel;
- restart di `app` e `webserver`.

Comandi manuali equivalenti:

```sh
git pull
make deploy-update
```

Se cambi solo file statici in `public/images`, Blade o PHP, il comando resta sicuro: ricompila anche gli asset frontend per evitare differenze tra codice sorgente e `public/build`.

## Backup E Restore Database

Crea un dump SQL nella directory del progetto:

```sh
make backup-db
```

Ripristina un dump:

```sh
make restore-db file=backup-YYYYMMDD-HHMMSS.sql
```

## Note Operative

- Il volume `db-store` contiene i dati MySQL. `make down` non lo cancella.
- `make destroy-volumes` cancella anche il database: usalo solo se vuoi perdere i dati locali.
- Il servizio `node` serve per sviluppo. In produzione è preferibile usare `make npm-build` e avviare con `make up-prod`.
- Se metti il progetto dietro reverse proxy HTTPS, imposta `APP_URL` con `https://...` e configura il proxy verso `http://127.0.0.1:8080`.
- Le credenziali database non vanno committate: `.env` e `app/.env` sono ignorati da Git.
