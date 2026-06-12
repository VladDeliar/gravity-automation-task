# Частина 2 — WordPress (реальна збірка, локально)

Локальний WordPress-стенд, що збирає заявки формою й шле їх у той самий n8n-workflow,
що й Частина 1. Усе піднято в Docker поряд із n8n.

## Що піднято

| Контейнер | Образ | Адреса |
|---|---|---|
| `gravity-wp` | wordpress:latest | http://localhost:8080 |
| `gravity-db` | mariadb:11 | внутрішня |
| `gravity-n8n` | n8nio/n8n | http://localhost:5678 |

WordPress і n8n — у спільній docker-мережі, тож WP б'є у webhook за внутрішнім імʼям
`http://n8n:5678/webhook/gravity-lead`.

Адмін WP: логін/пароль задаються під час `wp core install` (див. крок «Як це зібрано»).

## Плагіни

| Плагін | Навіщо |
|---|---|
| **Elementor** | Конструктор сторінок (візуальне оформлення лендингу) |
| **Contact Form 7** | Форма заявки |
| **CF7 to Webhook** (`cf7-to-zapier`) | Шле сабміт CF7 як JSON у n8n-webhook |

## Форма (CF7, ID 6)

Поля названі так, як їх чекає n8n-workflow:
`name, phone, object_type, area, stage, interest[], comment, source`.
`interest` — чекбокси (масив), решта — text/tel/select/number/textarea.

Налаштування CF7 to Webhook (збережено в формі):
- **Webhook URL:** `http://n8n:5678/webhook/gravity-lead`
- **send_mail = 0** — пропустити реальний лист, але webhook надіслати
  (CF7 шле webhook на хуку `wpcf7_mail_sent`, який спрацьовує і при пропущеному листі).

## Сторінка

«Підобрати smart home-рішення» (slug `pidibraty-smart-home`) з усіма блоками: Hero,
що робить Gravity, Professional, Wireless, Як це працює, форма CF7, фінальний CTA.

Оформлення зроблено в **Elementor** через `wp-elementor.php` — генерує `_elementor_data`
(секції → колонки → віджети) з фонами, типографікою, кнопками й вбудованою CF7-формою
(віджет `shortcode`). Секцію форми позначено CSS-якорем `zayavka`, тож усі кнопки
«Залишити заявку» / «Підібрати рішення» скролять до неї.

> Далі сторінку можна доопрацьовувати візуально: **Сторінки → Підібрати smart home →
> Edit with Elementor** — перетягувати блоки, міняти стилі, додавати іконки/зображення.

## mu-plugin: `mu-fake-mail.php` (тільки локально!)

Лежить у `wp-content/mu-plugins/`. Робить дві речі, без яких локальний звʼязок не працює:

1. **`pre_wp_mail` → true.** Локально немає SMTP, тож `wp_mail()` падає → CF7 ставить
   `mail_failed` і не доходить до webhook. Фейкаємо «успіх» (лист нікуди не йде).
2. **`http_request_args` → `reject_unsafe_urls=false`** для запиту на `n8n`. WordPress із
   захисту від SSRF блокує запити на приватні IP (Docker 172.x) і нестандартні порти
   (`wp_http_validate_url` дозволяє лише 80/443/8080, а n8n — 5678). Для внутрішнього
   виклику цей захист вимикаємо точково.

> ⚠️ На бойовому сервері цей mu-plugin **не потрібен**: там реальний SMTP і n8n за
> публічним доменом на 443 (`https://n8n.gravity.example/webhook/gravity-lead`),
> тож стандартні перевірки WordPress проходять самі.

## Як протестувати

1. Браузер: http://localhost:8080/?page_id=7 → заповнити форму → надіслати.
2. Очікувано: «Thank you for your message» + новий рядок у Google Sheets +
   повідомлення в Telegram із типом заявки.

CLI-тест (із чистим UTF-8, зсередини контейнера) — `submit-test.sh`:
```bash
docker cp submit-test.sh gravity-wp:/tmp/ && docker exec gravity-wp sh /tmp/submit-test.sh
```
Успіх = `"status":"mail_sent"`.

## Як це зібрано (відтворити з нуля)

```powershell
docker compose up -d
# wp-cli (через одноразовий контейнер, спільні volume+мережа):
$WP = "docker run --rm -u 33:33 --network gravity-automation-task_default --volumes-from gravity-wp -e WORDPRESS_DB_HOST=db:3306 -e WORDPRESS_DB_USER=wordpress -e WORDPRESS_DB_PASSWORD=wordpress -e WORDPRESS_DB_NAME=wordpress wordpress:cli"
# 1) ядро WP
# підстав свої логін/пароль адміна замість <ADMIN_USER>/<ADMIN_PASS>
iex "$WP wp core install --url=http://localhost:8080 --title=`"Gravity Smart Home`" --admin_user=<ADMIN_USER> --admin_password=<ADMIN_PASS> --admin_email=admin@gravity.local --skip-email"
# 2) плагіни
iex "$WP wp plugin install elementor contact-form-7 cf7-to-zapier --activate"
# 3) форма + сторінка
docker cp wp-setup.php gravity-wp:/var/www/html/wp-setup.php
iex "$WP wp eval-file /var/www/html/wp-setup.php"
# 3b) оформлення сторінки в Elementor
docker cp wp-elementor.php gravity-wp:/var/www/html/wp-elementor.php
iex "$WP wp eval-file /var/www/html/wp-elementor.php"
# 4) міст для локалки
docker cp mu-fake-mail.php gravity-wp:/var/www/html/wp-content/mu-plugins/mu-fake-mail.php
# 5) активувати n8n-workflow (production-webhook)
docker exec gravity-n8n n8n update:workflow --id=<ID> --active=true; docker restart gravity-n8n
```
