# Laravel Template

Template base Laravel 12 com Docker, CI/CD GitLab e deploy no Kubernetes (Azure AKS).

## O que está incluído

- **Laravel 12** + PHP 8.2+
- **Docker** — Nginx + PHP-FPM + Supervisor (pronto para produção)
- **Laravel Sail** — ambiente local via Docker Compose
- **GitLab CI/CD** — pipeline com build de imagem Docker e deploy no AKS
- **Kubernetes** — manifests para homolog e produção com HPA e workers
- **Middleware `ValidateApiRequestHost`** — controle de hosts permitidos na API
- **HTTPS forçado** em produção e homologação via `AppServiceProvider`
- **Sanctum** — autenticação de API por tokens
- **Tailwind CSS + Vite**
- **Pest** — testes

---

## Setup inicial (todos os caminhos)

### 1. Configurar o ambiente

```bash
cp .env.example .env
```

### 2. Iniciar com Sail (Docker local)

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs

./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

### 3. Assets frontend

```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

---

## Caminhos disponíveis

Escolha **um** dos três caminhos abaixo.

---

## Caminho A — Filament (painel admin)

Ideal para sistemas administrativos com CRUD, importações, dashboards.

### 1. Instalar o Filament

```bash
./vendor/bin/sail composer require filament/filament:"^4.0"
./vendor/bin/sail artisan filament:install --panels
```

### 2. Adicionar o script de upgrade no `composer.json`

Em `scripts > post-autoload-dump`, adicionar:

```json
"@php artisan filament:upgrade"
```

### 3. Registrar o provider em `bootstrap/providers.php`

```php
App\Providers\Filament\AdminPanelProvider::class,
```

### 4. Implementar a interface no `User` model

```php
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    public function canAccessPanel(Panel $panel): bool
    {
        return true; // ou: $this->hasRole('admin')
    }
}
```

### 5. Criar o primeiro usuário admin

```bash
./vendor/bin/sail artisan make:filament-user
```

### 6. (Opcional) Pacotes recomendados

```bash
# Filtro de data range
./vendor/bin/sail composer require malzariey/filament-daterangepicker-filter:"^4.0"

# Log de atividades
./vendor/bin/sail composer require spatie/laravel-activitylog:"^4.10"
./vendor/bin/sail artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
./vendor/bin/sail artisan migrate
```

### CSS com Flux (UI do Filament)

Adicionar em `resources/css/app.css`:

```css
@import '../../vendor/livewire/flux/dist/flux.css';
```

### Estrutura recomendada de Resources

```
app/Filament/
    Resources/
        UserResource/
            Pages/
            Schemas/
            Tables/
    Pages/
    Widgets/
```

---

## Caminho B — Inertia.js (SPA com Vue ou React)

Ideal para aplicações com frontend rico, mantendo o backend Laravel como servidor de páginas.

### 1. Instalar o Inertia no backend

```bash
./vendor/bin/sail composer require inertiajs/inertia-laravel
./vendor/bin/sail artisan inertia:middleware
```

Registrar o middleware em `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\HandleInertiaRequests::class,
    ]);
    // ...
})
```

### 2. Criar o layout Blade raiz

```bash
mkdir -p resources/views
```

Criar `resources/views/app.blade.php`:

```html
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @viteReactRefresh {{-- remover se usar Vue --}}
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
```

### 3a. Com Vue 3

```bash
./vendor/bin/sail npm install @inertiajs/vue3 vue @vitejs/plugin-vue
```

Atualizar `vite.config.js`:

```js
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({ input: ['resources/css/app.css', 'resources/js/app.js'], refresh: true }),
        tailwindcss(),
        vue(),
    ],
});
```

Atualizar `resources/js/app.js`:

```js
import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

createInertiaApp({
    resolve: name => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
});
```

### 3b. Com React

```bash
./vendor/bin/sail npm install @inertiajs/react react react-dom @vitejs/plugin-react
```

Atualizar `vite.config.js`:

```js
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({ input: ['resources/css/app.css', 'resources/js/app.jsx'], refresh: true }),
        tailwindcss(),
        react(),
    ],
});
```

Renomear `app.js` para `app.jsx` e usar:

```jsx
import './bootstrap';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

