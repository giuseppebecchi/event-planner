SHELL := /bin/sh
DOCKER_COMPOSE ?= docker-compose
APP_SERVICE := app
NODE_SERVICE := node
WEB_SERVICE := webserver
DB_SERVICE := db
ADMINER_SERVICE := adminer
ARTISAN := $(DOCKER_COMPOSE) exec $(APP_SERVICE) php artisan
COMPOSER := $(DOCKER_COMPOSE) exec $(APP_SERVICE) composer
NODE_RUN := $(DOCKER_COMPOSE) run --rm --entrypoint sh $(NODE_SERVICE) -c

.PHONY: help env build up up-prod up-dev stop down restart destroy destroy-volumes ps install install-prod install-dev deploy-update deploy-pull composer-install composer-install-prod npm-install npm-build npm-dev publish-assets migrate migrate-fresh seed storage-link permissions key-generate optimize optimize-clear cache-clear test tinker artisan app node web db logs logs-watch log-app log-web log-db log-node wait-db backup-db restore-db

help:
	@echo "Comandi disponibili:"
	@echo "  make install-prod        Prima installazione server: env, build, dipendenze, asset, migrazioni, cache"
	@echo "  make install-dev         Prima installazione sviluppo con Vite dev server"
	@echo "  make up-prod             Avvia app, nginx, mysql, adminer"
	@echo "  make up-dev              Avvia anche il servizio node/Vite"
	@echo "  make down                Ferma e rimuove i container"
	@echo "  make logs-watch          Segue i log di tutti i servizi"
	@echo "  make app                 Shell nel container PHP"
	@echo "  make artisan cmd='...'   Esegue artisan, es: make artisan cmd='route:list'"
	@echo "  make migrate             Esegue le migrazioni"
	@echo "  make npm-build           Compila gli asset frontend"
	@echo "  make publish-assets      Pubblica asset Filament e Livewire in public/"
	@echo "  make deploy-update       Aggiorna dipendenze, asset, migrazioni, cache e riavvia dopo git pull"
	@echo "  make deploy-pull         Esegue git pull e poi make deploy-update"

# Crea i file .env solo se non esistono già.
env:
	@test -f .env || cp .env.example .env
	@test -f app/.env || cp app/.env.example app/.env

build:
	$(DOCKER_COMPOSE) build

up:
	$(DOCKER_COMPOSE) up -d

up-prod:
	$(DOCKER_COMPOSE) up -d $(APP_SERVICE) $(WEB_SERVICE) $(DB_SERVICE) $(ADMINER_SERVICE)

up-dev:
	$(DOCKER_COMPOSE) up -d

stop:
	$(DOCKER_COMPOSE) stop

down:
	$(DOCKER_COMPOSE) down --remove-orphans

restart: down up-prod

destroy:
	$(DOCKER_COMPOSE) down --rmi all --volumes --remove-orphans

destroy-volumes:
	$(DOCKER_COMPOSE) down --volumes --remove-orphans

ps:
	$(DOCKER_COMPOSE) ps

install: install-prod

install-prod: env build up-prod composer-install-prod npm-install npm-build publish-assets key-generate storage-link permissions wait-db migrate seed optimize

install-dev: env build up-dev composer-install npm-install key-generate storage-link permissions wait-db migrate seed optimize-clear

deploy-update: env up-prod composer-install-prod npm-install npm-build publish-assets migrate storage-link permissions optimize-clear optimize
	rm -f app/public/hot
	$(DOCKER_COMPOSE) restart $(APP_SERVICE) $(WEB_SERVICE)

deploy-pull:
	git pull
	$(MAKE) deploy-update

composer-install:
	$(COMPOSER) install --no-interaction --optimize-autoloader

composer-install-prod:
	$(COMPOSER) install --no-dev --no-interaction --optimize-autoloader

npm-install:
	$(NODE_RUN) "npm ci"

npm-build:
	$(NODE_RUN) "npm run build"

publish-assets:
	$(ARTISAN) filament:assets
	$(ARTISAN) livewire:publish --assets

npm-dev:
	$(DOCKER_COMPOSE) up $(NODE_SERVICE)

migrate:
	$(ARTISAN) migrate --force

migrate-fresh:
	$(ARTISAN) migrate:fresh --seed --force

seed:
	$(ARTISAN) db:seed --force

storage-link:
	$(ARTISAN) storage:link

permissions:
	$(DOCKER_COMPOSE) exec $(APP_SERVICE) chmod -R a+rwX storage bootstrap/cache

key-generate:
	$(ARTISAN) key:generate --force

optimize:
	$(COMPOSER) dump-autoload -o
	$(ARTISAN) optimize
	$(ARTISAN) event:cache
	$(ARTISAN) view:cache

optimize-clear:
	$(ARTISAN) optimize:clear
	$(ARTISAN) event:clear

cache-clear: optimize-clear
	$(COMPOSER) clear-cache

test:
	$(ARTISAN) test

tinker:
	$(ARTISAN) tinker

artisan:
	$(ARTISAN) $(cmd)

app:
	$(DOCKER_COMPOSE) exec $(APP_SERVICE) sh

node:
	$(DOCKER_COMPOSE) run --rm --entrypoint sh $(NODE_SERVICE)

web:
	$(DOCKER_COMPOSE) exec $(WEB_SERVICE) sh

db:
	$(DOCKER_COMPOSE) exec $(DB_SERVICE) sh

logs:
	$(DOCKER_COMPOSE) logs

logs-watch:
	$(DOCKER_COMPOSE) logs --follow

log-app:
	$(DOCKER_COMPOSE) logs $(APP_SERVICE)

log-web:
	$(DOCKER_COMPOSE) logs $(WEB_SERVICE)

log-db:
	$(DOCKER_COMPOSE) logs $(DB_SERVICE)

log-node:
	$(DOCKER_COMPOSE) logs $(NODE_SERVICE)

wait-db:
	$(DOCKER_COMPOSE) exec $(APP_SERVICE) php -r '$$deadline=time()+60; do { try { new PDO("mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); echo "Database pronto\n"; exit(0); } catch (Throwable $$e) { sleep(2); } } while (time()<$$deadline); fwrite(STDERR, "Database non raggiungibile\n"); exit(1);'

backup-db:
	$(DOCKER_COMPOSE) exec $(DB_SERVICE) sh -c 'mysqldump -u"$$MYSQL_USER" -p"$$MYSQL_PASSWORD" "$$MYSQL_DATABASE"' > backup-$$(date +%Y%m%d-%H%M%S).sql

restore-db:
	@test -n "$(file)" || (echo "Uso: make restore-db file=backup.sql" && exit 1)
	cat $(file) | $(DOCKER_COMPOSE) exec -T $(DB_SERVICE) sh -c 'mysql -u"$$MYSQL_USER" -p"$$MYSQL_PASSWORD" "$$MYSQL_DATABASE"'
