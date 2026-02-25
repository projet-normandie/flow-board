# FlowBoard

Kanban task management application built with Symfony 8.0, Twig, Webpack Encore and MySQL.

## Tech Stack

- **Backend:** PHP 8.4, Symfony 8.0, Doctrine ORM
- **Frontend:** Twig, Bootstrap 5, Stimulus, Turbo, Webpack Encore
- **Database:** MySQL 8.0 (Docker)
- **Rich Text:** Quill editor
- **Drag & Drop:** SortableJS
- **Soft Delete:** Gedmo SoftDeleteable
- **Audit Trail:** damienharper/auditor-bundle

## Requirements

- PHP 8.4+
- Composer
- Node.js / npm
- Docker & Docker Compose

## Installation

```bash
git clone <repository-url>
cd flow-board

composer install
npm install

# Start Docker services (MySQL + Mailpit)
docker compose up -d

# Create database, run migrations and load fixtures
make db-local

# Build assets
npm run dev

# Start the dev server
make serve
```

The application is available at `https://localhost:8000`.
Mailpit (dev mail catcher) is available at `http://localhost:8025`.

## Default Users

| Email                 | Password       | Role             |
|-----------------------|----------------|------------------|
| admin@flowboard.dev   | FlowBoard2026! | ROLE_SUPER_ADMIN |
| manager@flowboard.dev | FlowBoard2026! | ROLE_ADMIN       |
| alice@flowboard.dev   | FlowBoard2026! | ROLE_USER        |
| bob@flowboard.dev     | FlowBoard2026! | ROLE_USER        |
| charlie@flowboard.dev | FlowBoard2026! | ROLE_USER        |

## Commands

```bash
# Dev server
make serve              # Start Symfony server + npm run watch
make serve-stop         # Stop server

# Quality
make qa                 # phpcs + phpstan
make phpcs              # PSR-12 code style check
make phpcs-fix          # Auto-fix code style
make phpstan            # Static analysis (level 9)

# Tests
make test               # Run all tests

# Database
make db-local           # Drop + create + schema:update + fixtures (dev)
make db-reset           # Drop + create + migrate + fixtures
make db-test            # Same for test env

# Assets
npm run dev             # Build once
npm run watch           # Build + watch
npm run build           # Production build
```

## Architecture

DDD layered structure:

```
src/
  Domain/           Entities, Enums, Traits, Repository interfaces
  Application/      Security (Voters), Commands
  Infrastructure/   Doctrine repositories
  Presentation/     Controllers, Forms, Templates, Stimulus controllers
```

### Entity relationships

```
Project 1──N Board 1──N Column 1──N Card N──M Label
                                     │
                                     ├── author    (M:1 → User)
                                     └── assignees (M:N → User)
```

### Enums

- **CardPriority:** LOW, MEDIUM, HIGH, CRITICAL (visual border on cards)
- **JobTitle:** DEVELOPER, TESTER, SYS_ADMIN, PRODUCT_OWNER

## Security

| Role             | Permissions                                    |
|------------------|------------------------------------------------|
| ROLE_USER        | View projects/boards, create/edit own cards    |
| ROLE_ADMIN       | Full CRUD on projects, boards, columns, labels |
| ROLE_SUPER_ADMIN | User management                                |

A `CardVoter` with `RoleHierarchyInterface` handles card-level access (VIEW, EDIT, DELETE).
Disabled users (`enabled = false`) are blocked at login by Symfony Security.

## Key Features

- **Multi-project boards:** each project has one or more boards with columns
- **Drag & drop:** SortableJS with position spacing of 1000, auto-rebalance when gaps are too small
- **Card details:** title, rich text description (Quill), priority, due date, labels, assignees
- **Archive:** soft delete via Gedmo, dedicated archive page with restore
- **Admin panel:** CRUD for projects, boards, columns, labels, users + dashboard with stats
- **Audit trail:** all entity changes tracked via auditor-bundle
- **i18n:** French and English translations
- **Dark mode:** theme toggle in navbar

## Roadmap

### V0 (MVP) — current

- [x] Entities: User, Project, Board, Column, Card, Label
- [x] TimestampableTrait (createdAt / updatedAt)
- [x] Symfony Security: firewall, login form, role hierarchy
- [x] CardVoter with RoleHierarchyInterface
- [x] Admin CRUD: projects, boards, columns, labels, users
- [x] Card CRUD: create, edit, delete with forms (priority, labels, assignees, due date)
- [x] Kanban board view with drag & drop (SortableJS)
- [x] Rich text editor (Quill) for card descriptions
- [x] Card archive (soft delete Gedmo) + restore
- [x] Audit trail (damienharper/auditor-bundle)
- [x] Translations (FR / EN)
- [x] Fixtures with demo data
- [x] CLI command: `app:create-admin`
- [ ] Complete test coverage (unit + functional)

### V1

- [ ] User-Project permissions (project_user table)
- [ ] Comments on cards
- [ ] Notifications on card assignment
- [ ] Board/card filtering by label and priority

### V2

- [ ] Activity history on cards
- [ ] File attachments (VichUploaderBundle)
- [ ] Full-text search
- [ ] CSV/PDF export
- [ ] REST API

## License

Proprietary.