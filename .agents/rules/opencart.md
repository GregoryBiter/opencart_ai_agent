---
name: opencart-change-rules
description: "Rules for OpenCart 3.0.3.7+ (and ocStore 3.0.3.7+): determining where to make changes (module in dev-modules vs site-specific theme vs OpenCart core)."
applyTo:
  - "**"
---

# OpenCart Change Rules (OpenCart 3.0.3.7+)

> [!IMPORTANT]
> This specification applies to **OpenCart 3.0.3.7 and higher** (including **ocStore 3.0.3.7+**).

Before creating or modifying files, determine the scope of the task:

1. Module functionality;
2. Site-specific theme, styling, or custom integration;
3. OpenCart core.

Choose where to apply changes based on this distinction.

---

## 1. Module Development and Maintenance

If the task relates to module functionality, ALL changes must be made ONLY in:

```text
dev-modules/<module_name>/upload/
```

This applies to:
* Creating a new module;
* Fixing module bugs;
* Expanding module features;
* Modifying controllers, models, language files, and templates of the module;
* Adding events, libraries, or other files belonging to the module;
* Changing reusable module behavior that must carry over to other sites;
* Updating an existing module regardless of where its installed copy resides.

The `upload` directory structure must strictly mirror the OpenCart directory tree:

```text
dev-modules/<module_name>/upload/admin/
dev-modules/<module_name>/upload/catalog/
dev-modules/<module_name>/upload/system/
dev-modules/<module_name>/upload/extension/
```

Example:
```text
dev-modules/example_module/upload/admin/controller/extension/module/example.php
dev-modules/example_module/upload/catalog/model/extension/module/example.php
dev-modules/example_module/upload/catalog/view/theme/default/template/extension/module/example.twig
```

NEVER modify the installed copy of the module directly in the site root if the change belongs to the module itself.

Incorrect:
```text
catalog/controller/extension/module/example.php
```

Correct:
```text
dev-modules/example_module/upload/catalog/controller/extension/module/example.php
```

After modifying module files, apply them to OpenCart using OCM CLI from the module folder:
```bash
ocm install
```
or run live watch mode:
```bash
ocm dev
```

---

## 2. Site-Specific Work

If the task applies specifically to the current site as an individual project, modifying files directly in the OpenCart root is permitted.

This applies to:
* Site design changes;
* Custom theme modifications;
* Tweaking Twig templates of a specific theme;
* Custom CSS, JavaScript, images, and frontend components;
* Page layout and structure adjustments;
* Site-only UI elements;
* Adding a module button or block into a custom site theme;
* Integrating a module with the site's unique theme;
* Module display adjustments needed only for the current theme;
* Site-specific features that are not reusable standalone modules;
* Configuration or glue code unique to this project.

Example: If a module button needs to be added into a custom site theme and is only needed on this site, edit the theme file directly in the root:
```text
catalog/view/theme/<theme_name>/template/...
```

Meanwhile, keep the reusable module logic in:
```text
dev-modules/<module_name>/upload/
```

Thus, a task may span two places simultaneously:
- `dev-modules/<module_name>/upload/` for module business logic;
- `catalog/view/theme/<theme_name>/` for integrating that logic into the specific site.

---

## 3. Determining Change Scope

Apply changes to `dev-modules/<module_name>/upload` if ANY of the following apply:
* Without this change, the module malfunctions;
* The change fixes a module bug;
* The change extends module capabilities;
* The change must persist when installing the module on another site;
* The change affects module controllers, models, settings, or data;
* The change must survive running `ocm install`;
* The change logically belongs to the module.

Apply changes directly to the site root if:
* The change is only needed for the current project;
* The change is tied to a specific installed theme;
* The change should not be distributed with the module;
* The change only alters visual appearance or layout on the current site;
* The change binds the module to the site's unique structure or design;
* The change is not essential to standalone module operation.

---

## 4. Rules for the OpenCart Root

Browsing the OpenCart root is permitted for:
* Studying the existing implementation;
* Finding hook/connection points;
* Analyzing controllers, models, and templates;
* Checking installed module versions;
* Comparing site files with files in `dev-modules`;
* Inspecting custom theme structures;
* Verifying results after running `ocm install`.

Do NOT directly edit in the OpenCart root:
* Module files if the change belongs to the module;
* System core files;
* Standard OpenCart controllers and models without explicit need;
* The installed copy of a module instead of its source in `dev-modules`.

Permitted to edit in root:
* Files of the custom theme (`catalog/view/theme/<theme>/`);
* Site-specific CSS and JavaScript;
* Project-specific templates, blocks, and layout;
* Integration glue code between a module and a custom theme.

---

## 5. OpenCart Core

Modifying OpenCart core files is FORBIDDEN by default.

Core files include general system files not belonging to a specific module or theme:
```text
system/engine/
system/library/
system/framework.php
catalog/controller/startup/
admin/controller/startup/
```

Modifying core files requires EXPLICIT user permission.

If a task seems to require core changes:
1. Determine if it can be solved via an Event, OCMOD, or module extension;
2. Prefer Events or OCMOD over core edits;
3. If no safe alternative exists, inform the user which core file is needed and why;
4. Do NOT apply core edits without explicit user approval.

---

## 6. OCMOD & Modifications

Compiled modification files reside in:
```text
system/storage/modification/
```

This directory is strictly READ-ONLY for:
* Inspecting compiled code;
* Verifying modifier application;
* Troubleshooting OCMOD conflicts;
* Finding root causes of errors;
* Comparing original and modified files.

NEVER edit files directly in `system/storage/modification/`. All fixes must be made in the module source (`install.xml` or `upload/system/`), followed by a cache refresh (`ocm ocmod:refresh` or admin panel).

---

## 7. No Change Duplication

Never maintain the same change simultaneously in:
* `dev-modules/<module_name>/upload`;
* The installed copy in OpenCart root;
* `system/storage/modification`.

Source of truth for any module is:
```text
dev-modules/<module_name>/
```

---

## 8. Summary Rules

```text
Module logic & features       → dev-modules/<module_name>/upload
Site-specific customization   → OpenCart root (theme/styles)
Custom theme design           → catalog/view/theme/<theme>/
Module-theme integration      → Site theme files
Reusable module code          → dev-modules/<module_name>/upload
OpenCart core                 → ONLY with explicit user permission
system/storage/modification   → READ-ONLY inspection (never edit directly!)
```
