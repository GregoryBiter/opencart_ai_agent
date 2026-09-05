---
name: opencart-database-migrations
description: "Practical guide to working with databases in OpenCart 3: table creation (DB_PREFIX), safe SQL queries, upgrade migrations, and OCM CLI tools."
---

# Database Work and Migrations in OpenCart 3

Guidelines for creating and safely altering database tables, escaping parameters, and automating database workflows via OCM CLI.

---

## 1. Core Database Principles in OpenCart

Database operations in OpenCart use the registry service: `$this->db`.

* **Table Prefix:** Always use the `DB_PREFIX` constant and wrap table names in backticks:
  ```php
  $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE `order_id` = " . (int)$order_id);
  ```
* **String Escaping:** All string inputs **must** be escaped with `$this->db->escape()`:
  ```php
  $title = $this->db->escape($this->request->post['title']);
  ```
* **Numeric Parameters:** Always cast integers and floats: `(int)$id`, `(float)$price`.
* **Table Engine & Collation:**
  * Engine: `ENGINE=InnoDB`
  * Collation: `DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci` (or `utf8_general_ci` on older MySQL servers).

---

## 2. Table Creation on Install (`install`)

When installing a module, create custom tables using `IF NOT EXISTS`:

```php
public function install() {
    // 1. Create dedicated module table
    $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "my_module_item` (
        `item_id` INT(11) NOT NULL AUTO_INCREMENT,
        `product_id` INT(11) NOT NULL DEFAULT 0,
        `code` VARCHAR(64) NOT NULL DEFAULT '',
        `payload` TEXT NULL,
        `date_added` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`item_id`),
        KEY `idx_product_id` (`product_id`),
        KEY `idx_code` (`code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

    // 2. Safely add column to an existing core OpenCart table
    $query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "order` LIKE 'my_custom_code'");
    if (!$query->num_rows) {
        $this->db->query("ALTER TABLE `" . DB_PREFIX . "order` ADD COLUMN `my_custom_code` VARCHAR(64) NULL AFTER `order_id`");
    }
}
```

---

## 3. Data Cleanup on Uninstall (`uninstall`)

In the `uninstall()` method, clean up tables and settings created by the module:

```php
public function uninstall() {
    // 1. Drop module tables
    $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "my_module_item`");

    // 2. Remove added columns (if full cleanup policy applies)
    $query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "order` LIKE 'my_custom_code'");
    if ($query->num_rows) {
        $this->db->query("ALTER TABLE `" . DB_PREFIX . "order` DROP COLUMN `my_custom_code`");
    }

    // 3. Remove module settings
    $this->load->model('setting/setting');
    $this->model_setting_setting->deleteSetting('module_my_module');
}
```

---

## 4. Safe Upgrade Migrations Pattern

When a module is updated on a live site, track schema versioning in `oc_setting`:

```php
public function checkMigration() {
    $current_schema_version = (int)$this->config->get('module_my_module_db_version');

    if ($current_schema_version < 2) {
        // Upgrade migration to version 2
        $query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "my_module_item` LIKE 'status'");
        if (!$query->num_rows) {
            $this->db->query("ALTER TABLE `" . DB_PREFIX . "my_module_item` ADD `status` TINYINT(1) NOT NULL DEFAULT 1");
        }

        // Update stored schema version
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSettingValue('module_my_module', 'module_my_module_db_version', 2);
    }
}
```

---

## 5. Database Management via OCM CLI

The OCM CLI automatically parses connection parameters from `config.php`:

```bash
# Check connection status and database size:
ocm db:info

# Open an interactive MySQL shell:
ocm db

# Run an ad-hoc SQL query:
ocm db:query "SELECT COUNT(*) FROM oc_product"

# Dump database:
ocm db:dump backup_before_update.sql

# Dump only store tables (matching DB_PREFIX):
ocm db:dump oc_tables_only.sql --prefix-only

# Restore database from dump:
ocm db:import backup_before_update.sql
```
