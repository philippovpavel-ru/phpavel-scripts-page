<?php
/**
* Plugin Name: [ Scripts Page ]
* Description: Страница добавление скриптов в настройки
* Author URI:  https://philippovpavel.ru
* Author:      PhilippovPavel
*
* Network:     true
* Version:     1.0
*/

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'phpavel_fields_script_page', 6 );
add_action( 'admin_init', 'phpavel_scripts_fields' );

add_action( 'admin_enqueue_scripts', 'phpavel_code_editor' );

add_action( 'wp_head',   'phpavel_add_inline_js_to_head', 100 );
add_action( 'wp_footer', 'phpavel_add_inline_js_to_footer', 100 );

function phpavel_fields_script_page() {
	add_submenu_page(
		'options-general.php',
		'Скрипты',
		'Скрипты',
		'manage_options',
		'phpavel_scripts',
		'phpavel_scripts_page_callback'
	);
}

function phpavel_scripts_page_callback() {
	echo '<div class="wrap">
	<h1>' . get_admin_page_title() . '</h1>
	<form method="post" action="options.php">';

		settings_fields( 'phpavel_scripts_settings' ); // название настроек
		do_settings_sections( 'phpavel_scripts' ); // ярлык страницы, не более
		submit_button(); // функция для вывода кнопки сохранения

	echo '</form></div>';
}

function phpavel_scripts_fields() {
	// добавляем секцию без заголовка
	add_settings_section(
		'scripts_settings_section_id', // ID секции, пригодится ниже
		'', // заголовок (не обязательно)
		'', // функция для вывода HTML секции (необязательно)
		'phpavel_scripts' // ярлык страницы
	);

	$fields_array = [[
		'id'            => 'script_head',
		'name'          => 'JS в шапке',
		'fn_clear'      => '',
		'fn_callback'   => 'phpavel_codemirror_field',
		'input_classes' => 'regular-text',
	],
	[
		'id'            => 'script_footer',
		'name'          => 'JS в подвале',
		'fn_clear'      => '',
		'fn_callback'   => 'phpavel_codemirror_field',
		'input_classes' => 'regular-text',
	]];

	if( ! $fields_array ) return;

	foreach ( $fields_array as $key => $field ) {
		// регистрируем опцию
		register_setting(
			'phpavel_scripts_settings', // название настроек из предыдущего шага
			$field['id'], // ярлык опции
			$field['fn_clear'] // функция очистки
		);

		// добавление поля
		add_settings_field(
			$field['id'],
			$field['name'],
			$field['fn_callback'], // название функции для вывода
			'phpavel_scripts', // ярлык страницы
			'scripts_settings_section_id', // ID секции, куда добавляем опцию
			[
				'label_for'     => $field['id'],
				'class'         => $field['id'] . '-class', // для элемента <tr>
				'name'          => $field['id'], // любые доп параметры в колбэк функцию
				'input_classes' => $field['input_classes'],
			]
		);
	}
}

function phpavel_codemirror_field( $args ) {
	// получаем значение из базы данных
	$value = get_option( $args[ 'name' ] );

	echo '<textarea id="' . esc_attr( $args[ 'name' ] ) . '" class="' . esc_attr( $args[ 'input_classes' ] ) . '" name="' . esc_attr( $args[ 'name' ] ) . '" rows="4" cols="30">' . $value . '</textarea>';
	echo '<p class="description" id="' . esc_attr( $args[ 'name' ] ) . '-description">
		<b>Важно!</b> Добавляйте и редактируйте код в данном поле на свой страх и риск!<br>
		Не забывайте обрамлять код в теги: <code>&lt;script&gt;&lt;/script&gt;</code>
	</p>';
}

function phpavel_code_editor() {
	if ( 'settings_page_phpavel_scripts' !== get_current_screen()->id ) return;

	$settings = wp_enqueue_code_editor( array( 'type' => 'text/html' ) );

	if ( false === $settings ) return;

	$scripts_array = array( 'script_head', 'script_footer' );

	foreach ( $scripts_array as $key => $script_id ) {
		wp_add_inline_script(
			'code-editor',
			sprintf( 'jQuery( function() { wp.codeEditor.initialize( "' . $script_id. '", %s ); } );', wp_json_encode( $settings ) )
		);
	}
}

function phpavel_add_inline_js_to_head() {
	echo ( get_option( 'script_head' ) ?: '' );
}

function phpavel_add_inline_js_to_footer() {
	echo ( get_option( 'script_footer' ) ?: '' );
}