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

fixtload:
	@${CONSOLE} doctrine:fixtures:load

db:
	@${CONSOLE} doctrine:database:create

encore_dev:
	@${COMPOSE} run --rm node yarn encore dev

encore_prod:
	@${COMPOSE} run --rm node yarn encore production

phpunit:
	@${PHP} bin/phpunit
	
-include local.mk