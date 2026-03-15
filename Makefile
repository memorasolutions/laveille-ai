# Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
.PHONY: install dev test test-coverage lint lint-check analyse rector rector-fix seed migrate fresh cache cache-clear horizon telescope docker-up docker-down docker-build deploy ide-helper check check-quick logs e2e setup-hooks

install:
	composer install
	npm install
	cp .env.example .env
	php artisan key:generate
	php artisan migrate
	npm run build

dev:
	php artisan serve &
	npm run dev

test:
	php artisan view:cache
	php artisan test --parallel --exclude-group=sequential
	php artisan test --group=sequential

test-sequential:
	php -d memory_limit=2G artisan test

test-coverage:
	php -d "zend_extension=/Applications/Herd.app/Contents/Resources/xdebug/xdebug-84-arm64.so" -d "xdebug.mode=coverage" vendor/bin/pest --coverage --min=80

lint:
	vendor/bin/pint

lint-check:
	vendor/bin/pint --test

analyse:
	vendor/bin/phpstan analyse

rector:
	vendor/bin/rector process --dry-run

rector-fix:
	vendor/bin/rector process

seed:
	php artisan db:seed

migrate:
	php artisan migrate

fresh:
	@echo "WARNING: This will drop all tables and re-run all migrations with seeding"
	php artisan migrate:fresh --seed

cache:
	php artisan optimize

cache-clear:
	php artisan optimize:clear

horizon:
	php artisan horizon

telescope:
	@echo "Visit /telescope in your browser"

docker-up:
	docker compose up -d

docker-down:
	docker compose down

docker-build:
	docker compose build --no-cache

deploy:
	composer install --no-dev --optimize-autoloader --classmap-authoritative
	npm ci --production
	npm run build
	php artisan migrate --force
	php artisan optimize
	php artisan storage:link

ide-helper:
	php artisan ide-helper:generate
	php artisan ide-helper:models -N
	php artisan ide-helper:meta

check:
	php artisan app:check

check-quick:
	php artisan app:check --quick

logs:
	php artisan app:logs

e2e:
	npx playwright test tests/e2e/

setup-hooks:
	php artisan app:setup-hooks
