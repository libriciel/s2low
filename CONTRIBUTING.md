# Lancement rapide

cp .env.exemple .env
// Configurer S2LOW_WEBSITE
docker login gitlab.libriciel.fr:4567
docker-compose run  --entrypoint "composer install" web     
docker compose up -d


# Lancer les tests d'intégrations

docker-compose exec web bash
    composer test

