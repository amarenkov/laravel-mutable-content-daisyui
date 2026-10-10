# Changelog

All notable changes to this package are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this package adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.3.1] - 2026-10-10

### Added

- `datetime` fields: a date and time input in the application timezone, shown as `d.m.Y H:i`; a field type icon.

### Changed

- Requires `amarenkov/laravel-mutable-content` ^0.7.2.

## [0.3.0] - 2026-10-10

### Added

- `MutableContentDaisyUi::navigation()` and the `navigation` component: menu items of the management screens under the "System settings" group.
- The delete confirmation names the record (`recordTitle()`, the scope for a field usage).

### Changed

- Requires `amarenkov/laravel-mutable-content` ^0.7.
- A field usage is bound to a class and optionally to one of its types (core 0.7 class types) instead of a class or a LOV: the type select lists the types of the chosen class, the LOV items are the `Item` class typed by LOV. Usage titles, the usage column and filter show the type, quick filters on the fields screen have a group per typed class.

### Fixed

- A required input with an object validation rule no longer fails when its rules are built.

## [0.2.0] - 2026-10-10

### Changed

- Requires `amarenkov/laravel-mutable-content` ^0.6.
- Numbers are formatted with the separators of the current locale (core `NumberHelper`).

### Fixed

- Object reference columns showed the id instead of the object title.

## [0.1.0] - 2026-10-10

Initial release.

### Added

- `ManageRecords` Livewire page: a table with search, sorting, filters, pagination and column
  toggles, create and edit in a modal and deletion, all built from the field definitions;
  saves go to the change log with the user and the page path.
- Management screens for fields and their usage, with field type settings and quick filters.
- Management screens for LOVs and their items, with adding items as a list of labels.
- Combobox for selects: search in the browser or on the server, option icons, keyboard
  navigation, a popover list that is not clipped by modals.
- Blade components on daisyUI 5: button, modal, field, input, select, textarea, toggle,
  checkbox, combobox, dropdown, collapse, badge, card, table, breadcrumbs, notifications.
- Lucide icons: the interface, field type icons kept in the package (an icon set for the item in
  the admin panel takes precedence) and icon fields storing Lucide icon names.

[Unreleased]: https://github.com/amarenkov/laravel-mutable-content-daisyui/compare/v0.3.1...HEAD
[0.3.1]: https://github.com/amarenkov/laravel-mutable-content-daisyui/compare/v0.3.0...v0.3.1
[0.3.0]: https://github.com/amarenkov/laravel-mutable-content-daisyui/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/amarenkov/laravel-mutable-content-daisyui/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/amarenkov/laravel-mutable-content-daisyui/releases/tag/v0.1.0
