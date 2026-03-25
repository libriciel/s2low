build:
	docker compose build

start:
	docker compose up -d

install:
	docker exec -it php_sqlite_composer composer install

stop:
	docker compose down

bash:
	docker exec -it php_sqlite_composer /bin/bash

test:
	docker exec php_sqlite_composer vendor/bin/phpunit tests/ --testdox

run:
	docker exec -it php_sqlite_composer php src/script.php $(args)

test-script:
	docker exec -it php_sqlite_composer php src/test-script.php $(args)
