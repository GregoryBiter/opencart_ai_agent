# OpenCart 3 AI Agent: Knowledge Base, Rules & Skills

A comprehensive repository of rules, instructions, and specialized **AI Skills** for developing, debugging, and maintaining modules and custom storefronts on **OpenCart 3** and **ocStore 3**.

Compatible with AI coding assistants (Antigravity, GitHub Copilot, Cursor, Claude Code) and the **OCM CLI** developer utility.

---

## 1. Project Setup

### Option A: Via OCM CLI (Recommended)
If [OCM CLI](https://github.com/GregoryBiter/ocm-cli) is installed in your system:
```bash
# Install skills and rules into current OpenCart project:
ocm agent:install

# Or install globally for all projects on your machine:
ocm agent:install --global
```

### Option B: Manual Copy
Copy `.agents/`, `AGENTS.md`, and `dev-modules/` into the root of your working OpenCart installation.

---

## 2. Repository Layout

```text
.
├── AGENTS.md                                   # Core change scope rules (module vs theme vs core)
├── .agents/
│   ├── rules/
│   │   └── opencart.md                         # Contextual rules for AI agents
│   └── skills/
│       ├── ocm-cli/                            # OCM CLI developer toolkit & workflows
│       ├── opencart-events/                    # OpenCart 3 Event System (non-breaking hooks)
│       ├── opencart-module-scaffolding/        # Module boilerplate, templates & quality checks
│       │   └── scripts/check_module.php        # Standalone module linter and PHP syntax checker
│       ├── opencart-database-migrations/       # Database guidelines, DB_PREFIX & migrations
│       ├── opencart-debugging-qa/              # Logging map, 5 cache tiers & WSOD troubleshooting
│       ├── ocmod/                              # OCMOD XML authoring best practices
│       │   └── scripts/validate_ocmod.php      # Standalone OCMOD XML validator
│       └── opencart3/                          # OpenCart 3 MVC-L architecture navigator
└── dev-modules/                                # Isolated module development workspace
    ├── CONTRIBUTING.md
    └── opencart-instructions/
```

---

## 3. Specialized AI Skills Catalog

| Skill | Purpose & Scope |
| :--- | :--- |
| **[`ocm-cli`](.agents/skills/ocm-cli/SKILL.md)** | Artisan-style CLI commands: `ocm make:module`, `ocm dev` (watch & auto-sync), `ocm db:*` (import/export/query), `ocm cache:clear`, `ocm ocmod:refresh`, `ocm build` (`*.ocmod.zip`). |
| **[`opencart-events`](.agents/skills/opencart-events/SKILL.md)** | Native, conflict-free extension mechanism. Intercept controllers, models, languages, and views (`view/*/before` and `after`) without modifying core files. Event registration via `model_setting_event`. |
| **[`opencart-module-scaffolding`](.agents/skills/opencart-module-scaffolding/SKILL.md)** | Clean module structure inside `dev-modules/<name>/upload/`. Boilerplates for admin controller with `modify` permissions and `user_token`, Twig settings form, multilingual support, and storefront display. |
| **[`opencart-database-migrations`](.agents/skills/opencart-database-migrations/SKILL.md)** | Safe database design: `DB_PREFIX`, `InnoDB`, `utf8mb4`, `$this->db->escape()`, column existence checks (`SHOW COLUMNS FROM`), schema version tracking in `oc_setting`. |
| **[`opencart-debugging-qa`](.agents/skills/opencart-debugging-qa/SKILL.md)** | Troubleshooting and diagnostics: error logs (`error.log`, `ocmod.log`), WSOD resolution, management of 5 independent cache tiers (modifications, Twig, system data, SASS, image cache). |
| **[`ocmod-writing`](.agents/skills/ocmod/SKILL.md)** | Authoring resilient XML patches: strictly single-line `<search>`, `trim="true"`, `regex="true"`, mandatory `<![CDATA[ ... ]]>`, preference for `before`/`after` over `replace`. |
| **[`opencart-3-workflow`](.agents/skills/opencart3/SKILL.md)** | Core MVC-L flow, `system/library` isolation, namespacing conventions, and release checklist. |

---

## 4. Built-in Quality Scripts

Zero-dependency PHP scripts included in the repository:

1. **OCMOD XML Validator:**
   ```bash
   php .agents/skills/ocmod/scripts/validate_ocmod.php dev-modules/my_module/install.xml
   ```
   *Validates XML syntax, prevents prohibited multi-line search blocks, checks CDATA, validates regex, and flags risky replace operations.*

2. **Module Integrity & Syntax Linter:**
   ```bash
   php .agents/skills/opencart-module-scaffolding/scripts/check_module.php dev-modules/my_module
   ```
   *Checks directory structure, controller class conventions, required languages, Twig views, and executes `php -l` on all PHP files.*

---

## 5. Golden Rules for Changes (AGENTS.md)

1. **Module functionality** must be changed ONLY in `dev-modules/<module_name>/upload/`.
2. **Site-specific design and theme overrides** belong in the OpenCart root (`catalog/view/theme/<theme>/`).
3. **OpenCart core** (`system/engine/`, `system/framework.php`, etc.) must NEVER be edited directly without explicit user consent.
4. **`system/storage/modification/`** is strictly READ-ONLY. Any direct edits in this folder will be deleted upon the next modification cache refresh.
