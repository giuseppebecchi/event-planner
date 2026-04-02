up:
	docker-compose up -d
build:
	docker-compose -f docker-compose.yml build
build-app:
	docker-compose -f docker-compose.yml build app
build-web:
	docker-compose -f docker-compose.yml build web

install-recommend-packages:
	docker-compose exec app composer require doctrine/dbal
	docker-compose exec app composer require --dev ucan-lab/laravel-dacapo
	docker-compose exec app composer require --dev barryvdh/laravel-ide-helper
	docker-compose exec app composer require --dev beyondcode/laravel-dump-server
	docker-compose exec app composer require --dev barryvdh/laravel-debugbar
	docker-compose exec app composer require --dev roave/security-advisories:dev-master
	docker-compose exec app php artisan vendor:publish --provider="BeyondCode\DumpServer\DumpServerServiceProvider"
	docker-compose exec app php artisan vendor:publish --provider="Barryvdh\Debugbar\ServiceProvider"

restart-node:
	docker-compose restart node

restore-mongodb-initial-dump:
	docker-compose exec mongo mongorestore --drop --db tickets /mongo_dump/tickets

init:
	docker-compose up -d
	docker-compose exec app composer install
	docker-compose exec app cp .env.example .env
	docker-compose exec app php artisan key:generate
	docker-compose exec app php artisan storage:link
	docker-compose exec app chmod -R 777 storage bootstrap/cache
	docker-compose exec app php artisan storage:link
	docker-compose exec app php artisan migrate
	docker-compose exec app php artisan admin:install

composer_install:
	docker-compose exec app composer install
stop:
	docker-compose stop
down:
	docker-compose down --remove-orphans
restart:
	@make down
	@make up
destroy:
	docker-compose down --rmi all --volumes --remove-orphans
destroy-volumes:
	docker-compose down --volumes --remove-orphans
ps:
	docker-compose ps
logs:
	docker-compose logs
logs-watch:
	docker-compose logs --follow
log-web:
	docker-compose logs web
log-web-watch:
	docker-compose logs --follow web
log-app:
	docker-compose logs app
log-app-watch:
	docker-compose logs --follow app
log-db:
	docker-compose logs db
log-db-watch:
	docker-compose logs --follow db
web-reload:
	docker-compose exec web nginx -s reload
web:
	docker-compose exec web bash
app:
	docker-compose exec app bash
migrate:
	docker-compose exec app php artisan migrate
rollback:
	docker-compose exec app php artisan migrate:rollback
phpartisan:
	docker-compose exec app php artisan
fresh:
	docker-compose exec app php artisan migrate:fresh --seed
seed:
	docker-compose exec app php artisan db:seed
dacapo:
	docker-compose exec app php artisan dacapo
rollback-test:
	docker-compose exec app php artisan migrate:fresh
	docker-compose exec app php artisan migrate:refresh
tinker:
	docker-compose exec app php artisan tinker
test:
	docker-compose exec app php artisan test
backup-run:
	docker-compose exec app php artisan backup:run

test-wikipedia:
	docker-compose exec app ./vendor/bin/phpunit --filter "WikipediaTest"
optimize:
	docker-compose exec app php artisan optimize
optimize-clear:
	docker-compose exec app php artisan optimize:clear
cache:
	docker-compose exec app composer dump-autoload -o
	@make optimize
	docker-compose exec app php artisan event:cache
	docker-compose exec app php artisan view:cache
cache-clear:
	docker-compose exec app composer clear-cache
	@make optimize-clear
	docker-compose exec app php artisan event:clear
config-clear:
	docker-compose exec app php artisan config:clear
npm:
	@make npm-install
npm-install:
	docker-compose exec web npm install
npm-dev:
	docker-compose exec web npm run dev
npm-watch:
	docker-compose exec web npm run watch
npm-watch-poll:
	docker-compose exec web npm run watch-poll
npm-hot:
	docker-compose exec web npm run hot
yarn:
	docker-compose exec web yarn
yarn-install:
	@make yarn
yarn-dev:
	docker-compose exec web yarn dev
yarn-watch:
	docker-compose exec web yarn watch
yarn-watch-poll:
	docker-compose exec web yarn watch-poll
yarn-hot:
	docker-compose exec web yarn hot
