---
name: opencart-events
description: "Comprehensive guide to OpenCart 3 Event System: architecture, intercepting controllers, models, views (Twig), languages, and event registration without OCMOD."
---

# OpenCart 3 Event System — Developer Guide

The Event System in OpenCart 3 is the recommended mechanism for extending core and third-party functionality without modifying original files. Unlike OCMOD:
1. It does not alter core files or generate virtual files in `system/storage/modification/`.
2. It does not break when OpenCart is upgraded or when conflicting modifiers are installed.
3. It requires no modification cache rebuilds when modifying handler logic.

---

## 1. How Events Work in OpenCart 3

The Event architecture is implemented in `system/engine/event.php` and `system/engine/loader.php`. Whenever a controller, model, view, or language file is loaded, the loader dispatches two trigger points:
- **`before`** (prior to method execution or template rendering): allows inspecting/modifying input arguments or halting execution by returning a custom result.
- **`after`** (following execution): allows inspecting or altering the output (`$output`).

### Database Storage (`oc_event`)
The `DB_PREFIX . "event"` table stores:
- `code` — Unique event identifier (e.g. `module_myfilter`).
- `trigger` — Interception point prefixed with scope (`catalog/` or `admin/`).
- `action` — Route to the handler method in a controller (e.g. `extension/module/myfilter/eventHeaderBefore`).
- `status` — Enabled status (`1` or `0`).
- `sort_order` — Execution order (ascending).

> [!IMPORTANT]
> **OpenCart 3 Scope Stripping:**
> In `catalog/controller/startup/event.php` and `admin/controller/startup/event.php`, the `catalog/` or `admin/` prefix is **stripped** when registering into the event engine (`substr($trigger, strpos($trigger, '/') + 1)`).
> Therefore:
> - In the database table you save: `catalog/controller/product/product/before`
> - The core dispatcher fires on: `controller/product/product/before`

---

## 2. Trigger Points & Argument Signatures

### 2.1. Controllers (`controller/*`)
* **Trigger:** `catalog/controller/{route}/before`
  * **Arguments:** `(&$route, &$data)`
  * **Behavior:** If the handler returns a non-null value (and not an Exception), the original controller is **skipped**, and that value becomes the result.
* **Trigger:** `catalog/controller/{route}/after`
  * **Arguments:** `(&$route, &$data, &$output)`
  * **Behavior:** If the handler returns a value, it overwrites `$output`.

### 2.2. Models (`model/*`)
* **Trigger:** `catalog/model/{route}/{method}/before`
  * **Arguments:** `(&$route, &$args)`
  * **Behavior:** If the handler returns a value, the original model method is **not executed**; the returned value is used instead.
* **Trigger:** `catalog/model/{route}/{method}/after`
  * **Arguments:** `(&$route, &$args, &$output)`
  * **Behavior:** Allows modifying query results (e.g. augmenting product data arrays with custom table records).

### 2.3. Templates / Views (`view/*`)
* **Trigger:** `catalog/view/{template_route}/before`
  * **Arguments:** `(&$route, &$data, &$code)`
  * **Behavior:**
    - Inject custom variables into Twig: `$data['my_custom_var'] = 'Value';`.
    - `$code` contains raw template source code (if supplied directly as a string).
* **Trigger:** `catalog/view/{template_route}/after`
  * **Arguments:** `(&$route, &$data, &$output)`
  * **Behavior:** `$output` contains the rendered HTML string. You can manipulate the HTML before it is sent to the client (e.g. via `str_replace` or DOM parsing).

### 2.4. Language Files (`language/*`)
* **Trigger:** `catalog/language/{route}/before` -> `(&$route, &$key)`
* **Trigger:** `catalog/language/{route}/after` -> `(&$route, &$key, &$output)`

---

## 3. Registering and Deleting Events in a Module

Register events in `install()` and remove them in `uninstall()` inside the admin controller (`admin/controller/extension/module/{module_name}.php`).

### Example in Admin Controller:

```php
<?php
class ControllerExtensionModuleMyModule extends Controller {
    private $event_group = 'module_my_module';

    public function install() {
        $this->load->model('setting/event');

        // 1. Intercept header rendering to inject assets/variables into Twig
        $this->model_setting_event->addEvent(
            $this->event_group,
            'catalog/view/common/header/before',
            'extension/module/my_module/eventHeaderBefore'
        );

        // 2. Hook order history addition (e.g. push to CRM or Webhook)
        $this->model_setting_event->addEvent(
            $this->event_group,
            'catalog/model/checkout/order/addOrderHistory/after',
            'extension/module/my_module/eventOrderHistoryAfter'
        );
    }

    public function uninstall() {
        $this->load->model('setting/event');

        // Remove all events registered under this code
        $this->model_setting_event->deleteEventByCode($this->event_group);
    }
}
```

---

## 4. Writing Catalog Event Handlers

Place event handlers in your catalog controller:
`upload/catalog/controller/extension/module/{module_name}.php`.

```php
<?php
class ControllerExtensionModuleMyModule extends Controller {
    /**
     * Handler for view/common/header/before
     * Arguments: &$route (string), &$data (Twig data array), &$code (raw template string)
     */
    public function eventHeaderBefore(&$route, &$data, &$code) {
        // Register styles and scripts
        $this->document->addStyle('catalog/view/javascript/my_module/style.css');
        $this->document->addScript('catalog/view/javascript/my_module/script.js');

        // Inject variables directly into header.twig
        $data['my_custom_badge'] = $this->config->get('module_my_module_badge_text');
    }

    /**
     * Handler for model/checkout/order/addOrderHistory/after
     * Arguments: &$route, &$args (arguments passed to addOrderHistory), &$output (method return)
     */
    public function eventOrderHistoryAfter(&$route, &$args, &$output) {
        $order_id = isset($args[0]) ? (int)$args[0] : 0;
        $order_status_id = isset($args[1]) ? (int)$args[1] : 0;
        $comment = isset($args[2]) ? (string)$args[2] : '';
        $notify = isset($args[3]) ? (bool)$args[3] : false;

        if ($order_id > 0) {
            $this->load->model('checkout/order');
            $order_info = $this->model_checkout_order->getOrder($order_id);

            // Integration logic here...
        }
    }
}
```

---

## 5. Comparison: Events vs. OCMOD

| Criterion | Events | OCMOD (XML Patches) |
| :--- | :--- | :--- |
| **Injecting data into Twig** | ✅ Ideal (`view/*/before`) | ⚠️ Prone to theme conflicts |
| **Hooking actions (order, register)** | ✅ Ideal (`model/*/after`) | ⚠️ Fragile & redundant |
| **Overriding core method logic** | ✅ Clean via `before` | ❌ Breaks on single whitespace change |
| **Targeted HTML markup injection** | ⚠️ Requires HTML parsing in `after` | ✅ Precise `<search>` and `<add>` |
| **Adding form fields in admin** | ⚠️ Complex string manipulation | ✅ Simple XML patch |
| **Stability across CMS updates** | ⭐ 100% resilient | ❌ `NOT FOUND` errors in logs |

**Golden Rule:**
Implement business logic, action hooks, and data injection using **Events**. Reserve **OCMOD** exclusively for targeted markup injection in admin forms and product templates where no native events exist.
