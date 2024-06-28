<?php

/**
 * Plugin Name: [ PHPavel Scripts Page ]
 * Description: Страница добавление скриптов в настройки
 * Author URI:  https://philippovpavel.ru
 * Author:      PhilippovPavel
 *
 * Network:     true
 * Version:     1.0
 */

if (!defined('ABSPATH')) exit;

register_uninstall_hook(__FILE__, ['phpavelScriptsPage', 'uninstall']);

class phpavelScriptsPage
{
  public static function uninstall()
  {
    if (!current_user_can('activate_plugins')) return;

    $fields = self::fields;
    if (!$fields) return;

    foreach ($fields as $field) {
      $option_name = $field['id'];

      delete_option($option_name);
    }
  }

  public function __construct()
  {
    add_action('admin_menu', [$this, 'fields_script_page'], 6);
    add_action('admin_init', [$this, 'scripts_fields']);

    add_action('admin_enqueue_scripts', [$this, 'code_editor']);

    add_action('wp_head', [$this, 'add_inline_js_to_head'], 100);
    add_action('wp_footer', [$this, 'add_inline_js_to_footer'], 100);
  }

  private const fields = [
    [
      'id' => 'script_head',
      'name' => 'JS в шапке',
    ],
    [
      'id' => 'script_footer',
      'name' => 'JS в подвале',
    ],
  ];

  function fields_script_page()
  {
    add_submenu_page(
      'options-general.php',
      'Скрипты',
      'Скрипты',
      'manage_options',
      'phpavel_scripts',
      [$this, 'scripts_page_callback']
    );
  }

  function scripts_page_callback()
  {
    echo '<div class="wrap">
    <h1>' . get_admin_page_title() . '</h1>
    <form method="post" action="options.php">';

    settings_fields('phpavel_scripts_settings'); // название настроек
    do_settings_sections('phpavel_scripts'); // ярлык страницы, не более
    submit_button(); // функция для вывода кнопки сохранения

    echo '</form></div>';
  }

  function scripts_fields()
  {
    // добавляем секцию без заголовка
    add_settings_section(
      'scripts_settings_section_id', // ID секции, пригодится ниже
      '', // заголовок (не обязательно)
      '', // функция для вывода HTML секции (необязательно)
      'phpavel_scripts' // ярлык страницы
    );

    $fields_array = self::fields;
    if (!$fields_array) return;

    foreach ($fields_array as $field) {
      $fn_clear = [$this, 'strip_tags_for_script'];
      $fn_callback = [$this, 'codemirror_field'];

      // регистрируем опцию
      register_setting(
        'phpavel_scripts_settings', // название настроек из предыдущего шага
        $field['id'], // ярлык опции
        $fn_clear // функция очистки
      );

      // добавление поля
      add_settings_field(
        $field['id'],
        $field['name'],
        $fn_callback, // название функции для вывода
        'phpavel_scripts', // ярлык страницы
        'scripts_settings_section_id', // ID секции, куда добавляем опцию
        [
          'label_for'     => $field['id'],
          'class'         => $field['id'] . '-class', // для элемента <tr>
          'name'          => $field['id'], // любые доп параметры в колбэк функцию
          'input_classes' => 'regular-text',
        ]
      );
    }
  }

  function strip_tags_for_script($value)
  {
    return strip_tags($value, '<script>');
  }

  function codemirror_field($args)
  {
    // получаем значение из базы данных
    $value = get_option($args['name']);

    echo '<textarea id="' . esc_attr($args['name']) . '" class="' . esc_attr($args['input_classes']) . '" name="' . esc_attr($args['name']) . '" rows="4" cols="30">' . $value . '</textarea>';
    echo '<p class="description" id="' . esc_attr($args['name']) . '-description">
      <b>Важно!</b> Добавляйте и редактируйте код в данном поле на свой страх и риск!<br>
      Не забывайте обрамлять код в теги: <code>&lt;script&gt;&lt;/script&gt;</code>
    </p>';
  }

  function code_editor()
  {
    if ('settings_page_phpavel_scripts' !== get_current_screen()->id) return;

    $settings = wp_enqueue_code_editor(array('type' => 'text/html'));

    if (false === $settings) return;

    $scripts_array = array('script_head', 'script_footer');

    foreach ($scripts_array as $key => $script_id) {
      wp_add_inline_script(
        'code-editor',
        sprintf('jQuery( function() { wp.codeEditor.initialize( "' . $script_id . '", %s ); } );', wp_json_encode($settings))
      );
    }
  }

  function add_inline_js_to_head()
  {
    echo (get_option('script_head') ?: '');
  }

  function add_inline_js_to_footer()
  {
    echo (get_option('script_footer') ?: '');
  }
}

$phpavelScriptsPage = new phpavelScriptsPage();
