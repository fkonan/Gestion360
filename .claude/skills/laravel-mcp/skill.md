---
name: laravel-mcp
description: MCP (Model Context Protocol) integration for Laravel 12+. Setup Laravel Boost, configure AI tooling, and create custom MCP servers.
keywords: mcp, ai, boost, model context protocol, ai tooling
---

# Laravel MCP Integration Skill

## What is MCP?

Model Context Protocol (MCP) allows AI assistants like Claude to interact with your Laravel application through standardized tools and resources.

## Laravel Boost (Official)

Laravel Boost is the official MCP server from Laravel, providing:

- **Database Schema Access** - Claude sees your table structures
- **Route Discovery** - All available routes
- **Documentation Search** - 17,000+ Laravel docs indexed
- **Tinker Integration** - Execute code in context
- **Log Access** - View recent errors
- **Model Information** - Understand your Eloquent models

---

## Installation

### 1. Install Laravel Boost

```bash
composer require laravel/boost --dev
```

### 2. Run Installation

```bash
php artisan boost:install
```

This will:
- Generate a CLAUDE.md file with AI guidelines
- Configure MCP server
- Set up auto-discovery for your project

### 3. Update Guidelines

```bash
php artisan boost:update
```

Run this after major changes to regenerate guidelines.

---

## Configuration

### Claude Code MCP Config

Create `.claude/mcp.json` in your project:

```json
{
  "mcpServers": {
    "laravel-boost": {
      "command": "php",
      "args": ["artisan", "boost:serve"],
      "cwd": "${workspaceFolder}"
    }
  }
}
```

### Project CLAUDE.md

Laravel Boost auto-generates this. You can extend it:

```markdown
# Project: My Laravel App

## Stack
- Laravel 12
- PHP 8.3
- MySQL 8
- Redis

## Custom Guidelines
- Use Actions pattern for business logic
- All API responses use Resources
- Tests required for all features
```

---

## Custom Guidelines

### Add Project-Specific Rules

Create files in `.ai/guidelines/`:

```
.ai/
└── guidelines/
    ├── coding-standards.md
    ├── api-conventions.md
    └── testing-rules.blade.php
```

Example `coding-standards.md`:

```markdown
# Coding Standards

## Actions
- One action per file
- Naming: VerbNoun (CreateUser, UpdateProduct)
- Always use DTOs for input

## Controllers
- Max 20 lines per method
- No business logic
- Always return Resources

## Testing
- Minimum 80% coverage
- Use Pest describe/it syntax
- Mock external services
```

---

## Tools Provided by Boost

### Database Schema

Claude can query:
- Table structures
- Column types
- Relationships
- Indexes

### Route Information

```php
// Claude knows about:
Route::apiResource('users', UserController::class);
Route::get('/dashboard', DashboardController::class);
```

### Model Details

```php
// Claude understands:
class User extends Model
{
    protected $fillable = ['name', 'email'];
    protected $casts = ['role' => UserRole::class];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
```

### Tinker Access

Claude can execute:
```php
User::count();
User::where('role', 'admin')->get();
```

### Log Access

Claude can see recent errors:
```
[2024-01-15 10:30:00] local.ERROR: SQLSTATE[23000]...
```

---

## Creating Custom MCP Servers

### Using laravel/mcp Package

```bash
composer require laravel/mcp
```

### Define a Tool

```php
<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Tool;
use Laravel\Mcp\ToolResult;

class SearchProducts extends Tool
{
    public string $name = 'search_products';
    public string $description = 'Search products by name or category';

    public function parameters(): array
    {
        return [
            'query' => [
                'type' => 'string',
                'description' => 'Search query',
                'required' => true,
            ],
            'category' => [
                'type' => 'string',
                'description' => 'Filter by category',
                'required' => false,
            ],
        ];
    }

    public function execute(array $params): ToolResult
    {
        $products = Product::query()
            ->when($params['query'], fn($q) => $q->where('name', 'like', "%{$params['query']}%"))
            ->when($params['category'] ?? null, fn($q) => $q->where('category', $params['category']))
            ->limit(10)
            ->get();

        return ToolResult::success($products->toArray());
    }
}
```

### Define a Resource

```php
<?php

namespace App\Mcp\Resources;

use Laravel\Mcp\Resource;

class ProductCatalog extends Resource
{
    public string $name = 'product_catalog';
    public string $description = 'Current product catalog with prices';
    public string $uri = 'resource://products/catalog';
    public string $mimeType = 'application/json';

    public function content(): string
    {
        return Product::all()->toJson();
    }
}
```

### Register in Service Provider

```php
<?php

namespace App\Providers;

use App\Mcp\Tools\SearchProducts;
use App\Mcp\Resources\ProductCatalog;
use Laravel\Mcp\Facades\Mcp;

class McpServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Mcp::tool(SearchProducts::class);
        Mcp::resource(ProductCatalog::class);
    }
}
```

---

## Security

### Authentication

Laravel Boost supports:
- OAuth 2.1
- Laravel Sanctum

```php
// config/boost.php
return [
    'auth' => [
        'driver' => 'sanctum',
        'guard' => 'api',
    ],
];
```

### Read-Only Mode

For safety, enable read-only:

```php
return [
    'database' => [
        'read_only' => true, // Only SELECT queries
    ],
];
```

### Excluded Tables

```php
return [
    'database' => [
        'excluded_tables' => [
            'password_resets',
            'personal_access_tokens',
            'failed_jobs',
        ],
    ],
];
```

---

## Best Practices

### 1. Keep CLAUDE.md Updated

```bash
# After major changes
php artisan boost:update
```

### 2. Add Custom Guidelines

Document project-specific patterns in `.ai/guidelines/`.

### 3. Use Read-Only in Production

Never allow write access to production databases.

### 4. Monitor Tool Usage

Log MCP tool invocations:

```php
Mcp::tool(SearchProducts::class)
    ->middleware(LogToolUsage::class);
```

### 5. Test Your Tools

```php
it('searches products', function () {
    Product::factory()->create(['name' => 'Laravel Book']);

    $tool = new SearchProducts();
    $result = $tool->execute(['query' => 'Laravel']);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->data)->toHaveCount(1);
});
```

---

## Troubleshooting

### Boost Not Connecting

```bash
# Verify installation
php artisan boost:status

# Check MCP server
php artisan boost:serve --debug
```

### Schema Not Visible

```bash
# Refresh schema cache
php artisan boost:refresh
```

### Tools Not Available

```bash
# List registered tools
php artisan mcp:tools
```

---

## Links

- [Laravel Boost](https://github.com/laravel/boost)
- [Laravel MCP](https://github.com/laravel/mcp)
- [MCP Specification](https://spec.modelcontextprotocol.io/)
- [Laravel AI Docs](https://laravel.com/docs/12.x/ai)
