.PHONY: help
.DEFAULT_GOAL := help

help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

# Default configuration
PHP_BIN := php
COMPOSER_BIN := composer
PHPUNIT_BIN := php bin/phpunit
CONSOLE_BIN := php bin/console

##
## Qualité de code
##---------------------------------------------------------------------------

phpcs: ## Vérifie le code avec PHP CodeSniffer
	vendor/bin/phpcs

phpcs-fix:: ## Corrige automatiquement les erreurs de style
	vendor/bin/phpcbf

phpstan: ## Analyse statique avec PHPStan
	vendor/bin/phpstan analyse

qa: phpcs phpstan ## Lance tous les outils de qualité (phpcs + phpstan)

##
## Projet
##---------------------------------------------------------------------------

install: ## Installe les dépendances
	composer install

update: ## Met à jour les dépendances
	composer update

cache-clear: ## Vide le cache
	php bin/console cache:clear

cache-warmup: ## Préchauffe le cache
	php bin/console cache:warmup

##
## Base de données
##---------------------------------------------------------------------------

db-create: ## Crée la base de données
	php bin/console doctrine:database:create --if-not-exists

db-drop: ## Supprime la base de données
	php bin/console doctrine:database:drop --force --if-exists

db-migrate: ## Exécute les migrations
	php bin/console doctrine:migrations:migrate --no-interaction

db-update: ## Met à jour le schéma de la base de données (doctrine:schema:update)
	php bin/console doctrine:schema:update --force

db-rollback: ## Annule la dernière migration
	php bin/console doctrine:migrations:migrate prev --no-interaction

db-fixtures: ## Charge les fixtures
	php bin/console doctrine:fixtures:load --no-interaction

db-reset: db-drop db-create db-migrate db-fixtures ## Réinitialise complètement la base de données

##@ Database
db-local: ## Create local database with fixtures
	@echo "$(YELLOW)Setting up local database...$(RESET)"
	$(CONSOLE_BIN) doctrine:database:drop --force --if-exists
	$(CONSOLE_BIN) doctrine:database:create
	$(CONSOLE_BIN) doctrine:schema:update --force
	$(CONSOLE_BIN) doctrine:fixtures:load --no-interaction

db-test: ## Create test database with fixtures
	@echo "$(YELLOW)Setting up test database...$(RESET)"
	$(CONSOLE_BIN) doctrine:database:drop --force --env=test --if-exists
	$(CONSOLE_BIN) doctrine:database:create --env=test
	$(CONSOLE_BIN) doctrine:schema:update --force --env=test
	@echo "$(YELLOW)Loading fixtures...$(RESET)"
	$(CONSOLE_BIN) doctrine:fixtures:load --env=test --no-interaction
	@echo "$(GREEN)Test database ready with fixtures loaded!$(RESET)"

##
## Tests
##---------------------------------------------------------------------------

test: ## Lance les tests
	php bin/phpunit

test-coverage: ## Lance les tests avec couverture de code
	XDEBUG_MODE=coverage php bin/phpunit --coverage-html var/coverage

test-vgr-core-api: ## Run VideoGamesRecords Core API tests only
	@echo "$(GREEN)Running VideoGamesRecords Core API tests...$(RESET)"
	@echo "$(YELLOW)Clearing rate limiter cache...$(RESET)"
	$(PHPUNIT_BIN) tests/BoundedContext/VideoGamesRecords/Core/Functional/Api/ --testdox

##
## Assets
##---------------------------------------------------------------------------

assets-install: ## Installe les assets dans le répertoire public
	php bin/console assets:install

assets-compile: ## Compile les assets dans le répertoire public
	php bin/console asset-map:compile

##
## Serveur de développement
##---------------------------------------------------------------------------

serve: ## Démarre le serveur Symfony et compile les assets en mode watch
	symfony server:start -d
	npm run watch

serve-stop: ## Arrête le serveur Symfony
	symfony server:stop

serve-log: ## Affiche les logs du serveur
	symfony server:log
