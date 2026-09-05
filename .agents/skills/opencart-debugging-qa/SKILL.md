---
name: opencart-debugging-qa
description: "Diagnostics, error logging, and cache management in OpenCart 3 and ocStore: PHP errors, OCMOD logs, Twig cache, and system cache."
---

# Debugging, Logs, and Cache Management in OpenCart 3

Practical guide for diagnosing issues, inspecting error logs, and managing caches in OpenCart 3 and ocStore.

---

## 1. Log Files Directory

OpenCart 3 stores log files in the protected `system/storage/logs/` directory (or externalized `storage/` directory):

1. **`system/storage/logs/error.log`** — Primary PHP error log and fatal application exceptions.
2. **`system/storage/logs/ocmod.log`** — OCMOD application log. Records `NOT FOUND!` errors when XML `<search>` strings fail to match.
3. **Web Server Logs** (`/var/log/nginx/error.log` or `/var/log/apache2/error.log`) — 500 Internal Server Errors, segmentation faults, FastCGI timeouts.

---

## 2. OpenCart 3 Caching Layers

OpenCart 3 operates 5 distinct caching levels:

| Cache Level | Storage Path | Purpose | How to Flush |
| :--- | :--- | :--- | :--- |
| **OCMOD Modifications** | `system/storage/modification/` | Compiled virtual core files | Admin: *Extensions → Modifications → Refresh* or `ocm ocmod:refresh` |
| **Twig Templates** | `system/storage/cache/template/` | Compiled Twig PHP cache | Admin: *Dashboard → Gear icon (top right) → Clear Template* or `rm -rf system/storage/cache/template/*` |
| **System Data Cache** | `system/storage/cache/cache.*` | Database query and settings cache | `ocm cache:clear` or `rm -f system/storage/cache/cache.*` |
| **SASS Styles** | `system/storage/cache/sass/` | Compiled theme CSS | Admin: Dashboard Gear icon → Clear SASS |
| **Image Resizes** | `image/cache/` | Generated thumbnail/product images | Manual: `rm -rf image/cache/*` |

---

## 3. Troubleshooting Common Issues

### 1. White Screen of Death (WSOD / 500 Error)
* **Step 1:** Check `system/storage/logs/error.log` for the latest PHP Fatal Error.
* **Step 2:** If the error points to a file inside `system/storage/modification/`:
  - Open the file and identify the syntax error.
  - Determine which modifier introduced the erroneous code.
  - Fix the XML modifier in `dev-modules/<module>/install.xml` and re-run `ocm ocmod:refresh`.

### 2. Changes in Twig Templates Are Not Visible
* **Root Cause:** Twig cache is active or the template is overridden by a compiled OCMOD modifier.
* **Solution:**
  1. Clear template cache: delete files in `system/storage/cache/template/`.
  2. Disable template caching during development (Dashboard → Gear icon → switch *Template* to *Off*).
  3. Verify whether an older version exists in `system/storage/modification/catalog/view/theme/...`.

### 3. "Permission Denied" in Admin Panel
* **Root Cause:** The admin user group lacks permissions for the new controller route.
* **Solution:**
  - Navigate to: *System → Users → User Groups → Top Administrator*.
  - Click *Select All* on both *Access Permission* and *Modify Permission*, then save.

### 4. Module Does Not Appear in Extensions List
* **Check:**
  - Controller class name: for `admin/controller/extension/module/my_mod.php`, the class MUST be `ControllerExtensionModuleMyMod`.
  - Language file: `admin/language/en-gb/extension/module/my_mod.php` with `$_['heading_title']` must exist.
  - Case sensitivity: on Linux, filenames and paths are case-sensitive (`My_Mod` vs `my_mod`).

---

## 4. OCM CLI Commands for Debugging

```bash
# Instant system cache clear:
ocm cc

# Recompile all OCMOD modifiers:
ocm ocmod:refresh

# Check active modifications in database:
ocm db:query "SELECT * FROM oc_modification WHERE status = 1"
```
