---
name: ocm-cli
description: "Practical guide to OCM CLI (OpenCart Module Manager) for development, watch/dev mode synchronization, database management, and module builds."
---

# OCM CLI (OpenCart Module Manager) — Developer Guide

OCM is a command-line tool (Artisan-style) designed for developing, debugging, testing, managing databases, and packaging modules for CMS OpenCart 2.x and 3.x.

---

## 1. Module Architecture in OCM

Standard layout of an OCM module:
```text
my_module/
├── opencart-module.json    # Metadata: {"module_name": "...", "code": "...", "version": "1.0.0", "author": "..."}
├── install.xml             # Core OCMOD modifier (optional, index.xml is also supported)
├── upload/                 # Source code for OpenCart
│   ├── admin/              # Admin panel (controller, language, model, view)
│   ├── catalog/            # Storefront (controller, language, model, view)
│   └── system/             # Libraries, helpers (system/library/...)
└── .ocm/                   # OCM metadata directory
    ├── files.json          # Tracked files registry
    └── target              # Path to linked OpenCart installation
```

---

## 2. Core Commands & Workflows

### Creating a New Module
```bash
# Interactive creation:
ocm make:module

# One-liner with parameters:
ocm make:module my_filter --template=my_module --title="Fast Filter" --ver="1.0.0"
cd my_filter
```

### Linking to an OpenCart Installation
```bash
# Link module to a local OpenCart installation:
ocm link /var/www/my-opencart.loc

# Verify link and tracked files status:
ocm status
```

### Active Development (Watch & Dev Mode)
```bash
ocm dev
# or alias:
ocm watch
```
In `ocm dev` mode:
1. Syncs all files from `upload/` into the linked OpenCart installation.
2. Watches file changes in real time.
3. **Automatically imports `install.xml` (or `index.xml`) into the OpenCart database** (`oc_modification` table) upon modifier edits.
4. Flushes modification cache and refreshes OCMOD automatically.

### One-Time Install and Return
```bash
# Copy files into OpenCart and register OCMOD:
ocm install

# Copy files only without writing to database:
ocm install --no-db

# Pull modified files back from OpenCart into upload/:
ocm return
```

### Cache & Modifications Management
```bash
# Clear OpenCart system cache (system/storage/cache):
ocm cache:clear
# or alias:
ocm cc

# Recompile OCMOD modifiers:
ocm ocmod:refresh
```

### Database Toolkit (DB Management)
OCM automatically extracts DB credentials from the target `config.php`:
```bash
# View DB connection status and stats:
ocm db:info

# Interactive MySQL CLI:
ocm db

# Run ad-hoc SQL query:
ocm db:query "SELECT * FROM oc_setting WHERE `key` = 'config_name'"

# Create database dump:
ocm db:dump backup.sql.gz -z

# Dump only store tables (matching DB_PREFIX):
ocm db:dump oc_only.sql --prefix-only

# Import SQL file:
ocm db:import backup.sql
```

### AI Agent Skills Installation
```bash
# Install OpenCart AI Agent rules and skills into linked OpenCart:
ocm agent:install

# Install globally into user system (~/.agents/skills):
ocm agent:install --global
```

### Building Release Archive
```bash
# Packages upload/, install.xml into a clean *.ocmod.zip ready for distribution:
ocm build
```
