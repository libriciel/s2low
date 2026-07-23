DOCKER=docker
DOCKER_COMPOSE=docker compose \
-f compose.yaml \
-f compose.dev.yaml \
$(if $(wildcard compose.override.yaml),-f compose.override.yaml)

IN_CONTAINER := $(shell [ -f /.dockerenv ] && echo 1 || echo 0)
.DEFAULT_GOAL := help
.PHONY: help

ifneq ($(IN_CONTAINER),1)
    DOCKER_COMPOSE_EXEC=$(DOCKER_COMPOSE) exec app
    DOCKER_COMPOSE_RUN=$(DOCKER_COMPOSE) run --rm --entrypoint /bin/sh app
    DOCKER_COMPOSE_UP=$(DOCKER_COMPOSE) up -d
    WEBPACK_RUN=$(DOCKER_COMPOSE) run --rm webpack
else
	DOCKER_COMPOSE_EXEC=
	DOCKER_COMPOSE_RUN=
	DOCKER_COMPOSE_UP=
	WEBPACK_RUN=
endif

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

env:
ifeq ($(wildcard .env),)
	cp .env.dev.example .env
	sed -i "s/^DATABASE_PASSWORD=.*/DATABASE_PASSWORD=$(shell openssl rand -base64 32 | md5sum | cut -d' ' -f1)/" .env
	sed -i "s/^DATABASE_PASSWORD_TEST=.*/DATABASE_PASSWORD_TEST=$(shell openssl rand -base64 32 | md5sum | cut -d' ' -f1)/" .env
	sed -i "s/^APP_SECRET=.*/APP_SECRET=$(shell openssl rand -base64 32 | md5sum | cut -d' ' -f1)/" .env
endif

composer-install: ## Run composer install
	$(DOCKER_COMPOSE_RUN) -c "composer install"

npm-install: ## Run npm install
	$(WEBPACK_RUN) npm install

webpack: ## Compile webpack assets
	$(WEBPACK_RUN) npx webpack --config webpack.config.js

js-unit-test:
	$(WEBPACK_RUN) npm test

install: composer-install npm-install webpack

clean: ## Clear and remove dependencies
	rm -rf  vendor
	rm -rf /node_modules/*
	rm -rf public.ssl/jsmodules/*

test-db-init:
	$(DOCKER_COMPOSE_EXEC) composer test-db-init

docker-compose-up: ## Up all container
	$(DOCKER_COMPOSE_UP)

phpcs: docker-compose-up ## Check code style through docker-compose
	$(DOCKER_COMPOSE_EXEC) phpcs

phpcbf: docker-compose-up ## Fix all code style errors
	$(DOCKER_COMPOSE_EXEC) phpcbf

phpunit: docker-compose-up ## Run unit test through docker-compose
	$(DOCKER_COMPOSE_EXEC) composer test

coverage: docker-compose-up ## Run unit test through docker-compsose with coverage
	$(DOCKER_COMPOSE_EXEC) composer test-cover

start:  ## Start all services
	$(DOCKER_COMPOSE) up -d --remove-orphans

stop: ## Stop all services
	$(DOCKER_COMPOSE) down

build-app: ## Build app container
	$(DOCKER_COMPOSE) build app

build-web: ## Build web container
	$(DOCKER_COMPOSE) build web

build: build-app build-web ## Build containers

bash: ## Get a bash console from the running "app" docker
	$(DOCKER_COMPOSE) exec app bash

run: ## Get a bash console from a fresh container
	$(DOCKER_COMPOSE) run web bash

force-bash: ## Force a bash console without running the entrypoint
	$(DOCKER_COMPOSE) run --entrypoint bash web

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
