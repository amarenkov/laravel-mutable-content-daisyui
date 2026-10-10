# amarenkov/laravel-mutable-content-daisyui

[![tests](https://github.com/amarenkov/laravel-mutable-content-daisyui/actions/workflows/tests.yml/badge.svg)](https://github.com/amarenkov/laravel-mutable-content-daisyui/actions/workflows/tests.yml)
[![Packagist](https://img.shields.io/packagist/v/amarenkov/laravel-mutable-content-daisyui)](https://packagist.org/packages/amarenkov/laravel-mutable-content-daisyui)

Blade components on [daisyUI](https://daisyui.com) for
[`amarenkov/laravel-mutable-content`](https://github.com/amarenkov/laravel-mutable-content).

Server-rendered screens for models with mutable fields: forms, detail views and tables built from
field definitions, with a minimal amount of JavaScript.

> Work in progress. Nothing is released yet: the screens for fields and their usage are coming.

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
- Livewire 4, Heroicons through `blade-ui-kit/blade-heroicons`

## Installation

```bash
composer require amarenkov/laravel-mutable-content-daisyui
npm i -D daisyui@5
```

The package views are styled by the application build. Add daisyUI and the package sources to
`resources/css/app.css`:

```css
@import 'tailwindcss';

@plugin 'daisyui';

@source '../../vendor/amarenkov/laravel-mutable-content-daisyui/resources/views';
@source '../../vendor/amarenkov/laravel-mutable-content-daisyui/src';
```

## Management screens

Screens for lists of values (LOVs) and their items. The application registers them inside its own
route group, with its middleware and prefix but without a name prefix:

```php
use Amarenkov\MutableContentDaisyUi\MutableContentDaisyUi;

Route::middleware('auth')->prefix('settings')->group(function () {
    MutableContentDaisyUi::routes();
});
```

Route names are `MutableContentDaisyUi::ROUTE_LOVS` and `MutableContentDaisyUi::ROUTE_LOV_ITEMS`.
The screens are full-page Livewire components rendered in the `layouts::app` layout, which gets
the page title as `$title`. Another layout is set in the config
(`php artisan vendor:publish --tag=mutable-content-daisyui-config`) or with
`MUTABLE_CONTENT_DAISYUI_LAYOUT`.

Every change made on the screens lands in the change log with the user and the page path.
System records cannot be deleted, a LOV used in fields cannot be deleted or have its code
changed, and items can be added as a list of labels.

## Building screens

Extend `Livewire\ManageRecords` and set the model: the table (search, sorting, filters,
pagination, column toggles), the create and edit modal and deletion are built from the field
definitions.

```php
use Amarenkov\MutableContentDaisyUi\Livewire\ManageRecords;

class ManageProjects extends ManageRecords
{
    protected static string $model = Project::class;

    protected function inputs(): array
    {
        $inputs = parent::inputs();

        $inputs['status']->live();

        return $inputs;
    }
}
```

`columns()`, `tableFilters()` and `inputs()` return arrays keyed by field code, so a single
column, filter or input is adjusted without rebuilding the list. A model refusing a save or
a deletion with a `DomainException`, or a unique constraint violation, is shown to the user as
a notification and the form stays open.

## Components

Screens are built from the package Blade components, e.g. `<x-mutable-content-daisyui::button>`,
`::modal`, `::field`, `::input`, `::select`, `::textarea`, `::toggle`, `::checkbox`,
`::dropdown`, `::card`, `::table`, `::breadcrumbs`, `::notifications`. Notifications are sent
with the `mutable-content-notify` browser event (`type`, `title`, `body`).

## Translations

UI strings are in `lang/{en,ru}/ui.php` under the `mutable-content-daisyui` namespace. Publish
them to override:

```bash
php artisan vendor:publish --tag=mutable-content-daisyui-lang
```

## License

MIT. See [LICENSE](LICENSE).
