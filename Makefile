DOCKER=docker
PASTELL_PATH=/var/www/pastell
EXEC_NODE=$(DOCKER) run --rm --volume ${PWD}:$(PASTELL_PATH) -it node:14-slim
EXEC_COMPOSER=$(DOCKER) run --rm --volume ${PWD}:/app --volume ${HOME}/.composer:/tmp -it composer:2
DOCKER_COMPOSE=docker compose -f docker-compose.yml -f docker-compose.test.yml -f docker-compose.dev.yml
DOCKER_COMPOSE_EXEC=$(DOCKER_COMPOSE) exec web
DOCKER_COMPOSE_UP=$(DOCKER_COMPOSE)  up -d

.DEFAULT_GOAL := help
.PHONY: help

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

composer-install: ## Run composer install
	$(EXEC_COMPOSER) composer install --ignore-platform-reqs

clean: ## Clear and remove dependencies
	rm -rf  vendor
	rm -rf /node_modules/*
	rm -rf public.ssl/jsmodules/*

test: phpunit  ## Run all tests (code style, unit test, ...)

docker-compose-up: ## Up all container
	$(DOCKER_COMPOSE_UP)

phpunit: docker-compose-up ## Run unit test through docker-compose
	$(DOCKER_COMPOSE_EXEC) composer test

coverage: docker-compose-up ## Run unit test through docker-compsose with coverage
	$(DOCKER_COMPOSE_EXEC) composer test-cover

start:  ## Start all services
	$(DOCKER_COMPOSE) up -d --remove-orphans

stop: ## Stop all services
	$(DOCKER_COMPOSE) down

build: ## Build the container
	$(DOCKER_COMPOSE) build web

build-webpack: ## Build the node container
	$(DOCKER_COMPOSE) build webpack

bash: ## Get a bash console from the running "web" docker
	$(DOCKER_COMPOSE) exec web bash

run: ## Get a bash console from a fresh container
	$(DOCKER_COMPOSE) run web bash

force-bash: ## Force a bash console without running the entrypoint
	$(DOCKER_COMPOSE) run --entrypoint bash web

phpcs: docker-compose-up ## Check code style through docker-compose
	$(DOCKER_COMPOSE_EXEC) phpcs

phpcbf: docker-compose-up ## Fix all code style errors
	$(DOCKER_COMPOSE_EXEC) phpcbf

npm-install: docker-compose-up ## Install npm modules
	$(DOCKER_COMPOSE) run -it webpack npm install

webpack: docker-compose-up ## Compile webpack assets
	$(DOCKER_COMPOSE) run -it webpack npx webpack --config webpack.config.js

install: composer-install npm-install webpack

new-migration:
	$(DOCKER_COMPOSE_EXEC) php bin/console doctrine:migrations:generate

migrate:
	$(DOCKER_COMPOSE_EXEC) php bin/console doctrine:migrations:migrate

v ?= 0
migrate-to:
	$(DOCKER_COMPOSE_EXEC) php bin/console doctrine:migrations:migrate 'DoctrineMigrations\Version$(v)' --no-interaction

migrate-reset:
	$(DOCKER_COMPOSE_EXEC) php bin/console doctrine:migrations:migrate 0


undo-last-migration:
	$(DOCKER_COMPOSE_EXEC) php bin/console doctrine:migrations:migrate prev --no-interaction
