# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

A Symfony 7.4 REST API (PHP >= 8.2) built with Domain-Driven Design and Test-Driven Development.

## Commands

```bash
composer install                      # install dependencies
docker compose up -d --build          # run the API (http://localhost:8080) with nginx and Postgres
docker compose exec php php bin/console <command>  # run a console command in the container
php bin/console debug:router          # list routes
php bin/console lint:container        # validate service wiring
php bin/console lint:yaml config      # validate YAML config
php bin/console cache:clear
php bin/console app:recipes:reindex  # rebuild the recipe search index from the database
```

Tests:

```bash
vendor/bin/phpunit                                  # all tests
vendor/bin/phpunit tests/Path/To/SomeTest.php       # one file
vendor/bin/phpunit --filter testMethodName          # one test
vendor/bin/phpunit --group integration              # tests that need a real Elasticsearch (excluded by default)
```

## Architecture (DDD)

Organize code by bounded context, each split into layers, under the existing `App\` => `src/` PSR-4 root:

```
src/<Context>/
    Domain/          # entities, value objects, aggregates, domain events, repository interfaces
    Application/     # use cases: commands, queries, handlers, DTOs
    Infrastructure/  # repository implementations, persistence mapping, external adapters
    Presentation/    # controllers, request/response mapping
```

Dependencies point inward only: `Domain` depends on nothing outside itself (no Symfony or ORM imports), `Application` depends on `Domain`, and `Infrastructure` and `Presentation` depend on the layers below them. Controllers stay thin and delegate to `Application`; business rules live in `Domain`.

`config/services.yaml` registers everything under `src/` as a service. Exclude `Domain` entities and value objects from that resource, and bind repository interfaces to their `Infrastructure` implementations.

`config/routes.yaml` loads routes from tagged controller services, so a `#[Route]` controller anywhere under `src/` is picked up without routing changes as long as it stays registered as a service.

## Testing (TDD)

Write a failing test first, make it pass with the minimum code, then refactor.

Tests go under `tests/` (`App\Tests\`), mirroring the `src/` structure:

- `Domain` tests are pure unit tests with no kernel or database.
- `Application` tests use in-memory fakes for repository interfaces.
- `Infrastructure` and `Presentation` tests use `KernelTestCase` and `WebTestCase`, the latter covering HTTP status codes and response bodies.

Don't write tests for seeds (Faker data-seeding commands and fixtures); they're exempt from TDD.

### Elasticsearch testing rules

- By default, run only the unit test suite. Do not run integration tests or any test that needs a real Elasticsearch instance.
- Never connect to a real Elasticsearch server in unit tests. Access Elasticsearch only through a repository or search service interface defined in `Domain` or `Application`, implemented in `Infrastructure`, and use an in-memory fake or mock of that interface in tests.
- When adding or changing search logic, write unit tests that assert on the queries built and the results mapped, using a mocked Elasticsearch client or fake search service.
- Integration tests that need a real Elasticsearch instance may be written, but must be tagged with `#[Group('integration')]` and must not be executed unless the issue explicitly asks for it.

## Git
- Branch names: `<type>/<ticket-id>-<kebab-description>` (types: feat, fix, chore, docs, refactor)
- Branch from `master`, never commit directly to it
