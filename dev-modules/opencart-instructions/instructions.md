---
name: opencart-instructions
description: "Agent instructions: OpenCart development workflow, scope separation, and code conventions."
applyTo: dev-modules/**
---

## Purpose
Clear, unambiguous rules for interacting with the OpenCart codebase in this repository. Used by AI agents as a persistent instruction set.

## Scope of Application
- Applies to requests involving module development, custom site/theme tweaks, localization, and extensions.
- Scope separation:
  1. Reusable module logic: ONLY in `dev-modules/<module_name>/upload/`.
  2. Site-specific theme tweaks: permitted in `catalog/view/theme/<theme_name>/` and project stylesheets.
  3. OpenCart Core: direct edits to core files are prohibited without explicit user approval.

## Key Rules
- Core Modifications: Prohibited. Prefer the native Event System or targeted OCMOD instead of core edits.
- Module Workspace: Always maintain the installable tree within `dev-modules/<module>/upload/`.
- OCMOD: If modifying standard controller/template behavior without events, author `install.xml` in the module. Validate syntax with `php .agents/skills/ocmod/scripts/validate_ocmod.php`.
- Architecture: OpenCart follows MVC-L (controllers, models, views + languages). Explicitly specify target files: `admin/controller/...`, `catalog/controller/...`, `admin/view/template/...`, `catalog/view/theme/...`, `admin/language/...`.
- Events: Use the OpenCart 3 Event System for action hooks and view data injection (refer to skill `opencart-events`).
- Languages: Add translation keys to language arrays in both `en-gb` and `ru-ru`.

## Technical Summary
- Controllers: `admin/controller/` and `catalog/controller/` — request handling and business logic.
- Models: `model/` — database queries via `$this->db`.
- Views: `view/template/` (admin) and `view/theme/<theme>/template/` (storefront) — Twig templates.
- Languages: `language/<lang>/...` — interface strings.
- Modifications: OCMOD modifiers are compiled into `system/storage/modification/`.

## Standard Module Layout (`dev-modules/<module>/upload`)
- `upload/admin/controller/extension/module/<module>.php`
- `upload/catalog/controller/extension/module/<module>.php`
- `upload/admin/language/en-gb/extension/module/<module>.php`
- `upload/catalog/language/en-gb/extension/module/<module>.php`
- `upload/admin/view/template/extension/module/<module>.twig`
- `upload/catalog/view/theme/default/template/extension/module/<module>.twig`
- `install.xml` (if core modification is necessary)
