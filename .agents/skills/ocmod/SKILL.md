---
name: ocmod-writing
description: "Practical guide to authoring OCMOD XML patches taking into account single-line search, regex, and OpenCart specific constraints."
---

# OCMOD — How to Author Reliable XML Patches

Goal: Provide concise, verifiable rules and examples for creating OCMOD files that apply reliably in OpenCart.

---

## 1. Format Structure

```xml
<modification>
  <name>Module Name</name>
  <code>author_module_code</code>
  <version>1.0</version>
  <author>Author</author>
  <file path="catalog/controller/product/product.php">
    <operation>
      <search trim="true"><![CDATA[...]]></search>
      <add position="replace|before|after"><![CDATA[...]]></add>
    </operation>
  </file>
</modification>
```

---

## 2. Single-Line Search: Core Constraint

OpenCart OCMOD performs line-by-line searches. The content of `<search>` must match a single line in the source file:
* **Do NOT search multi-line blocks** in standard `<search>` — it will fail to match.
* If surrounding whitespace or indentation varies, use `trim="true"` or regex search.

---

## 3. Regular Expressions

When matching dynamic code or variable spacing, set `regex="true"` on `<search>`:

```xml
<search regex="true"><![CDATA[\$this->response->setOutput\(\$this->load->view\('product/product', \$data\)\);]]></search>
```

Rules for regex:
* Escape slashes, brackets, and dollar signs: `\$`, `\(`, `\)`.
* Use `\s+` or `\s*` for flexible whitespace matching.
* Ensure the regex is contained within a single logical line.

---

## 4. CDATA Wrapping

Always wrap search and replacement code in `<![CDATA[ ... ]]>` to prevent breaking the XML parser when handling `<`, `>`, `&`, or PHP tags:

```xml
<add position="after"><![CDATA[
  // Custom logging call
  $this->log->write('Product view hook');
]]></add>
```

---

## 5. Insertion Positions (`position`)

* `position="before"` — Inserts code immediately before the matched line.
* `position="after"` — Inserts code immediately after the matched line.
* `position="replace"` — Completely replaces the matched line.

---

## 6. Search Strategy & Attributes

* **Uniqueness:** Choose an anchor line that appears uniquely within the target method, not generic tags like `<?php` or `</div>`.
* **Attribute `trim="true"`:** Always include `trim="true"` — leading and trailing spaces/tabs will not break the search.
* **Attribute `index="N"`:** If a line occurs multiple times and you need a specific occurrence, use `<search index="1">` (zero-indexed).
* **Minimal Search Scope:** Keep the search string as short and specific as possible to minimize conflict with other modules.

---

## 7. Insertion Strategy (`<add>`)

* Prefer `position="before"` or `position="after"` over `position="replace"`. Full replacement is the #1 cause of third-party module conflicts.
* If adding significant business logic, place it in an external helper/controller and call it with a single line in `<add>`.

---

## 8. Modifier Precedence & VQMod

* OCMOD modifiers execute in alphabetical order by their `<code>` tag.
* If a project uses both VQMod and OCMOD: VQMod executes **first**, and OCMOD processes the resulting output.

---

## 9. Safe Developer Checklist

* After every refresh, check `system/storage/logs/ocmod.log` (or Admin → Modifications → Log). Any `NOT FOUND!` indicates a failed match.
* **Never edit** files in `system/storage/modification/` directly; they will be overwritten on the next refresh.
* Whenever possible, prefer **Events** over OCMOD.

---

## 10. Example Operations

### Example A: Safe insertion after output call
```xml
<file path="catalog/controller/product/product.php">
  <operation error="skip">
    <search trim="true"><![CDATA[$data['footer'] = $this->load->controller('common/footer');]]></search>
    <add position="after"><![CDATA[$data['my_custom_var'] = 'Hello World';]]></add>
  </operation>
</file>
```

### Example B: Regex search with flexible spacing
```xml
<file path="catalog/controller/checkout/checkout.php">
  <operation>
    <search regex="true"><![CDATA[\$this->session->data\['order_id'\];]]></search>
    <add position="after"><![CDATA[
      $this->log->write('Order ID: ' . $this->session->data['order_id']);
    ]]></add>
  </operation>
</file>
```

---

## 11. Location & Deployment

* Place `install.xml` in the module root (for packaging into `*.ocmod.zip`) or in `dev-modules/<module>/upload/system/<name>.ocmod.xml`.
* With OCM CLI, running `ocm dev` or `ocm install` automatically synchronizes the modifier to the database (`oc_modification`) and recompiles the modification cache.

---

## 12. Automated OCMOD Validation

Use the built-in standalone validator script to detect common errors (multi-line search, missing CDATA, dangerous replacements, regex syntax):

```bash
php .agents/skills/ocmod/scripts/validate_ocmod.php dev-modules/<module_name>/install.xml
```
Always run this check before committing or packaging releases.
