# Lancement rapide

```bash
cp .env.exemple .env
# Configurer S2LOW_WEBSITE
docker login gitlab.libriciel.fr:4567
docker-compose run  --entrypoint "composer install" web     
docker compose up -d
```


# Lancer les tests d'intégrations
```bash
docker-compose exec web composer test
```
