<?php
/**
 * Оформлює сторінку «Підібрати smart home-рішення» в Elementor:
 * генерує _elementor_data (секції → колонки → віджети) з фонами, типографікою,
 * кнопками та вбудованою CF7-формою. Запуск:  wp eval-file wp-elementor.php
 */

$page = get_page_by_path( 'pidibraty-smart-home' );
if ( ! $page ) { WP_CLI::error( 'Сторінку pidibraty-smart-home не знайдено. Спершу запусти wp-setup.php' ); }
$page_id = $page->ID;

$cf7 = get_posts( [ 'post_type' => 'wpcf7_contact_form', 'title' => 'Gravity — заявка smart home', 'numberposts' => 1 ] );
$cf7_id = $cf7 ? $cf7[0]->ID : 6;

// ── helpers ──────────────────────────────────────────────────────────────────
function el_id() { return substr( md5( uniqid( '', true ) ), 0, 7 ); }

function widget( $type, $settings ) {
    return [ 'id' => el_id(), 'elType' => 'widget', 'widgetType' => $type, 'settings' => $settings, 'elements' => [] ];
}
function column( $size, $widgets, $settings = [] ) {
    return [ 'id' => el_id(), 'elType' => 'column', 'settings' => array_merge( [ '_column_size' => $size, '_inline_size' => $size ], $settings ), 'elements' => $widgets ];
}
function section( $columns, $settings = [] ) {
    return [ 'id' => el_id(), 'elType' => 'section', 'settings' => $settings, 'elements' => $columns ];
}
function pad( $v ) { return [ 'unit' => 'px', 'top' => $v, 'right' => '20', 'bottom' => $v, 'left' => '20', 'isLinked' => false ]; }
function fsize( $n ) { return [ 'unit' => 'px', 'size' => $n, 'sizes' => [] ]; }

$GREEN = '#3DDC97'; $DARK = '#0F1115'; $MUTED = '#B8BDC7'; $GREY = '#F4F6F8';

function heading( $title, $tag, $color, $size, $align = 'center' ) {
    return widget( 'heading', [
        'title' => $title, 'header_size' => $tag, 'align' => $align, 'title_color' => $color,
        'typography_typography' => 'custom', 'typography_font_size' => fsize( $size ), 'typography_font_weight' => '700',
    ] );
}
function text( $html, $color, $align = 'center', $size = 17 ) {
    return widget( 'text-editor', [
        'editor' => $html, 'align' => $align, 'text_color' => $color,
        'typography_typography' => 'custom', 'typography_font_size' => fsize( $size ), 'typography_line_height' => [ 'unit' => 'em', 'size' => 1.6 ],
    ] );
}
function cta_button( $text ) {
    global $GREEN;
    return widget( 'button', [
        'text' => $text, 'link' => [ 'url' => '#zayavka', 'is_external' => '', 'nofollow' => '' ], 'align' => 'center',
        'background_color' => $GREEN, 'button_text_color' => '#06281C', 'border_radius' => [ 'unit' => 'px', 'top' => '10', 'right' => '10', 'bottom' => '10', 'left' => '10', 'isLinked' => true ],
        'text_padding' => [ 'unit' => 'px', 'top' => '14', 'right' => '30', 'bottom' => '14', 'left' => '30', 'isLinked' => false ],
        'typography_typography' => 'custom', 'typography_font_size' => fsize( 16 ), 'typography_font_weight' => '700',
    ] );
}

// ── секції ───────────────────────────────────────────────────────────────────
$data = [];

// HERO
$data[] = section( [ column( 100, [
    heading( "Підберемо smart home-рішення під ваш об'єкт", 'h1', '#FFFFFF', 38 ),
    text( '<p>Professional або wireless — підкажемо, що підійде саме вам, прорахуємо й змонтуємо під ключ.</p>', $MUTED ),
    cta_button( 'Залишити заявку' ),
] ) ], [ 'background_background' => 'classic', 'background_color' => $DARK, 'padding' => pad( '80' ) ] );

// ЩО РОБИТЬ GRAVITY
$data[] = section( [ column( 100, [
    heading( 'Що робить Gravity', 'h2', $DARK, 30 ),
    text( '<p>Ми проєктуємо, підбираємо обладнання, монтуємо й налаштовуємо системи розумного будинку та електрики. Працюємо з квартирами, будинками й комерцією на будь-якій стадії ремонту.</p>', '#444444' ),
] ) ], [ 'padding' => pad( '60' ) ] );

