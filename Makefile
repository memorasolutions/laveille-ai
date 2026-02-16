.PHONY: install dev test test-coverage lint lint-check analyse rector rector-fix seed migrate fresh cache cache-clear horizon telescope docker-up docker-down docker-build deploy ide-helper

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
	php artisan test

test-coverage:
	php artisan test --coverage

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
	php artisan config:cache
	php artisan route:cache
	php artisan view:cache

cache-clear:
	php artisan config:clear
	php artisan route:clear
	php artisan view:clear
	php artisan cache:clear

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
	bash scripts/deploy.sh

ide-helper:
	php artisan ide-helper:generate
	php artisan ide-helper:models -N
	php artisan ide-helper:meta
