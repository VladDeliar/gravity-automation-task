# SETUP — Запуск автоматизації заявок Gravity (self-hosted n8n)

Покрокова інструкція: підняти n8n, імпортувати workflow, підключити Telegram, Gemini,
Google Sheets і протестувати наскрізно через `test-form.html`.

---

## 0. Що знадобиться (у тебе вже є)
- Токен Telegram-бота (від `@BotFather`)
- Gemini API ключ (Google AI Studio)
- Google-акаунт з доступом до Google Sheets
- Сервер/VPS з Docker для n8n

---

## 1. Підняти n8n (Docker, локально)

Готовий файл `docker-compose.yml` уже в репозиторії.

```powershell
docker compose up -d
docker compose logs -f n8n      # дочекатись "Editor is now accessible"
```

Після старту відкрий **http://localhost:5678** → перший раз створи owner-акаунт (логін/пароль у UI).

> - Усі секрети (Gemini-ключ, Telegram-токен, Google) зберігаються **у n8n-credentials**
>   (зашифровані `N8N_ENCRYPTION_KEY`), а не в env. `N8N_BLOCK_ENV_ACCESS_IN_NODE=true`.
> - `n8n-data/` — у `.gitignore`, у git не потрапить.
> - Якщо контейнер падає з `Mismatching encryption keys` — зітри стару теку даних і
>   перезапусти: `docker compose down; Remove-Item -Recurse -Force .\n8n-data; docker compose up -d`.
> - Якщо контейнер падає з `Mismatching encryption keys` — зітри стару теку даних і
>   перезапусти: `docker compose down; Remove-Item -Recurse -Force .\n8n-data; docker compose up -d`.
> - Для бойового деплою на VPS пізніше додаси домен + reverse-proxy (Caddy/Nginx) з HTTPS.

---

## 2. Імпортувати workflow

1. Відкрий n8n → **Workflows → Import from File**.
2. Обери `n8n/gravity-lead-workflow.json`.
3. З'явиться схема з 7 нод. Дай збережеться (поки неактивний).

---

## 3. Telegram-бот

1. **Chat ID менеджера:** напиши боту будь-що, потім відкрий
   `https://api.telegram.org/bot<ТОКЕН>/getUpdates` — у відповіді знайди `chat.id`.
   Для групи: додай бота в групу й візьми `chat.id` (від'ємне число) звідти.
2. Встав цей `chat.id` у `TELEGRAM_MANAGER_CHAT_ID` (крок 1) і перезапусти n8n.
3. У n8n → нода **«Сповістити менеджера в Telegram»** → Credentials → **Create New** →
   `Telegram API` → встав токен бота → Save.

---

## 4. Gemini

AI-аналіз зроблено нативним стеком n8n:
**`Basic LLM Chain` → `Google Gemini Chat Model` (+ розбір JSON у code-ноді)**.

- У n8n → нода **«Gemini Chat Model»** → Credentials → **Create New** →
  `Google Gemini(PaLM) Api` → встав свій ключ Gemini → Save.
- Модель за замовчуванням `models/gemini-2.5-flash`. За потреби зміни в полі **Model**.
- Ключ зберігається **у n8n-credential** (зашифрований `N8N_ENCRYPTION_KEY`), а не в env —
  тож `N8N_BLOCK_ENV_ACCESS_IN_NODE` лишається `true` (захист увімкнено).
- Нода **«Розібрати відповідь AI»** дістає `{...}` з відповіді моделі (стійко до ```json-обгортки)
  і має fallback на «консультація», якщо модель віддала несподіване.

---

## 5. Google Sheets

1. Створи таблицю Google Sheets, аркуш назви **`Заявки`**.
2. У перший рядок встав заголовки (точно так, регістр і символи важливі):

   `Дата | Імʼя | Телефон | Тип обʼєкта | Площа | Запит | Коментар | Тип рішення | Висновок AI | Наступний крок | Статус | Менеджер | Джерело`

   > Увага: апостроф у «Імʼя» та «обʼєкта» — це U+02BC (ʼ), як у workflow. Найпростіше —
   > скопіювати заголовки прямо звідси, щоб збігалися 1:1.
3. У n8n → нода **«Записати в Google Sheets»** → Credentials → **Create New** →
   `Google Sheets OAuth2` → пройди авторизацію Google.
4. У тій же ноді: **Document** → обери свою таблицю; **Sheet** → `Заявки`.

---

## 6. Тест наскрізно

1. У n8n зроби workflow **Active** (тумблер угорі) — або тисни **Execute Workflow** для
   тесту в ручному режимі.
2. Скопіюй URL вебхука з ноди «Webhook (форма)»:
   - `http://localhost:5678/webhook-test/gravity-lead` — поки тиснеш **Execute Workflow** (ручний тест);
   - `http://localhost:5678/webhook/gravity-lead` — коли workflow **Active** (Production).
3. Відкрий `test-form.html` у браузері, встав цей URL у поле **Webhook URL**.
4. Заповни форму → **Залишити заявку**.
5. Очікуваний результат:
   - у формі — `✅ Заявку відправлено` + JSON з `solution_type`;
   - новий рядок у Google Sheets;
   - повідомлення менеджеру в Telegram з типом заявки й наступним кроком.

> **CORS:** `test-form.html` відкривається як файл (origin `null`), тож у ноді Webhook →
> **Options → Allowed Origins (CORS)** постав `*` для тесту.

---

## 7. Підключення реальної форми (WordPress) — далі

Замість `test-form.html` бойова форма (Fluent Forms / WPForms) шле той самий JSON на той
самий webhook URL:
- **Fluent Forms** → Integrations / Webhook → метод POST, URL вебхука, поля у JSON.
- або плагін **WP Webhooks** → action «Send Data» на submit форми.

Структура полів має збігатися з тим, що чекає workflow:
`name, phone, object_type, area, stage, interest (масив або рядок), comment, source`.

---

## Структура файлів

```
gravity-automation-task/
├── DELIVERABLE.md                    # текстова відповідь на тестове завдання (Ч.1 + Ч.2)
├── SETUP.md                          # ця інструкція
├── docker-compose.yml                # n8n + WordPress + MariaDB (локально)
├── test-form.html                    # тестова форма → webhook
└── n8n/
    └── gravity-lead-workflow.json    # workflow для імпорту в n8n
```

## Чек-лист підключень

- [ ] n8n піднятий (`http://localhost:5678`), owner-акаунт створений
- [ ] Workflow імпортований
- [ ] Gemini credential (`Google Gemini(PaLM) Api`) створений, обраний у ноді «Gemini Chat Model»
- [ ] Telegram credential створений, `chatId` вписаний у ноді
- [ ] Google Sheets credential + таблиця з аркушем `Заявки`
- [ ] Тест через `test-form.html` пройшов: форма + Sheets + Telegram
