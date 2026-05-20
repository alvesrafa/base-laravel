# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

This is a **Laravel 12 base template** (PHP 8.2+) for Luby/Assefaz projects. It is not a finished application — it is a starting point that must be configured for one of three frontend paths before development begins: **Filament**, **Inertia.js**, or **API-only**. The current default includes Livewire Flux and Volt but these may be replaced depending on the chosen path.

## Commands

```bash
# Start local environment (Sail)
./vendor/bin/sail up -d

# Run all tests
./vendor/bin/sail artisan test
# or via composer
composer test

# Run a single test file
./vendor/bin/sail artisan test tests/Feature/ExampleTest.php

# Run a specific test by name
./vendor/bin/sail artisan test --filter="test name here"

# Lint (PHP Pint)
./vendor/bin/sail vendor/bin/pint

# Full local dev server (serves + queue + logs + vite in parallel)
composer dev

# Clear all caches
./vendor/bin/sail artisan optimize:clear
```

## Architecture & Development Patterns

These patterns are mandatory regardless of which frontend path is chosen.

### Controller (thin)
Controllers only receive the request, delegate to a service or action, and return a response. No business logic lives here.

```php
public function store(StoreUserRequest $request): JsonResponse
{
    $data = $this->userService->create($request->toDto());
    return response()->json(['data' => $data, 'message' => 'Created.', 'errors' => null], 201);
}
```

### Standardized response format
All API responses must follow `{ data, message, errors }` with the correct HTTP status code.

### Validation via Form Request
Never validate inline in the controller. Always create a dedicated `App\Http\Requests\*Request` class with typed rules.

### DTOs (Data Transfer Objects)
Use typed DTOs for both input (from Form Request) and output (before returning). The Form Request should expose a `toDto()` method. No raw arrays passed between layers.

### Repository pattern
Always define an interface and a concrete implementation:

```
App\Repositories\Contracts\UserRepositoryInterface
App\Repositories\Eloquent\UserRepository
```

Bind the interface to the implementation in `AppServiceProvider::register()`:

```php
$this->app->bind(UserRepositoryInterface::class, UserRepository::class);
```

### Models
Models are only created when there is direct persistence. Keep them lean: casts, fillable, relationships. No business logic.

### Tests
Every feature must have tests. Use Pest (already configured). Feature tests live in `tests/Feature/`, unit tests in `tests/Unit/`. The test environment uses `array` cache/session/mail drivers and `sync` queue by default (`phpunit.xml`).

## Middleware

`ValidateApiRequestHost` is prepended to all API routes. It checks both the `Host` header and `Origin` header against `APP_API_ALLOWED_HOSTS` (comma-separated env variable). Configure this before exposing any API route.

## Frontend Paths

Choose exactly one path per project and document the choice here.

| Path | When to use |
|---|---|
| **Filament** | Admin panels, CRUDs, dashboards |
| **Inertia.js (Vue or React)** | Rich SPAs backed by Laravel |
| **API-only** | Mobile backends, external SPA, integrations |

See `README.md` for the full setup steps for each path, including which packages to install or remove.

### Filament conventions
```
app/Filament/
    Resources/{Name}Resource/
        Pages/
        Schemas/
        Tables/
    Pages/
    Widgets/
```
Add `@php artisan filament:upgrade` to `post-autoload-dump` in `composer.json`. Implement `FilamentUser` on the `User` model.

### Inertia conventions
Pages live in `resources/js/Pages/`. Use `Inertia::render('PageName')` in routes. Register `HandleInertiaRequests` middleware in `bootstrap/app.php`.

### API-only conventions
Remove `livewire/flux` and `livewire/volt`. Keep only `routes/api.php` and `routes/console.php` in `bootstrap/app.php`. Use Sanctum token auth.

## Infrastructure

### Environments
- `homolog.env` — variables for homologation; used by CI to generate `.env` in the container
- `prod.env` — variables for production

### CI/CD (GitLab)
Pipelines run on pushes to `development`, `homolog`, or `production` branches and on MRs targeting `development` or `homolog`. The pipeline stages are: `quality → test → docker-build → deploy`.

Docker images are tagged with the first 8 chars of the commit SHA and pushed to Azure Container Registry (ACR). Deploy applies Kubernetes manifests from `.gitlab/homolog/` or `.gitlab/production/`.

### Kubernetes manifests
Before deploying, replace these placeholders in `.gitlab/homolog/` and `.gitlab/production/`:

| Placeholder | Example |
|---|---|
| `APP-NAME` | `meu-sistema` |
| `APP-NAMESPACE-HML` | `minha-equipe-hml` |
| `APP-NAMESPACE-PRD` | `minha-equipe-prd` |

For additional queue workers, add `4_deployment-worker-*.yaml` files following the pattern of `3_deployment-worker.yaml`.

### Required GitLab CI variables
`ENV_HML`, `ENV_PRODUCTION`, `AZURE_ACR_HML_NAME`, `AZURE_ACR_PROD_NAME`, `AZURE_ACR_REPOSITORY_NAME`, `AZURE_CLIENT_ID`, `AZURE_CLIENT_SECRET`, `AZURE_TENANT_ID`, and (if using Filament/Flux) `FLUX_USERNAME`, `FLUX_LICENSE_KEY`.

## Global Helpers (`app/Helpers/helpers.php`)

`maskPhone(string)` — formats 10 or 11-digit phone strings to `(XX) XXXXX-XXXX`.  
`maskCep(string)` — formats 8-digit CEP strings to `XXXXX-XXX`.
