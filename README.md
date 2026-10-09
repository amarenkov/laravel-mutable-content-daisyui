# amarenkov/laravel-mutable-content-daisyui

[![tests](https://github.com/amarenkov/laravel-mutable-content-daisyui/actions/workflows/tests.yml/badge.svg)](https://github.com/amarenkov/laravel-mutable-content-daisyui/actions/workflows/tests.yml)

Blade components on [daisyUI](https://daisyui.com) for
[`amarenkov/laravel-mutable-content`](https://github.com/amarenkov/laravel-mutable-content).

Server-rendered screens for models with mutable fields: forms, detail views and tables built from
field definitions, with a minimal amount of JavaScript.

> Work in progress. Nothing is released yet.

## Stack

- Laravel 13 and Blade components
- Tailwind CSS 4 and daisyUI 5
- Livewire for server-driven interactivity, Alpine.js for small client-side behaviour

## Requirements

- PHP 8.4
- `amarenkov/laravel-mutable-content`

## Installation

```bash
composer require amarenkov/laravel-mutable-content-daisyui
```

## Translations

UI strings are in `lang/{en,ru}/ui.php` under the `mutable-content-daisyui` namespace. Publish
them to override:

```bash
php artisan vendor:publish --tag=mutable-content-daisyui-lang
```

## License

MIT. See [LICENSE](LICENSE).
