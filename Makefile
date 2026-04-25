COMPOSE := docker compose
APP := app
DB := db
TEST_BUSINESS_ID ?= 2
TEST_USERNAME ?= testadmin
TEST_PASSWORD ?= test123456
TEST_EMAIL ?=

.PHONY: help build up down destroy restart ps logs shell root-shell artisan composer migrate fresh seed mysql test-user

help:
	@printf "Available commands:\n"
	@printf "  make build       Build Docker images\n"
	@printf "  make up          Start app, db, and phpMyAdmin\n"
	@printf "  make down        Stop the stack\n"
	@printf "  make destroy     Stop the stack and remove volumes\n"
	@printf "  make restart     Restart the stack\n"
	@printf "  make ps          Show container status\n"
	@printf "  make logs        Tail container logs\n"
	@printf "  make shell       Open a shell in the app container\n"
	@printf "  make root-shell  Open a root shell in the app container\n"
	@printf "  make artisan c=about\n"
	@printf "  make composer c=install\n"
	@printf "  make migrate     Run php artisan migrate --force\n"
	@printf "  make fresh       Run php artisan migrate:fresh --seed --force\n"
	@printf "  make seed        Run php artisan db:seed --force\n"
	@printf "  make mysql       Open a MySQL shell as root\n"
	@printf "  make test-user   Create or refresh a local test admin user\n"

build:
	$(COMPOSE) build

up:
	$(COMPOSE) up -d --build

down:
	$(COMPOSE) down

destroy:
	$(COMPOSE) down -v

restart:
	$(MAKE) down
	$(MAKE) up

ps:
	$(COMPOSE) ps

logs:
	$(COMPOSE) logs -f

shell:
	$(COMPOSE) exec $(APP) sh

root-shell:
	$(COMPOSE) exec --user root $(APP) sh

artisan:
	$(COMPOSE) exec $(APP) php artisan $(c)

composer:
	$(COMPOSE) exec $(APP) composer $(c)

migrate:
	$(COMPOSE) exec $(APP) php artisan migrate --force

fresh:
	$(COMPOSE) exec $(APP) php artisan migrate:fresh --seed --force

seed:
	$(COMPOSE) exec $(APP) php artisan db:seed --force

mysql:
	$(COMPOSE) exec $(DB) mysql -uroot -proot ultimate_pos

test-user:
	$(COMPOSE) exec $(APP) php artisan dev:test-user --business-id=$(TEST_BUSINESS_ID) --username=$(TEST_USERNAME) --password=$(TEST_PASSWORD) --email="$(TEST_EMAIL)"
