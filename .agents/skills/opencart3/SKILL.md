---
name: opencart-3-workflow
description: "Practical guide: OpenCart 3 architecture, MVC-L workflow, and modification management."
---

# OpenCart 3 Developer Navigator

Concise, reproducible workflow for developing, debugging, and deploying extensions in OpenCart 3.

---

## 1. Structure & Architecture

* `catalog/` — Client-facing storefront.
* `admin/` — Back-office administrative interface.
* `system/` — Core framework, engine, libraries, storage, modifications, config.
* Architecture: **MVC-L**
  * Controllers: `controller/*` (business logic, request handling).
  * Models: `model/*` (database interactions).
  * Views: `view/*` (Twig templates in OC3).
  * Languages: `language/*` (UI translation arrays).

---

## 2. Where to Edit Source Code

* Module development happens strictly in `dev-modules/<module_name>/upload/`.
* The `upload/` folder mirrors the OpenCart directory tree (`admin/`, `catalog/`, `system/`).
* DO NOT edit files directly in OpenCart root for module functionality.
* Use OCM CLI (`ocm install` or `ocm dev`) to synchronize changes into the runtime.

---

## 3. Core Libraries (`system/library/`)

* `system/library/` houses core framework classes.
* Avoid modifying existing core classes.
* For custom module libraries, place them inside your module:
  `dev-modules/<module_name>/upload/system/library/<vendor>/<package>/...`
  and load via `$this->load->library(...)` or standard PSR-4 autoloading.

---

## 4. Quality Control & Checklist

1. Verify `git status`: Ensure changed files reside only in `dev-modules/<module_name>/` (unless deliberate site theme customizations were requested).
2. Validate module integrity:
   ```bash
   php .agents/skills/opencart-module-scaffolding/scripts/check_module.php dev-modules/<module_name>
   ```
3. Validate OCMOD if `install.xml` is used:
   ```bash
   php .agents/skills/ocmod/scripts/validate_ocmod.php dev-modules/<module_name>/install.xml
   ```
4. Test lifecycle actions: install, edit settings, view frontend display, uninstall.
