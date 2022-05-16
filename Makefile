DOCKER=docker
PASTELL_PATH=/var/www/pastell
EXEC_NODE=$(DOCKER) run --rm --volume ${PWD}:$(PASTELL_PATH) -it node:14-slim
EXEC_COMPOSER=$(DOCKER) run --rm --volume ${PWD}:/app --volume ${HOME}/.composer:/tmp -it composer:2
DOCKER_COMPOSE=docker-compose -f docker-compose.yml -f docker-compose.simulateur.yml -f docker-compose.dev.yml
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

build:
	$(DOCKER_COMPOSE) build web

run:
	$(DOCKER_COMPOSE) run web bash