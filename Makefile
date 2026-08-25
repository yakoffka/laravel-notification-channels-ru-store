.PHONY: up build down install bash test help

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

help:
	@echo "Available targets:"
	@echo "  up       - Start containers in detached mode"
	@echo "  build    - Rebuild images (without cache)"
	@echo "  down     - Stop and remove containers"
	@echo "  install  - Install composer dependencies inside container"
	@echo "  bash     - Open interactive bash shell in container"
	@echo "  test     - Run tests inside container"
