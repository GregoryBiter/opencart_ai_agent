---
name: opencart-module-scaffolding
description: "Standard architecture, generation, and file checklist for OpenCart 3 modules: admin, catalog, multilingual support, security (user_token, permissions), and OCM CLI integration."
---

# OpenCart 3 Module Architecture and Scaffolding

Guidelines and reference templates for creating OpenCart 3 and ocStore 3 extensions (`extension/module`).

---

## 1. Module File Structure

All module files must be developed strictly within:
`dev-modules/<module_name>/upload/`

```text
dev-modules/<module_name>/
├── install.xml                   # OCMOD modifier (if targeted core patch is required)
└── upload/
    ├── admin/
    │   ├── controller/extension/module/<module_name>.php
    │   ├── language/
    │   │   ├── en-gb/extension/module/<module_name>.php
    │   │   ├── ru-ru/extension/module/<module_name>.php
    │   │   └── uk-ua/extension/module/<module_name>.php (for ocStore)
    │   └── view/template/extension/module/<module_name>.twig
    ├── catalog/
    │   ├── controller/extension/module/<module_name>.php
    │   ├── language/
    │   │   ├── en-gb/extension/module/<module_name>.php
    │   │   └── ru-ru/extension/module/<module_name>.php
    │   ├── model/extension/module/<module_name>.php
    │   └── view/theme/default/template/extension/module/<module_name>.twig
    └── system/ (optional)
        └── library/<vendor>/<module_name>/
```

---

## 2. Reference Admin Controller

Path: `upload/admin/controller/extension/module/<module_name>.php`

```php
<?php
class ControllerExtensionModuleMyModule extends Controller {
    private $error = array();
    private $m_code = 'my_module';
    private $m_route = 'extension/module/my_module';
    private $m_set_prefix = 'module_my_module_';

    public function index() {
        $this->load->language($this->m_route);

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        // Handle settings save (POST)
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('module_' . $this->m_code, $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }

        // Validation errors
        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';

        // Breadcrumbs
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link($this->m_route, 'user_token=' . $this->session->data['user_token'], true)
        );

        // Actions
        $data['action'] = $this->url->link($this->m_route, 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        // Load persisted settings with fallback
        $fields = array('status', 'custom_text');
        foreach ($fields as $field) {
            $key = $this->m_set_prefix . $field;
            if (isset($this->request->post[$key])) {
                $data[$key] = $this->request->post[$key];
            } else {
                $data[$key] = $this->config->get($key);
            }
        }

        // Common layout controllers
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view($this->m_route, $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', $this->m_route)) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    public function install() {
        // Register events, create tables, grant permissions
    }

    public function uninstall() {
        // Unregister events, cleanup settings/tables
    }
}
```

---

## 3. Reference Admin Twig Template

Path: `upload/admin/view/template/extension/module/<module_name>.twig`

```twig
{{ header }}{{ column_left }}
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-module" data-toggle="tooltip" title="{{ button_save }}" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="{{ cancel }}" data-toggle="tooltip" title="{{ button_cancel }}" class="btn btn-default"><i class="fa fa-reply"></i></a>
      </div>
      <h1>{{ heading_title }}</h1>
      <ul class="breadcrumb">
        {% for breadcrumb in breadcrumbs %}
        <li><a href="{{ breadcrumb.href }}">{{ breadcrumb.text }}</a></li>
        {% endfor %}
      </ul>
    </div>
  </div>
  <div class="container-fluid">
    {% if error_warning %}
    <div class="alert alert-danger alert-dismissible"><i class="fa fa-exclamation-circle"></i> {{ error_warning }}
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    {% endif %}
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-pencil"></i> {{ text_edit }}</h3>
      </div>
      <div class="panel-body">
        <form action="{{ action }}" method="post" enctype="multipart/form-data" id="form-module" class="form-horizontal">
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-status">{{ entry_status }}</label>
            <div class="col-sm-10">
              <select name="module_my_module_status" id="input-status" class="form-control">
                <option value="1" {% if module_my_module_status %}selected="selected"{% endif %}>{{ text_enabled }}</option>
                <option value="0" {% if not module_my_module_status %}selected="selected"{% endif %}>{{ text_disabled }}</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-text">{{ entry_custom_text }}</label>
            <div class="col-sm-10">
              <input type="text" name="module_my_module_custom_text" value="{{ module_my_module_custom_text }}" placeholder="{{ entry_custom_text }}" id="input-text" class="form-control" />
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
{{ footer }}
```

---

## 4. Language Files

### English (`en-gb`)
Path: `upload/admin/language/en-gb/extension/module/<module_name>.php`
```php
<?php
// Heading
$_['heading_title']       = 'My Module';

// Text
$_['text_extension']      = 'Extensions';
$_['text_success']        = 'Success: You have modified module settings!';
$_['text_edit']           = 'Edit Module';

// Entry
$_['entry_status']        = 'Status';
$_['entry_custom_text']   = 'Custom Text';

// Error
$_['error_permission']    = 'Warning: You do not have permission to modify this module!';
```

### Russian (`ru-ru`)
Path: `upload/admin/language/ru-ru/extension/module/<module_name>.php`
```php
<?php
// Heading
$_['heading_title']       = 'Мой модуль';

// Text
$_['text_extension']      = 'Расширения';
$_['text_success']        = 'Настройки модуля успешно сохранены!';
$_['text_edit']           = 'Редактирование модуля';

// Entry
$_['entry_status']        = 'Статус';
$_['entry_custom_text']   = 'Произвольный текст';

// Error
$_['error_permission']    = 'У вас нет прав для управления этим модулем!';
```

---

## 5. Catalog Implementation

### Controller: `upload/catalog/controller/extension/module/<module_name>.php`
```php
<?php
class ControllerExtensionModuleMyModule extends Controller {
    public function index($setting) {
        if (!$this->config->get('module_my_module_status')) {
            return '';
        }

        $this->load->language('extension/module/my_module');

        $data['custom_text'] = $this->config->get('module_my_module_custom_text');

        return $this->load->view('extension/module/my_module', $data);
    }
}
```

### Catalog Twig Template: `upload/catalog/view/theme/default/template/extension/module/<module_name>.twig`
```twig
<div class="my-module-box">
  <h3>{{ heading_title }}</h3>
  <p>{{ custom_text }}</p>
</div>
```

---

## 6. Fast Creation with OCM CLI

If OCM CLI is installed, modules can be generated interactively:
```bash
ocm make:module my_filter --template=my_module --title="Fast Filter" --ver="1.0.0"
cd my_filter
ocm link /path/to/opencart
ocm dev
```

---

## 7. Automated Module Integrity Check

Before deploying or packaging a module, run the built-in validator to verify structure, language completeness, and PHP syntax:

```bash
php .agents/skills/opencart-module-scaffolding/scripts/check_module.php dev-modules/<module_name>
```

This verifies:
- Existence of `upload/` directory;
- Proper admin controller class naming;
- Presence of required language files (`en-gb`, `ru-ru`);
- Twig view template existence;
- PHP syntax validity (`php -l`) across all files;
- Valid `install.xml` via the OCMOD validator (if present).
