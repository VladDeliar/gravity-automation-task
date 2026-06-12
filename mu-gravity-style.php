<?php
/**
 * Plugin Name: Gravity Site Styles
 * Description: Дрібні стильові правки фронтенду Gravity. Прибирає підкреслення та
 *              квадратну обводку (focus-outline) із заголовків-посилань, назви сайту
 *              й пунктів меню в усіх станах. Фокус кнопок/полів форми не чіпає.
 */

add_action( 'wp_head', function () {
    echo '<style id="gravity-site-styles">
/* Назва сайту, заголовки постів, заголовки-посилання, меню —
   без підкреслення і без обводки/рамки у ВСІХ станах */
.wp-block-site-title a,
.wp-block-post-title a,
.entry-title a,
.wp-block-navigation a,
.wp-block-navigation-item a,
.wp-block-navigation-item__content,
h1 a, h2 a, h3 a, h4 a, h5 a, h6 a,

.wp-block-site-title a:hover, .wp-block-site-title a:focus, .wp-block-site-title a:focus-visible, .wp-block-site-title a:active,
.wp-block-post-title a:hover, .wp-block-post-title a:focus, .wp-block-post-title a:focus-visible, .wp-block-post-title a:active,
.entry-title a:hover, .entry-title a:focus, .entry-title a:focus-visible, .entry-title a:active,
.wp-block-navigation a:hover, .wp-block-navigation a:focus, .wp-block-navigation a:focus-visible, .wp-block-navigation a:active,
.wp-block-navigation-item a:hover, .wp-block-navigation-item a:focus, .wp-block-navigation-item a:focus-visible, .wp-block-navigation-item a:active,
.wp-block-navigation-item__content:hover, .wp-block-navigation-item__content:focus, .wp-block-navigation-item__content:focus-visible,
h1 a:hover, h2 a:hover, h3 a:hover, h4 a:hover, h5 a:hover, h6 a:hover,
h1 a:focus, h2 a:focus, h3 a:focus, h4 a:focus, h5 a:focus, h6 a:focus{
  text-decoration:none !important;
  outline:none !important;
  box-shadow:none !important;
  border-color:transparent !important;
}
</style>';
}, 100 );