// PROFESSIONAL / WIRELESS (2 колонки)
$data[] = section( [
    column( 50, [
        heading( 'Professional Smart Home', 'h3', $DARK, 24 ),
        text( '<p><strong>Для кого:</strong> будинки, великі квартири, комерція; стадія проєктування або чорнового ремонту.</p><p>Дротова централізована система (KNX-логіка): надійність, єдиний центр керування, клімат, освітлення, безпека, енергонезалежність.</p>', '#444444', 'left', 16 ),
    ], [ 'background_background' => 'classic', 'background_color' => '#FFFFFF', 'padding' => pad( '30' ), 'border_radius' => [ 'unit' => 'px', 'top' => '12', 'right' => '12', 'bottom' => '12', 'left' => '12', 'isLinked' => true ] ] ),
    column( 50, [
        heading( 'Wireless Smart Home', 'h3', $DARK, 24 ),
        text( '<p><strong>Для кого:</strong> готовий ремонт, оренда, менші площі, точкові сценарії.</p><p>Бездротові пристрої без штроблення стін: швидкий монтаж, гнучкість, керування зі смартфона.</p>', '#444444', 'left', 16 ),
    ], [ 'background_background' => 'classic', 'background_color' => '#FFFFFF', 'padding' => pad( '30' ), 'border_radius' => [ 'unit' => 'px', 'top' => '12', 'right' => '12', 'bottom' => '12', 'left' => '12', 'isLinked' => true ] ] ),
], [ 'background_background' => 'classic', 'background_color' => $GREY, 'padding' => pad( '50' ), 'gap' => 'wide' ] );

// ЯК ЦЕ ПРАЦЮЄ
$data[] = section( [ column( 100, [
    heading( 'Як це працює', 'h2', $DARK, 30 ),
    text( '<ol style="max-width:640px;margin:0 auto;text-align:left;font-size:17px;line-height:1.8;"><li><strong>Заявка</strong> — лишаєте форму нижче.</li><li><strong>Консультація</strong> — уточнюємо об\'єкт і прораховуємо рішення.</li><li><strong>Проєкт</strong> — підбираємо обладнання та готуємо кошторис.</li><li><strong>Монтаж і налаштування</strong> — встановлюємо й передаємо під ключ.</li></ol>', '#444444' ),
] ) ], [ 'padding' => pad( '60' ) ] );

// ФОРМА (anchor #zayavka)
$data[] = section( [ column( 100, [
    heading( 'Залишити заявку', 'h2', '#FFFFFF', 30 ),
    text( '<p>Заповніть форму — менеджер зв\'яжеться й допоможе підібрати рішення.</p>', $MUTED ),
    widget( 'shortcode', [ 'shortcode' => '[contact-form-7 id="' . $cf7_id . '"]' ] ),
] ) ], [ '_element_id' => 'zayavka', 'background_background' => 'classic', 'background_color' => $DARK, 'padding' => pad( '60' ) ] );

// CTA
$data[] = section( [ column( 100, [
    heading( 'Не впевнені, що підійде?', 'h2', $DARK, 28 ),
    text( '<p>Залиште заявку — підкажемо professional чи wireless під ваш бюджет і стадію ремонту.</p>', '#444444' ),
    cta_button( 'Підібрати рішення' ),
] ) ], [ 'padding' => pad( '60' ) ] );

// ── зберегти ─────────────────────────────────────────────────────────────────
update_post_meta( $page_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
update_post_meta( $page_id, '_elementor_edit_mode', 'builder' );
update_post_meta( $page_id, '_elementor_template_type', 'wp-page' );
update_post_meta( $page_id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.0.0' );
update_post_meta( $page_id, '_wp_page_template', 'elementor_header_footer' );

// очистити кеш CSS, щоб згенерувався наново
if ( class_exists( '\Elementor\Plugin' ) ) {
    \Elementor\Plugin::$instance->files_manager->clear_cache();
}

WP_CLI::success( "Elementor оформлення застосовано до сторінки {$page_id} (CF7 id {$cf7_id})." );
