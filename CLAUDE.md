# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

FlowBoard — Kanban task management app built with Symfony 8.0, Twig, Webpack Encore, MySQL. Early development stage (scaffolding in place, entities/controllers not yet built).

## Commands

```bash
# Quality
make qa                    # phpcs + phpstan
make phpcs                 # PSR-12 code style check
make phpcs-fix             # Auto-fix code style
make phpstan               # Static analysis (level 9)

# Tests
make test                  # Run all tests (php bin/phpunit)
php bin/phpunit tests/Path/To/TestFile.php          # Single test file
php bin/phpunit --filter testMethodName              # Single test method

# Database
make db-reset              # Drop + create + migrate + fixtures
make db-local              # Drop + create + schema:update + fixtures (dev)
make db-test               # Same for test env

# Assets (Webpack Encore)
npm run dev                # Build once
npm run watch              # Build + watch
npm run build              # Production build

# Dev server
make serve                 # Start Symfony server + npm run watch
make serve-stop            # Stop server
```

## Architecture

- **DDD (Domain-Driven Design)** with layered structure:
  - `src/Domain/` — Entities, Value Objects, Repository interfaces, Domain Events, Exceptions
  - `src/Application/` — Use Cases (Command/Query handlers), DTOs, Ports (interfaces for infrastructure)
  - `src/Infrastructure/` — Doctrine repositories, external services, persistence adapters
  - `src/Presentation/` — Controllers, Forms, Twig templates, Stimulus controllers
- **PHP 8.4+**, Symfony 8.0, PSR-12 code style, PHPStan level 9
- **Database**: MySQL 16 (Docker via `compose.yaml`), Doctrine ORM with PHP attribute mapping
- **Frontend**: Webpack Encore, Bootstrap 5, Sass, Stimulus (via `@symfony/stimulus-bridge`), Turbo, Quill (rich text), Tom Select
- **Routing**: attribute-based (`#[Route]` on controllers), auto-discovered via `config/routes.yaml`
- **Services**: autowire + autoconfigure enabled for `src/`
- **Entities**: use `#[ORM\...]` PHP attributes (not YAML/XML). TimestampableTrait planned with PrePersist/PreUpdate lifecycle callbacks
- **Migrations**: `migrations/` directory, namespace `DoctrineMigrations`
- **Translations**: French (fr) and English (en) via Symfony Translation component
- **Tests**: PHPUnit 13, strict mode (failOnDeprecation/Notice/Warning), bootstrap loads `.env` via Dotenv
- **Docker**: MySQL + Mailpit (dev mail catcher on port 8025)

## Conventions

- All PHP files must start with `declare(strict_types=1);`
- Language: project documentation and comments in English
- Entity namespace: `App\Entity`, repository namespace: `App\Repository`
- Column positions use spacing of 1000 for drag-and-drop reordering
- Soft delete via Gedmo on Card entity (planned)
- Security roles: ROLE_USER (view), ROLE_ADMIN (CRUD), ROLE_SUPER_ADMIN (user management)
