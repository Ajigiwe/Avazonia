# Avazonia — local dev shortcuts (requires `make` or `nmake`; otherwise use docker compose directly)
# Windows: `make up` works if you have GnuWin Make or use `docker compose up` manually.

up:
	docker compose up --build -d --wait
	docker compose exec app php bin/setup.php
	@echo "App: http://localhost:8080  |  phpMyAdmin: http://localhost:8081"

down:
	docker compose down

logs:
	docker compose logs -f app

shell:
	docker compose exec app bash

seed:
	docker compose exec app php bin/setup.php

fresh:
	docker compose exec app php bin/setup.php --fresh

clean:
	docker compose down -v

native:
	php -S localhost:8000 -t . router.php

lint:
	php -l index.php
	php -l config/app.php
	php -l config/database.php
	php bin/setup.php --help || true
