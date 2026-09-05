# CONTRIBUTING — Working with Modules

Brief guidelines for developers and AI assistants when working on OpenCart modules.

- Edit module files ONLY in `dev-modules/<module_name>/upload/`.
- The `upload/` structure must strictly mirror OpenCart: `catalog/...`, `admin/...`, `system/...`.
- DO NOT modify installed module files directly in the OpenCart root — root files are for inspection only.
- Compiled OCMOD files in `system/storage/modification/` are read-only.
- All module changes originate in `dev-modules/<module_name>/` and are deployed to OpenCart via `ocm install` or `ocm dev`.
- Prior to committing, verify with `git status` that changes are restricted to `dev-modules/<module_name>/`.

Recommendations:
- Use `diff` between `dev-modules/<module_name>/upload` and the corresponding files in OpenCart root.
- Validate module integrity with `php .agents/skills/opencart-module-scaffolding/scripts/check_module.php dev-modules/<module_name>`.
- In Pull Requests, state the module name and a concise summary of changes.