createInertiaApp({
    resolve: name => resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx')),
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
});
```

### 4. Criar uma página de exemplo

```bash
mkdir -p resources/js/Pages
```

`resources/js/Pages/Home.vue` (Vue) ou `Home.jsx` (React).

### 5. Adicionar rota em `routes/web.php`

```php
use Inertia\Inertia;

Route::get('/', fn() => Inertia::render('Home'));
```

---

## Caminho C — API pura (sem frontend)

Ideal para backends que servem mobile, SPAs externas ou integrações.

### 1. Remover dependências de frontend

```bash
./vendor/bin/sail composer remove livewire/flux livewire/volt
```

Remover do `composer.json` a entrada `"app/Helpers/helpers.php"` em `autoload.files` se não precisar das funções utilitárias.

### 2. Simplificar `bootstrap/app.php`

Remover o `withRouting` de `web` se não houver rotas web, ou manter apenas a rota `/up` de health check:

```php
->withRouting(
    api: __DIR__ . '/../routes/api.php',
    commands: __DIR__ . '/../routes/console.php',
    health: '/up',
)
```

### 3. Configurar Sanctum para token de API

Em `routes/api.php`:

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
```

Publicar config do Sanctum se necessário:

```bash
./vendor/bin/sail artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

### 4. Controle de hosts permitidos

O middleware `ValidateApiRequestHost` já está ativo para todas as rotas de API.
Configure `APP_API_ALLOWED_HOSTS` no `.env` com os domínios separados por vírgula:

```env
APP_API_ALLOWED_HOSTS=app.example.com,api.example.com
```

### 5. (Opcional) Documentação com Swagger

```bash
./vendor/bin/sail composer require darkaonline/l5-swagger:"^9.0"
./vendor/bin/sail artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
```

---

## Configuração CI/CD e Deploy

### Substituir os placeholders nos manifests Kubernetes

Nos arquivos dentro de `.gitlab/homolog/` e `.gitlab/production/`, substituir:

| Placeholder | Valor |
|---|---|
| `APP-NAME` | nome do app em kebab-case, ex: `meu-sistema` |
| `APP-NAMESPACE-HML` | namespace K8s de homologação, ex: `minha-equipe-hml` |
| `APP-NAMESPACE-PRD` | namespace K8s de produção, ex: `minha-equipe-prd` |

### Preencher os ambientes

- `homolog.env` — variáveis para o ambiente de homologação
- `prod.env` — variáveis para produção

Esses arquivos são usados pelo CI/CD para gerar o `.env` dentro do container.

### Variáveis necessárias no GitLab CI/CD

| Variável | Descrição |
|---|---|
| `ENV_HML` | conteúdo do `homolog.env` |
| `ENV_PRODUCTION` | conteúdo do `prod.env` |
| `AZURE_ACR_HML_NAME` | nome do ACR de homologação |
| `AZURE_ACR_PROD_NAME` | nome do ACR de produção |
| `AZURE_ACR_REPOSITORY_NAME` | nome do repositório de imagem |
| `AZURE_CLIENT_ID` | service principal Azure |
| `AZURE_CLIENT_SECRET` | secret do service principal |
| `AZURE_TENANT_ID` | tenant Azure |
| `FLUX_USERNAME` | usuário Flux UI (se usar Filament/Flux) |
| `FLUX_LICENSE_KEY` | licença Flux UI (se usar Filament/Flux) |

### Workers adicionais

Se a aplicação tiver filas com volumes distintos, adicionar arquivos `4_deployment-worker-*.yaml` seguindo o padrão do `3_deployment-worker.yaml`, ajustando `--queue=nome-da-fila`.

---

## Comandos úteis

```bash
# Rodar testes
./vendor/bin/sail artisan test

# Lint (PHP Pint)
./vendor/bin/sail vendor/bin/pint

# Gerar chave
./vendor/bin/sail artisan key:generate

# Limpar caches
./vendor/bin/sail artisan optimize:clear
```
