# Gravity Smart Home — автоматизація заявок

Тестове завдання: автоматизація обробки заявок із сайту + MVP-лендинг на WordPress.
Заявка з форми потрапляє в n8n, аналізується AI (Gemini), записується в Google Sheets
і надсилається менеджеру в Telegram із визначеним типом рішення та наступним кроком.

## Як це працює

```
Форма (WordPress + Contact Form 7)
        │  webhook (JSON)
        ▼
      n8n  ──►  Gemini (LLM Chain)  — класифікація: professional / wireless / консультація
        ├──►  Google Sheets         — журнал заявок
        ├──►  Telegram              — сповіщення менеджеру + наступний крок
        └──►  відповідь формі
```

## Стек

- **n8n** — оркестратор (self-hosted, SQLite)
- **Google Gemini** (`gemini-2.5-flash`) — AI-аналіз заявки
- **Google Sheets** — журнал заявок
- **Telegram Bot** — сповіщення менеджеру
- **WordPress + Elementor + Contact Form 7** — лендинг і форма
- **Docker Compose** — увесь стенд (n8n + WordPress + MariaDB)

## Швидкий старт (локально)

```powershell
docker compose up -d
```

- **n8n:** http://localhost:5678 (перший вхід — створення owner-акаунта)
- **Лендинг:** http://localhost:8080/?page_id=7
- **WP-адмін:** http://localhost:8080/wp-admin

Далі підключи credentials (Gemini, Telegram, Google Sheets) у n8n — деталі в [SETUP.md](SETUP.md).

## Структура

| Файл / тека | Призначення |
|---|---|
| [SETUP.md](SETUP.md) | Як підняти й налаштувати **n8n** (Частина 1) |
| [PART2-WORDPRESS.md](PART2-WORDPRESS.md) | Як зібрано **WordPress**-стенд (Частина 2) |
| `docker-compose.yml` | Стек: n8n + WordPress + MariaDB |
| `n8n/gravity-lead-workflow.json` | Workflow для імпорту в n8n |
| `wp-setup.php` | Скрипт: створює CF7-форму + лендинг |
| `wp-elementor.php` | Скрипт: оформлення лендингу в Elementor |
| `mu-fake-mail.php` | mu-плагін: локальний міст WP → n8n |
| `mu-gravity-style.php` | mu-плагін: дрібні стильові правки сайту |
| `test-form.html` | Standalone-форма для тесту webhook |
| `submit-test.sh` | CLI-тест сабміту CF7-форми |

## Примітки

- Секрети (Gemini-ключ, Telegram-токен, Google) зберігаються в **n8n-credentials**
  (зашифровані), а не в коді. `N8N_BLOCK_ENV_ACCESS_IN_NODE=true`.
- Runtime-стан і секрети не комітяться: `n8n-data/`, `.env`, бази даних — у `.gitignore`.
- `mu-fake-mail.php` — **тільки для локального стенду** (немає SMTP; на проді не потрібен).
- Деталі обох частин — у відповідних `*.md`.
