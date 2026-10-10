# Changelog

All notable changes to this package are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this package adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/amarenkov/laravel-mutable-content-daisyui/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/amarenkov/laravel-mutable-content-daisyui/releases/tag/v0.1.0
