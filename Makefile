COMPOSE=docker-compose
PHP=$(COMPOSE) exec php
CONSOLE=$(PHP) bin/console
COMPOSER=$(PHP) composer

up:
	@${COMPOSE} up -d

down:
	@${COMPOSE} down

clear:
	@${CONSOLE} cache:clear

migration:
	@${CONSOLE} make:migration

migrate:
	@${CONSOLE} doctrine:migrations:migrate

migrate_test:
	@${CONSOLE} doctrine:migrations:migrate --env=test

fixtload:
	@${CONSOLE} doctrine:fixtures:load

fixtload_test:
	@${CONSOLE} doctrine:fixtures:load --env=test

db:
	@${CONSOLE} doctrine:database:create

db_test:
	@${CONSOLE} doctrine:database:create --env=test

encore_dev:
	@${COMPOSE} run --rm node yarn encore dev

encore_prod:
	@${COMPOSE} run --rm node yarn encore production

phpunit:
	@${PHP} bin/phpunit

-include local.mk