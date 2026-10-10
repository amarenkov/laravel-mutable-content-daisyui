# amarenkov/laravel-mutable-content-daisyui

[![tests](https://github.com/amarenkov/laravel-mutable-content-daisyui/actions/workflows/tests.yml/badge.svg)](https://github.com/amarenkov/laravel-mutable-content-daisyui/actions/workflows/tests.yml)
[![Packagist](https://img.shields.io/packagist/v/amarenkov/laravel-mutable-content-daisyui)](https://packagist.org/packages/amarenkov/laravel-mutable-content-daisyui)

Blade components on [daisyUI](https://daisyui.com) for
[`amarenkov/laravel-mutable-content`](https://github.com/amarenkov/laravel-mutable-content).

Server-rendered screens for models with mutable fields: forms, detail views and tables built from
field definitions, with a minimal amount of JavaScript.

> Work in progress. Nothing is released yet.

## Stack

- Laravel 13 and Blade components
- Tailwind CSS 4 and daisyUI 5
- Livewire for server-driven interactivity, Alpine.js for small client-side behaviour

## Design principles

- **daisyUI is the only UI dependency.** It is a pure CSS plugin for Tailwind CSS: no runtime
  JavaScript and no transitive dependencies. No other UI kits or JavaScript widget libraries.
- **daisyUI markup lives in the package's own Blade components** (button, field, table, modal
  and so on). Screens use these components, not daisyUI classes directly, so a daisyUI major
  upgrade or a switch to another CSS kit touches one place.
- **Native HTML first.** Modals use `<dialog>`, dropdowns use `popover` or `<details>`, dates use
  `<input type="date">`, suggestions use `<datalist>`. Where native elements are not enough,
  a small Alpine.js component (Alpine ships with Livewire) instead of a third-party widget.
- **The host app pins daisyUI to one major version** (`"daisyui": "^5"`). Majors may rename
  classes, so they are upgraded deliberately, together with this package.

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
