.PHONY: help start stop restart build up down logs shell composer install db-create db-migrate cache-clear test serve

# Variables
DOCKER_COMPOSE = docker compose
PHP = php
COMPOSER = composer
CONSOLE = $(PHP) bin/console
SYMFONY = symfony

# Couleurs pour l'affichage
GREEN  := $(shell tput -Txterm setaf 2)
YELLOW := $(shell tput -Txterm setaf 3)
RESET  := $(shell tput -Txterm sgr0)

## —— Makefile Symfony + Docker 🐳 ————————————————————————————————————————
help: ## Affiche cette aide
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

## —— Docker 🐳 ————————————————————————————————————————————————————————————
up: ## Démarre les conteneurs Docker (base de données)
	@echo "$(GREEN)Démarrage des conteneurs...$(RESET)"
	$(DOCKER_COMPOSE) up -d

start: up ## Démarre le projet (conteneurs Docker)
	@echo "$(GREEN)✓ Conteneurs démarrés !$(RESET)"
	@echo "$(YELLOW)Base de données disponible sur localhost:7007$(RESET)"
	@echo "$(YELLOW)  - User: db_user$(RESET)"
	@echo "$(YELLOW)  - Database: kubo$(RESET)"
	@echo "$(YELLOW)  - User: db_user$(RESET)"
	@echo "$(YELLOW)  - Database: kubo$(RESET)"
	@echo "$(GREEN)Pour démarrer le serveur Symfony, utilisez: make serve$(RESET)"

stop: ## Arrête les conteneurs Docker
	@echo "$(YELLOW)Arrêt des conteneurs...$(RESET)"
	$(DOCKER_COMPOSE) stop

down: ## Arrête et supprime les conteneurs Docker
	@echo "$(YELLOW)Arrêt et suppression des conteneurs...$(RESET)"
down-volumes: ## Arrête et supprime conteneurs + volumes (ATTENTION: perte de données)
	@echo "$(YELLOW)⚠️  Suppression des conteneurs ET volumes...$(RESET)"
	$(DOCKER_COMPOSE) down -v

reset-docker: ## Redémarre Docker et recrée les conteneurs
	@echo "$(YELLOW)Redémarrage complet de Docker...$(RESET)"
	@chmod +x reset-db.sh
	@./reset-db.sh

	$(DOCKER_COMPOSE) down --remove-orphans

down-volumes: ## Arrête et supprime conteneurs + volumes (ATTENTION: perte de données)
	@echo "$(YELLOW)⚠️  Suppression des conteneurs ET volumes...$(RESET)"
	$(DOCKER_COMPOSE) down -v

reset-docker: ## Redémarre Docker et recrée les conteneurs
	@echo "$(YELLOW)Redémarrage complet de Docker...$(RESET)"
	@chmod +x bin/reset-db
	@./bin/reset-db

restart: stop start ## Redémarre les conteneurs

logs: ## Affiche les logs des conteneurs
	$(DOCKER_COMPOSE) logs -f

ps: ## Liste les conteneurs en cours d'exécution
	$(DOCKER_COMPOSE) ps

## —— Composer 🧙 ——————————————————————————————————————————————————————————
composer: ## Exécute composer
	@$(eval c ?=)
	$(COMPOSER) $(c)

install: ## Installe les dépendances PHP
	@echo "$(GREEN)Installation des dépendances...$(RESET)"
	$(COMPOSER) install

update: ## Met à jour les dépendances PHP
	$(COMPOSER) update

## —— Symfony 🎵 ———————————————————————————————————————————————————————————
sf: ## Exécute une commande Symfony (utilisation: make sf c="about")
	@$(eval c ?=)
	$(CONSOLE) $(c)

serve: ## Démarre le serveur de développement Symfony
	@echo "$(GREEN)Démarrage du serveur Symfony...$(RESET)"
	@if command -v symfony > /dev/null; then \
		$(SYMFONY) serve; \
	else \
		$(PHP) -S localhost:8000 -t public; \
	fi

cache-clear: ## Vide le cache
	@echo "$(GREEN)Vidage du cache...$(RESET)"
	$(CONSOLE) cache:clear

cache-warmup: ## Préchauffe le cache
	$(CONSOLE) cache:warmup

## —— Base de données 💾 ————————————————————————————————————————————————————
db-create: ## Crée la base de données
	@echo "$(GREEN)Création de la base de données...$(RESET)"
	$(CONSOLE) doctrine:database:create --if-not-exists

db-drop: ## Supprime la base de données
	@echo "$(YELLOW)Suppression de la base de données...$(RESET)"
	$(CONSOLE) doctrine:database:drop --force --if-exists

db-migrate: ## Exécute les migrations
	@echo "$(GREEN)Exécution des migrations...$(RESET)"
	$(CONSOLE) doctrine:migrations:migrate --no-interaction

db-rollback: ## Annule la dernière migration
	$(CONSOLE) doctrine:migrations:migrate prev --no-interaction

db-diff: ## Génère une nouvelle migration
	$(CONSOLE) doctrine:migrations:diff

db-validate: ## Valide le schéma de la base de données
	$(CONSOLE) doctrine:schema:validate

db-fixtures: ## Charge les fixtures
	$(CONSOLE) doctrine:fixtures:load --no-interaction

check: ## Vérifie la configuration du projet
	@chmod +x bin/check-config
	@./bin/check-config

fix-db: ## Diagnostique et répare la connexion à la base de données
	@chmod +x bin/fix-db
	@./bin/fix-db

test-connection: ## Teste la connexion à PostgreSQL
	@chmod +x bin/test-connection
	@./bin/test-connection

db-reset: db-drop db-create db-migrate ## Réinitialise la base de données

## —— Tests ✅ ——————————————————————————————————————————————————————————————
test: ## Exécute les tests
	@echo "$(GREEN)Exécution des tests...$(RESET)"
	$(PHP) bin/phpunit

test-coverage: ## Exécute les tests avec couverture de code
	$(PHP) bin/phpunit --coverage-html var/coverage

## —— Outils 🛠️ ————————————————————————————————————————————————————————————
check: ## Vérifie la configuration du projet
	@chmod +x check-config.sh
	@./check-config.sh

fix-db: ## Diagnostique et répare la connexion à la base de données
	@chmod +x fix-db.sh
	@./fix-db.sh

test-connection: ## Teste la connexion à PostgreSQL
	@chmod +x test-connection.sh
	@./test-connection.sh

shell-db: ## Ouvre un shell dans le conteneur de base de données
	$(DOCKER_COMPOSE) exec kubodb sh

psql: ## Ouvre psql dans le conteneur de base de données
	$(DOCKER_COMPOSE) exec kubodb psql -U db_user -d kubo

clean: ## Nettoie le projet (cache, logs)
	@echo "$(YELLOW)Nettoyage du projet...$(RESET)"
	@rm -rf var/cache/* var/log/*

cleanup-scripts: ## Supprime les anciens scripts .sh à la racine
	@chmod +x bin/cleanup-old-scripts
	@./bin/cleanup-old-scripts

verify-migration: ## Vérifie que la migration des scripts est complète
	@chmod +x bin/verify-migration
	@./bin/verify-migration

## —— Installation complète 🚀 ——————————————————————————————————————————————
setup: start install db-create db-migrate ## Installation complète du projet
	@echo "$(GREEN)✓ Installation terminée !$(RESET)"
	@echo "$(YELLOW)Le projet est prêt à être utilisé.$(RESET)"
	@echo "$(GREEN)Utilisez 'make serve' pour démarrer le serveur Symfony.$(RESET)"

