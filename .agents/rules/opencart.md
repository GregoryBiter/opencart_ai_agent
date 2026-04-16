---
trigger: always_on
---

---
name: copilot-module-rules
description: "Use when suggesting code edits or generating files for OpenCart modules. Ограничивает изменения только папкой модуля и описывает исключения."
applyTo:
	- "**"
---

# Правила Copilot для работы с модулями

Короткая, однозначная инструкция для GitHub Copilot и автодополнений при работе с этим репозиторием.

- Изменять и предлагать файлы ТОЛЬКО в `dev-modules/<имя_модуля>/upload`.
- Структура в `upload` должна соответствовать структуре OpenCart (`catalog/`, `admin/`, и т.д.).
- НЕЛЬЗЯ вносить изменения напрямую в папку `opencart` — её можно только просматривать для понимания реализации.
- Готовые и применённые OCMOD находятся в `system/storage/modification` — туда можно заглядывать для проверки, но НЕ редактировать.
- Не дублируй изменения: правки должны быть в модуле, а не в корне OpenCart.
- В PR включай только файлы из `dev-modules/<имя_модуля>/upload` и указывай, что изменения реализованы через модуль.

Use when: module, dev-modules, upload, opencart (view-only), ocmod, modification, pull request

Примеры подсказок к Copilot:
- "Создать контроллер для модуля X в `dev-modules/X/upload/admin/controller/...`"
- "Обновить шаблон модуля Y — только в `dev-modules/Y/upload/catalog/view/...`"

Подсказка: при необходимости скопируйте этот файл в `dev-modules/<имя_модуля>/` и замените `<имя_модуля>` на реальное имя.

- Чтобы обновить файлы модуля в OpenCart, выполните в папке модуля команду `ocm install` — это применит/обновит файлы в OpenCart.
