.PHONY: *
.DEFAULT_GOAL := help

SHELL := /bin/bash
COMPOSE := docker compose -f docker/compose.yaml -p plex-mcp
APP := $(COMPOSE) exec -T app

##@ Setup

start: up composer ## Start the application in development mode

restart: stop start ## Restart the application in development mode

up:
	[ -f app/.env ] || cp app/.env.example app/.env
	[ -f docker/dev.env ] || cp docker/dev.env.example docker/dev.env
	$(COMPOSE) up -d

stop: ## Stop the application and clean up
	$(COMPOSE) down -v --remove-orphans

composer: ## Install Composer dependencies
	$(APP) composer install --no-interaction --no-ansi

clean: ## Clean up the application
	$(APP) rm -fr storage/framework/cache/*

##@ Testing/Linting

can-release: test lint ## Run all the same checks as CI to ensure code can be released

test: ## Run the test suite
	$(APP) php artisan test

test/%: ## Run the test suite with a filter
	$(APP) php artisan test --filter=$*

lint: ## Run the linting tools
	$(APP) vendor/bin/pint --test
	$(APP) vendor/bin/rector --dry-run

fmt: format
format: ## Fix style related code violations
	$(APP) vendor/bin/rector
	$(APP) vendor/bin/pint

##@ Running Instance

open: ## Open the website in the default browser
	open http://localhost:8001/

sh: shell
shell: ## Access a shell within the running container
	$(COMPOSE) exec app bash

shell/%: ## Run a command within the running container
	$(APP) sh -c "$*"

logs: ## Tail the container logs
	$(COMPOSE) logs -f

ps: ## List the running containers
	$(COMPOSE) ps -a

mcp/boost:
	$(APP) php artisan boost:mcp

mcp/plex: ## Plex MCP (stdio)
	$(APP) php artisan mcp:start plex

help:
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage:\n  make \033[36m<target>\033[0m\n"} /^[a-zA-Z_\-\/]+:.*?##/ { printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) } ' $(MAKEFILE_LIST)
