.PHONY: build start stop install bash test run test-script help

# ============================================================================
#  Migration S3 - Makefile
# ============================================================================

help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

build: ## Construit l'image Docker
	docker compose build

start: ## Démarre les conteneurs (app + db)
	docker compose up -d

stop: ## Arrête et supprime les conteneurs
	docker compose down

install: ## Lance composer install dans le conteneur
	docker exec -it php_sqlite_composer composer install

bash: ## Ouvre un shell interactif dans le conteneur
	docker exec -it php_sqlite_composer /bin/bash

test: ## Lance les tests unitaires PHPUnit
	docker exec php_sqlite_composer vendor/bin/phpunit tests/ --testdox

run: ## Lance le script de migration (args= pour les options)
	docker exec -it php_sqlite_composer php src/script.php $(args)

test-script: ## Lance l'outil de diagnostic S3 (args= pour les options)
	docker exec -it php_sqlite_composer php src/test-script.php $(args)

# ============================================================================
#  Exemples d'utilisation
# ============================================================================
#
#  Lancer le daemon complet :
#    make run args="--step=daemon"
#
#  Daemon filtré par type :
#    make run args="--step=daemon --type=acte"
#    make run args="--step=daemon --type=pes_aller,pes_acquit"
#
#  Daemon avec plage de dates :
#    make run args="--step=daemon --type=acte --min-date=2012-01-01 --max-date=2013-01-01"
#
#  Import seul (sans resolve/download/upload) :
#    make run args="--step=import --type=acte --max-date=2015-01-01"
#
#  Retenter les transactions en erreur :
#    make run args="--step=daemon --type=acte --retry-errors"
#
#  Vérifier les restaurations Glacier :
#    make run args="--step=check-restore"
#
#  Diagnostiquer un fichier S3 :
#    make test-script args="-t acte '212105340/AR24_2012/SLO-EACT--212105340--20120221-4.tar.gz'"
#
#  Lister les buckets S3 :
#    make test-script args="-l"
#
