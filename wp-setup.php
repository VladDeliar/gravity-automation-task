<?php
/**
 * Одноразовий скрипт: створює CF7-форму (з webhook у n8n) і landing-сторінку
 * «Підібрати smart home-рішення». Запускається через:  wp eval-file wp-setup.php
 */

if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
    WP_CLI::error( 'Contact Form 7 не активний.' );
}

$WEBHOOK = 'http://n8n:5678/webhook/gravity-lead'; // внутрішня мережа docker

// ─── 1. CF7-форма ────────────────────────────────────────────────────────────
$form_template = <<<CF7
<style>
.gl-form{max-width:620px;margin:0 auto;background:#ffffff;border-radius:16px;padding:32px;
  box-shadow:0 18px 50px rgba(0,0,0,.35);text-align:left;color:#1a1d24;}
.gl-form .gl-field{margin:0 0 18px;}
.gl-form .gl-row{display:grid;grid-template-columns:1fr 1fr;gap:18px;}
.gl-form label.gl-label{display:block;font-size:13px;font-weight:600;color:#3a3f4a;margin:0 0 6px;}
.gl-form input[type=text],.gl-form input[type=tel],.gl-form input[type=number],
.gl-form select,.gl-form textarea{
  width:100%;box-sizing:border-box;padding:12px 13px;font-size:15px;color:#1a1d24;
  background:#f7f9fb;border:1px solid #d7dce3;border-radius:10px;outline:none;
  transition:border-color .15s, box-shadow .15s;font-family:inherit;}
.gl-form textarea{min-height:96px;resize:vertical;}
.gl-form input:focus,.gl-form select:focus,.gl-form textarea:focus{
  border-color:#3DDC97;box-shadow:0 0 0 3px rgba(61,220,151,.18);background:#fff;}
.gl-form .wpcf7-checkbox{display:flex;flex-wrap:wrap;gap:10px;}
.gl-form .wpcf7-checkbox .wpcf7-list-item{margin:0;}
.gl-form .wpcf7-checkbox .wpcf7-list-item label{display:flex;align-items:center;gap:8px;
  cursor:pointer;padding:9px 14px;border:1px solid #d7dce3;border-radius:10px;
  font-size:14px;color:#1a1d24;background:#f7f9fb;transition:.15s;}
.gl-form .wpcf7-checkbox .wpcf7-list-item label:hover{border-color:#3DDC97;}
.gl-form .wpcf7-checkbox input[type=checkbox]{accent-color:#3DDC97;width:16px;height:16px;}
.gl-form .gl-submit{margin-top:8px;}
.gl-form .wpcf7-submit{width:100%;padding:14px 20px;font-size:16px;font-weight:700;
  color:#06281c;background:#3DDC97;border:0;border-radius:10px;cursor:pointer;
  transition:background .15s, transform .05s;}
.gl-form .wpcf7-submit:hover{background:#34c587;}
.gl-form .wpcf7-submit:active{transform:translateY(1px);}
.wpcf7 .wpcf7-response-output{max-width:620px;margin:16px auto 0!important;padding:12px 16px!important;
  border-radius:10px;font-size:14px;text-align:center;color:#e8eaed;
  background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.25)!important;}
.wpcf7-form.sent .wpcf7-response-output{color:#d7f7e8;background:rgba(61,220,151,.15);
  border-color:#3DDC97!important;}
.wpcf7-form.invalid .wpcf7-response-output,.wpcf7-form.failed .wpcf7-response-output,
.wpcf7-form.spam .wpcf7-response-output{color:#ffd9dc;background:rgba(224,98,109,.15);
  border-color:#e0626d!important;}
.gl-form .wpcf7-not-valid-tip{color:#ff8a93;font-size:12px;margin-top:4px;}
@media(max-width:560px){.gl-form{padding:22px;}.gl-form .gl-row{grid-template-columns:1fr;}}
</style>
<div class="gl-form">
  <div class="gl-row">
    <div class="gl-field"><label class="gl-label">Ім'я *</label>[text* name placeholder "Ваше ім'я"]</div>
    <div class="gl-field"><label class="gl-label">Телефон *</label>[tel* phone placeholder "+380 67 123 45 67"]</div>
  </div>
  <div class="gl-row">
    <div class="gl-field"><label class="gl-label">Тип об'єкта *</label>[select* object_type "Квартира" "Будинок" "Комерція"]</div>
    <div class="gl-field"><label class="gl-label">Площа, м² *</label>[number* area placeholder "120"]</div>
  </div>
  <div class="gl-field"><label class="gl-label">Стадія ремонту *</label>[select* stage "Проєктування" "Чорновий" "Чистовий" "Готово"]</div>
  <div class="gl-field"><label class="gl-label">Що цікавить *</label>[checkbox interest use_label_element "Smart home" "Електрика" "Енергонезалежність" "Wireless"]</div>
  <div class="gl-field"><label class="gl-label">Коментар</label>[textarea comment]</div>
  [hidden source default:"gravity-wordpress"]
  <div class="gl-submit">[submit "Залишити заявку"]</div>
</div>
CF7;

// існує вже?
$existing = get_posts( [
    'post_type'   => 'wpcf7_contact_form',
    'title'       => 'Gravity — заявка smart home',
    'numberposts' => 1,
] );

$cf7 = $existing ? WPCF7_ContactForm::get_instance( $existing[0]->ID ) : WPCF7_ContactForm::get_template();
$cf7->set_title( 'Gravity — заявка smart home' );

$props = $cf7->get_properties();
$props['form'] = $form_template;

$props['mail']['recipient'] = get_option( 'admin_email' );
$props['mail']['subject']   = 'Нова заявка Gravity: [name]';
$props['mail']['body']      = "Ім'я: [name]\nТелефон: [phone]\nОб'єкт: [object_type], [area] м²\nСтадія: [stage]\nЗапит: [interest]\nКоментар: [comment]";

// конфіг CF7 to Webhook
$props['ctz_zapier'] = [
    'activate'           => '1',
    'hook_url'           => [ $WEBHOOK ],
    'send_mail'          => '0',          // пропустити реальний лист, але webhook надіслати
    'custom_method'      => 'POST',
    'files_send_content' => '0',
    'special_mail_tags'  => '',
    'custom_headers'     => '',
    'custom_body'        => '',           // порожньо = слати всі поля як JSON
    'error_mails'        => [ get_option( 'admin_email' ) ],
    'accepted_statuses'  => [ 200, 201, 202, 204, 205 ],
];

$cf7->set_properties( $props );
$cf7_id = $cf7->save();
WP_CLI::log( "CF7-форма: ID {$cf7_id}, webhook → {$WEBHOOK}" );

// ─── 2. Landing-сторінка ─────────────────────────────────────────────────────
$shortcode = '[contact-form-7 id="' . $cf7_id . '" title="Gravity — заявка smart home"]';

$content = <<<HTML
<!-- HERO -->
<section style="text-align:center;padding:48px 16px;background:#0f1115;color:#fff;border-radius:16px;">
  <h1 style="font-size:34px;margin:0 0 12px;">Підберемо smart home-рішення під ваш об'єкт</h1>
  <p style="font-size:18px;color:#b8bdc7;max-width:620px;margin:0 auto 24px;">Professional або wireless — підкажемо, що підійде саме вам, прорахуємо й змонтуємо під ключ.</p>
  <a href="#zayavka" style="display:inline-block;background:#3ddc97;color:#06281c;font-weight:700;padding:14px 28px;border-radius:10px;text-decoration:none;">Залишити заявку</a>
</section>

<!-- ЩО РОБИТЬ GRAVITY -->
<section style="padding:40px 16px;">
  <h2>Що робить Gravity</h2>
  <p>Ми проєктуємо, підбираємо обладнання, монтуємо й налаштовуємо системи розумного будинку та електрики. Працюємо з квартирами, будинками й комерцією на будь-якій стадії ремонту.</p>
</section>

<!-- PROFESSIONAL -->
<section style="padding:24px 16px;background:#f4f6f8;border-radius:12px;">
  <h2>Professional Smart Home</h2>
  <p><strong>Для кого:</strong> будинки, великі квартири, комерція; стадія проєктування або чорнового ремонту.</p>
  <p>Дротова централізована система (KNX-логіка): надійність, єдиний центр керування, клімат, освітлення, безпека, енергонезалежність. Потребує проєкту й закладки кабелів.</p>
</section>

<!-- WIRELESS -->
<section style="padding:24px 16px;margin-top:16px;background:#f4f6f8;border-radius:12px;">
  <h2>Wireless Smart Home</h2>
  <p><strong>Для кого:</strong> готовий ремонт, оренда, менші площі, точкові сценарії.</p>
  <p>Бездротові пристрої без штроблення стін: швидкий монтаж, гнучкість, керування зі смартфона. Ідеально, коли ремонт уже зроблено.</p>
</section>

<!-- ЯК ЦЕ ПРАЦЮЄ -->
<section style="padding:40px 16px;">
  <h2>Як це працює</h2>
  <ol>
    <li><strong>Заявка</strong> — лишаєте форму нижче.</li>
    <li><strong>Консультація</strong> — уточнюємо об'єкт і прораховуємо рішення.</li>
    <li><strong>Проєкт</strong> — підбираємо обладнання та готуємо кошторис.</li>
    <li><strong>Монтаж і налаштування</strong> — встановлюємо й передаємо під ключ.</li>
  </ol>
</section>

<!-- ФОРМА -->
<section id="zayavka" style="padding:40px 16px;background:#0f1115;color:#fff;border-radius:16px;">
  <h2 style="color:#fff;">Залишити заявку</h2>
  <p style="color:#b8bdc7;">Заповніть форму — менеджер зв'яжеться й допоможе підібрати рішення.</p>
  {$shortcode}
</section>

<!-- CTA -->
<section style="text-align:center;padding:40px 16px;">
  <h2>Не впевнені, що підійде?</h2>
  <p>Залиште заявку — підкажемо professional чи wireless під ваш бюджет і стадію ремонту.</p>
  <a href="#zayavka" style="display:inline-block;background:#3ddc97;color:#06281c;font-weight:700;padding:14px 28px;border-radius:10px;text-decoration:none;">Підібрати рішення</a>
</section>
HTML;

$page = get_page_by_path( 'pidibraty-smart-home' );
$pagearr = [
    'post_title'   => 'Підібрати smart home-рішення',
    'post_name'    => 'pidibraty-smart-home',
    'post_content' => $content,
    'post_status'  => 'publish',
    'post_type'    => 'page',
];
if ( $page ) {
    $pagearr['ID'] = $page->ID;
    $page_id = wp_update_post( $pagearr );
} else {
    $page_id = wp_insert_post( $pagearr );
}

WP_CLI::log( "Сторінка: ID {$page_id} → http://localhost:8080/?page_id={$page_id}" );
WP_CLI::success( 'Готово: форма + сторінка створені.' );
