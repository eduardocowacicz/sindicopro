SHELL := /bin/sh

DEVOPS_DIR := $(patsubst %/,%,$(dir $(abspath $(lastword $(MAKEFILE_LIST)))))
DEV := $(DEVOPS_DIR)/dev

.PHONY: help init require-env config build up down restart ps logs \
	backend-up backend-down backend-shell frontend-up frontend-down frontend-shell \
	database-shell migrate seed test-backend test-frontend quality

help:
	@echo "SindicoPro - ambiente local"
	@echo "  make init             cria Devops/.env com APP_KEY local"
	@echo "  make config           valida a composicao"
	@echo "  make build            constroi as imagens de desenvolvimento"
	@echo "  make up               inicia todos os servicos"
	@echo "  make backend-up       inicia somente o conjunto do backend"
	@echo "  make frontend-up      inicia somente o frontend"
	@echo "  make down             encerra os servicos sem apagar volumes"
	@echo "  make logs             acompanha os logs"
	@echo "  make migrate          executa as migrations"
	@echo "  make seed             executa os seeders"
	@echo "  make test-backend     executa os testes Laravel"
	@echo "  make test-frontend    executa os testes Vue"
	@echo "  make quality          executa verificacoes locais"

init:
	@$(DEV) init

require-env:
	@$(DEV) init

config:
	@$(DEV) config >/dev/null

build:
	@$(DEV) build

up:
	@$(DEV) up

down:
	@$(DEV) down

restart:
	@$(DEV) restart

ps:
	@$(DEV) ps

logs:
	@$(DEV) logs

backend-up:
	@$(DEV) backend-up

backend-down:
	@$(DEV) backend-down

backend-shell:
	@$(DEV) backend-shell

frontend-up:
	@$(DEV) frontend-up

frontend-down:
	@$(DEV) frontend-down

frontend-shell:
	@$(DEV) frontend-shell

database-shell:
	@$(DEV) database-shell

migrate:
	@$(DEV) migrate

seed:
	@$(DEV) seed

test-backend:
	@$(DEV) test-backend

test-frontend:
	@$(DEV) test-frontend

quality:
	@$(DEV) quality
