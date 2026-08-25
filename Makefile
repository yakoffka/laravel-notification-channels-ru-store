.PHONY: up build down install bash test help lint lint-fix analyse check

up:
	docker compose up -d

build:
	docker compose build --no-cache

down:
	docker compose down

install:
	docker compose exec php-82 composer install

bash:
	docker compose exec php-82 bash

test:
	docker compose exec php-82 composer test

lint:
	docker compose exec php-82 composer lint

lint-fix:
	docker compose exec php-82 composer lint:fix

analyse:
	docker compose exec php-82 composer analyse

check: lint-fix analyse test
	@echo "All checks passed"

help:
	@echo "Available targets:"
	@echo "  up         - Start containers in detached mode"
	@echo "  build      - Rebuild images (without cache)"
	@echo "  down       - Stop and remove containers"
	@echo "  install    - Install composer dependencies inside container"
	@echo "  bash       - Open interactive bash shell in container"
	@echo "  test       - Run tests inside container"
	@echo "  lint       - Check code style (Pint)"
	@echo "  lint-fix   - Fix code style automatically (Pint)"
	@echo "  analyse    - Run static analysis (PHPStan)"
	@echo "  check      - Run lint, analyse, and test in sequence"
