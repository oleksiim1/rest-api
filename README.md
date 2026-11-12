# Symfony API

A REST API built with Symfony 7.4 (PHP 8.2+), following Domain-Driven Design and Test-Driven Development. It exposes CRUD endpoints for **products** (`/products`) and **recipes** (`/recipes`). Data is stored in PostgreSQL, and recipe search is powered by Elasticsearch.

## Quick start

```bash
docker compose up -d --build
docker compose exec php php bin/console doctrine:migrations:migrate
docker compose exec php php bin/console app:recipes:reindex
```

The API is then available at http://localhost:8080. A Postman collection is in `postman/`.

## Tests

```bash
vendor/bin/phpunit                       # unit tests
vendor/bin/phpunit --group integration   # tests that need a running Elasticsearch
```
