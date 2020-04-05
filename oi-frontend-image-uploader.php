<?php
/*
Plugin Name: Oi Frontend Image Uploader
Plugin URI: https://oiplug.com/plugins/oi-frontend-image-uploader
Description: ...
Version: 1.0
Author: Alexei Isaenko
Author URI: https://oiplug.com/users/isaenko_alexei
License: GPL2

Date: 22.11.17
Time: 20:03
*/

namespace oifrontend\image_uploader;

use function oifrontend\oifrontend;

/**
 * Function returns path to the current plugin: `/htdocs/wp-content/plugins/oi-frontend/`
 *
 * @return string
 */
function plugin_path() {
	return plugin_dir_path( __FILE__ );
}

/**
 * Function returns name of current plugin directory: `oi-frontend`
 *
 * @return string
 */
function plugin_name() {
	return plugin_basename( plugin_path() );
}

/**
 * Filtering get_avatar_url function
 *
 * @param $args
 * @param $user_id
 *
 * @return mixed
 */
function set_avatar_url( $args, $user_id ) {
	global $wp_query;
	if ( ! is_numeric( $user_id ) ) {
		$user    = get_user_by( 'email', $user_id );
		$user_id = $user->ID;
	}
	if ( empty( trim( $user_id ) ) ) {
		$user_id = get_queried_object_id();
	}
	$url = get_user_meta( $user_id, 'user_photo', true );
//if(current_user_can('administrator')){echo '<pre>';print_r($user_id);print_r($args);echo '</pre>';}
	// if user doesn't set the avatar
	if ( empty( trim( $url ) ) ) {

		if ( empty( trim( $args['url'] ) ) ) {
			// show default image
			$url = apply_filters( 'set_avatar_url', plugins_url() . '/' . plugin_name() . '/images/user.svg?' . $user_id, $user_id );
		} else {
			$url = $args['url'];
		}
	}

	$args['url'] = $url;

	return $args;
}

add_action( 'get_avatar_data', '\oifrontend\image_uploader\set_avatar_url', 100, 2 );

/**
 * Шорткод для загрузки изображения
 *
 * @param null $atts
 *
 * @return string
 */
function uploadable_image( $atts = null ) {

	$atts = shortcode_atts( array(
		'post_id'     => 0,
		'user_id'     => 0,
		// условие - можно ли изменять выводимое изображение
		'can_edit'    => true,
		// путь к изображению
		'image'       => '',
		// стили блока с изображением
		'styles'      => '',
		// минимальная размер изображения, с этим размером на картинке появится обрезалка
		'width'       => 100,
		'height'      => 100,
		// максимальный размер изображения, котрый можно выбрать
		'max_side'    => 600,
		'destination' => 'avatar',
		'size'        => 'contain',
		// image upload template form name
		'slug'        => 'form',
		// image upload template form name variation
		'name'        => 'crop-upload',
	), $atts );

	/*	if ( empty( $atts['image'] ) && !empty($atts['user_id'] ) ) {
			return false;
		}*/

	$user_id = $atts['user_id'];

	if ( empty( $user_id ) ) {

		// получение id того пользователя, страница которого сейчас просмтривается
		$user_id = get_displayed_user_id();

		// если вернулось пустое значение
		if ( empty( $user_id ) ) {

			// устанаваливается значение id текущего пользователя
			$user_id = get_current_user_id();
		}
	}

	// определение пути к изображению
	switch ( $atts['destination'] ) {
		case 'post_thumbnail':

			// get post thumbnail url
			$atts['image'] = get_the_post_thumbnail_url( $atts['post_id'], 'full' );
			break;
		case 'avatar':

			// получение пути к аватару текущего пользователя
			$atts['image'] = get_user_meta( $user_id, 'user_photo', true );
			break;
		default:

			// получение пути к аватару текущего пользователя
			$atts['image'] = get_user_meta( $user_id, 'user_' . $atts['destination'], true );

	}


	$atts['params'] = ' data-width="' . $atts['width'] . '"' .
	                  ' data-height="' . $atts['height'] . '"' .
	                  ' data-max_side="' . $atts['max_side'] . '"' .
	                  ' data-post_id="' . $atts['post_id'] . '"' .
	                  '';

	// если имя шаблона загрузки изображения указано
	if ( ! empty( $atts['name'] ) ) {
		$file_part = "{$atts['slug']}-{$atts['name']}.php";
	} else {
		$file_part = "{$atts['slug']}";
	}

	// set dynamic filter name placed in \oifrontend\the_template_part
	$action_name = 'oifrontend_get_template_part_' . $file_part . '_filter';

	// template path
	$template = trailingslashit( plugin_path() ) . trailingslashit( 'templates' ) . $file_part . '.php';

	// add own template path for image upload form
	add_action( $action_name, $template, 10 );

	// load image upload form template
	$image = get_template_part( $atts['slug'], $atts['name'], [
		'image'       => $atts['image'],
		'styles'      => $atts['styles'],
		'size'        => $atts['size'],
		'destination' => $atts['destination'],
		'params'      => $atts['params'],
		'can_edit'    => $atts['can_edit'],
	] );

	// Removes the last anonymous filter to be added
	remove_filter( $action_name, function () {
	} );

	wp_enqueue_script( 'fileapi' );
	wp_enqueue_script( 'jquery-fileapi' );
	wp_enqueue_script( 'jcrop' );
	wp_enqueue_script( 'jquery-modal' );
	wp_enqueue_script( 'upload-image-functions' );

	return $image;
}

add_shortcode( 'upload_image', '\oifrontend\image_uploader\uploadable_image' );

/**
 * Функция получения id пользователя, чья страница сейчас просматривается
 *
 * @return int
 */
function get_displayed_user_id() {

	if ( get_query_var( 'pagename' ) ) {
		$user_id = get_current_user_id();
	} else {
		$user_id = get_the_author_meta( 'ID' );
	}

	$user_id = absint( $user_id );

	return $user_id;
}

function register_scripts() {

	wp_register_script(
		'fileapi',
		plugins_url() . '/' . plugin_name() . '/js/FileAPI/FileAPI.min.js',
		array( 'jquery' ),
		'2017-11-07',
		true
	);
	//wp_enqueue_script( 'fileapi' );

	wp_register_script(
		'jquery-fileapi',
		plugins_url() . '/' . plugin_name() . '/js/FileAPI/jquery.fileapi.min.js',
		array( 'jquery' ),
		'2017-11-07',
		true
	);
	//wp_enqueue_script( 'jquery-fileapi' );

	wp_register_script(
		'jcrop',
		plugins_url() . '/' . plugin_name() . '/js/jCrop/js/jquery.Jcrop.js',
		array( 'jquery' ),
		'2017-11-07',
		true
	);
	//wp_enqueue_script( 'jcrop' );

	wp_register_script(
		'jquery-modal',
		plugins_url() . '/' . plugin_name() . '/js/jquery.modal.js',
		array( 'jquery' ),
		'2017-11-07',
		true
	);
	//wp_enqueue_script( 'jquery-modal' );

	wp_register_script(
		'upload-image-functions',
		plugins_url() . '/' . plugin_name() . '/js/functions.js',
		array( 'jquery' ),
		'2017-11-07',
		true
	);

	wp_localize_script(
		'upload-image-functions',
		'oifrontend_object',
		array(
			'ajax_url'   => admin_url( 'admin-ajax.php' ),
			'ajax_nonce' => wp_create_nonce( 'oiproaccount' ),
			'user_id'    => get_displayed_user_id(),
			/*'oiproaccount_is_admin' => oiproaccount_is_admin(),
			'current_user_id'       => get_current_user_id(),
			'pro_rating'            => $pro_rating,
			// tc
			'pro_is_on'             => oiproaccount_get_option( 'pro_is_on' ),
			'spaced_services'       => oiproaccount_get_option( 'spaced_services' ),
			'spaced_spaces'         => oiproaccount_get_option( 'spaced_spaces' ),
			'spaced_styles'         => oiproaccount_get_option( 'spaced_styles' ),
			//oiproaccount_get_option( 'spaced_styles' ),
			// рейтинг текущего пользователя

			//список имен полей, заполненность которых влияет на рейтинг
			//сами поля выводятся в профиле, шаблон pro_settings.php

			'pro_rating_names'      => oiproaccount_get_option( 'pro_rating_names' ),
			// проверка - является ли просматриваемая страница PRO
			'edit_published_posts'  => current_user_can( 'edit_published_posts' ),
			'is_pro'                => is_pro(),
			// минимальная ширина загружаемого в проект изображения
			'min_width'             => 600,
			// минимальная ширина загружаемого в проект изображения
			'min_height'            => 0,
			// минимальная ширина загружаемого в проект изображения
			'reduce_width'          => 1024,
			// минимальная высота загружаемого в проект изображения
			'reduce_height'         => 0,
			// 1 - вставлять при загрузке как фон блока, 0 - как canvas
			'as_background'         => 0,
			// лимит загружаемых в проект изображений
			'images_limit'          => oiproaccount_get_option( 'images_limit' ),
			'errors'                => array(
				'1' => __( 'Необходимо указать название проекта.', 'oi-pro-account' ),
				'2' => __( 'Подробно опишите этот проект.', 'oi-pro-account' ),
				'3' => __( 'Необходимо указать тип услуги проекта.', 'oi-pro-account' ),
				'4' => __( 'В проекте должна быть обложка.', 'oi-pro-account' ),
				'5' => __( 'В пространстве №', 'oi-pro-account' ) . '%i% ' . __( 'должено быть хотябы одно изображение.', 'oi-pro-account' ),
				//'6' => __( 'Для пространства №', 'oi-pro-account' ) . '%i% ' . __( 'должен быть определен', 'oi-pro-account' ) . ' ' . __( 'тип', 'oi-pro-account' ) . '.',
				'6' => __( 'Укажите тип помещения', 'oi-pro-account' ) . '.',
				//'7' => __( 'Для пространства №', 'oi-pro-account' ) . '%i% ' . __( 'должен быть определен', 'oi-pro-account' ) . ' ' . __( 'стиль', 'oi-pro-account' ) . '.',
				'7' => __( 'Укажите стиль помещения', 'oi-pro-account' ) . '.',
				'8' => __( 'В проект необходимо добавить хотябы одно пространство.', 'oi-pro-account' ),
				'9' => __( 'В проекте нет ни одного изображения.', 'oi-pro-account' ),
			),*/
		)
	);

	//wp_enqueue_script( 'upload-image-functions' );

}

add_action( 'wp_enqueue_scripts', '\oifrontend\image_uploader\register_scripts' );

function enque_styles() {
	wp_enqueue_style( 'jcrop', plugins_url() . '/' . plugin_name() . '/js/jCrop/css/jquery.Jcrop.css' );
	wp_enqueue_style( 'oifrontend-image_uploader', plugins_url() . '/' . plugin_name() . '/css/style.css' );
}

add_action( 'wp_enqueue_scripts', '\oifrontend\image_uploader\enque_styles' );

/**
 * Returns the user's avatar uploading block.
 *
 * @return string
 */
function get_user_avatar() {
	$atts = apply_filters( 'get_user_avatar', array(
		'width'       => 300,
		'height'      => 300,
		'max_side'    => 600,
		'destination' => 'avatar',
		'styles'      => 'width:150px;height:150px;',
		'image'       => get_user_meta( get_displayed_user_id(), 'user_avatar', true ),
	) );

	return uploadable_image( $atts );
}

/**
 * Prints the user's avatar uploading block.
 */
function the_user_avatar() {
	echo get_user_avatar();
}

add_action( 'profile_personal_options', '\oifrontend\image_uploader\the_user_avatar' );


/**
 * Getting template file path.
 *
 * @param string $slug
 * @param null   $name
 *
 * @return string
 */
function get_template_path( $slug, $name = null ) {

	$pathes = [];

	if ( ! empty( $name ) ) {
		$file = "{$slug}-{$name}.php";
	} else {
		$file = "{$slug}.php";
	}

	// template from theme root
	$pathes[] = trailingslashit( get_stylesheet_directory() ) . $file;

	// template from plugin directory in the theme
	$pathes[] = trailingslashit( get_stylesheet_directory() ) . trailingslashit( plugin_name() ) . $file;

	// template from plugin directory
	$pathes[] = trailingslashit( plugin_path() ) . trailingslashit( 'templates' ) . $file;
	$pathes[] = trailingslashit( plugin_path() ) . $file;

	// filter $pathes
	$pathes = apply_filters( 'oifrontend_template_path', $pathes, $file );

	if ( ! empty( $pathes ) ) {
		foreach ( $pathes as $path ) {

			if ( file_exists( $path ) ) {

				return $path;
			}
		}
	}

	$path = '';

	return $path;
}

/**
 * Getting template as a string.
 *
 * @param string $slug
 * @param null   $name
 * @param array  $atts
 * @param array  $default
 * @param string $query_var
 *
 * @return string
 */
function get_template_part( $slug, $name = null, $atts = [], $default = [], $query_var = '' ) {

	ob_start();

	the_template_part( $slug, $name, $atts, $default, $query_var );

	return ob_get_clean();
}


function the_template_part( $slug, $name = null, $atts = [], $default = [], $query_var = '' ) {
	$atts = wp_parse_args( $atts, $default );


	// set or extract variables
	if ( ! empty( $atts ) ) {
		if ( ! empty( $query_var ) ) {
			set_query_var( 'template_' . $query_var . '_vars', $atts );
		} else {
			extract( $atts, EXTR_SKIP );
		}
	}

	$action_name = 'oifrontend' . '_get_template_part_' . $slug;
	if ( ! empty( $name ) ) {
		$action_name .= '-' . $name;
	}

	do_action( $action_name, $slug, $name, $atts );

	// filter $template
	$template = apply_filters( $action_name . '_filter', get_template_path( $slug, $name ), $slug, $name );

	if ( ! empty( $template ) && file_exists( $template ) ) {
		include $template;
	}
}

/**
 * Determine if user look on it's own page.
 *
 * @return bool
 */
function is_my_page() {
//	wp_send_json_error( [get_displayed_user_id(),get_current_user_id()] );
	if ( get_displayed_user_id() == get_current_user_id() ) {

		return true;
	}

	return false;
}

/**
 * Determine is current user able to do something.
 *
 * @return bool
 */
function is_moderator() {
	if ( current_user_can( 'administrator' ) ) {
		return true;
	}

	return false;
}


function upload_file( $atts = [] ) {
	if ( ! function_exists( 'wp_handle_upload' ) ) {
		/** @noinspection PhpIncludeInspection */
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
	}

	if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
		/** @noinspection PhpIncludeInspection */
		include( ABSPATH . 'wp-admin/includes/image.php' );
	}

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( [
			'errors' => [
				__( 'Необходимо аторизоваться.', __NAMESPACE__ ),
			],
		] );
	}

	// если пользователь не авторизован
	if ( ! is_user_logged_in() ) {

		wp_send_json_error( [
			'errors' => [
				__( 'Необходимо аторизоваться, чтобы загрузить изображение для этой страницы.', __NAMESPACE__ ),
			],
		] );
	}

	// если данные переданы
	if ( ! empty( $_POST['data'] && is_array( $_POST['data'] ) ) ) {
		$atts = $_POST['data'];
	}

	$atts = wp_parse_args( $atts, [
		'user_id'     => 0,
		'post_id'     => 0,
		'url'         => null,
		'width'       => 300,
		'height'      => 300,
		'max_side'    => 1080,
		'destination' => 'post_thumbnail',
		'size'        => 'contain',
		'type'        => 'avatar',
		// timeout for file downloading via url(seconds)
		'timeout'     => 5,
	] );

	$user_id = absint( $atts['user_id'] );

	// set user id
	if ( empty( $user_id ) || ! is_moderator() ) {
		$user_id = get_current_user_id();
	}

	$post_id = absint( $atts['post_id'] );

	// типы изображений, которые хранятся в user meta
	$user_meta = array( 'avatar', 'cover' );

	// use filter to set upload dir for some types of uploadable files
	switch ( $atts['type'] ) {
		case 'avatar':
		case 'cover':

			// информация по текущему пользователю
			$user_info = get_userdata( $user_id );

			// определение заголовка изображения
			$atts['post_title'] = $user_info->first_name . ' ' . $user_info->last_name;
			break;
	}

	// file has been submitted via form, url to file not setted
	if ( empty( $atts['url'] ) ) {

		if ( ! empty( $_FILES['filedata'] ) ) {

			// данные загружаемого файла
			$filedata = $_FILES['filedata'];
		} else {

			$filedata = $_FILES[0];
		}

		// set test uploading flag to false
		$upload_overrides = array(

			// реальная згрузка, а не тест
			'test_form' => false,
		);

		// установка имени загружаемого файла
		switch ( $atts['type'] ) {
			case 'avatar':
			case 'cover':

				// определение нового имени файла
				$upload_overrides['unique_filename_callback'] = function ( $dir, $name, $ext ) use ( $atts, $user_id ) {
					return strtolower( $atts['type'] . '_' . $user_id . $ext );
				};
				break;
		}

		// загрузка файла
		$filedata = wp_handle_upload( $filedata, $upload_overrides );
	} else {
		// указана ссылка на файл на удаленном сервере

		// загрузка файла во временную директорию
		$temp_file = download_url( $atts['url'], $atts['timeout'] );

		if ( is_wp_error( $temp_file ) ) {
			wp_send_json_error( [ 'line_' . __LINE__ => $temp_file ] );
		}

		// массив аналогичный массиву $_FILE
		$file = array(
			'name'     => basename( $atts['url'] ),
			// todo: определить тип
			'type'     => 'image/png',
			'tmp_name' => $temp_file,
			'error'    => 0,
			'size'     => filesize( $temp_file ),
		);

		$overrides = array(
			// не проверять переменную POST, так как загрузка идет не через форму
			'test_form'   => false,

			// запрет загрузки пустого файла
			'test_size'   => true,

			// проверка правильности загрузки файла
			'test_upload' => true,
		);

		// перемещение временного файла в папку загрузки
		$filedata = wp_handle_sideload( $file, $overrides );

	}

	// настройки аттачмента
	$attachment = array(
		'guid'           => ! empty( $filedata['url'] ) ? $filedata['url'] : '',
		'post_mime_type' => ! empty( $filedata['type'] ) ? $filedata['type'] : '',
		'post_title'     => ! empty( $atts['post_title'] ) ? $atts['post_title'] : '',
		'post_content'   => '',
		'post_status'    => 'inherit',
	);


	/* -- работа с размером и качеством изображения -- */

	// создание объекта изображения для проведения манипуляций с файлом, лежащим на локальном сервере
	$image = wp_get_image_editor( $filedata['file'] );

	if ( is_wp_error( $image ) ) {
		wp_send_json_error( [ 'line_' . __LINE__ => $image ] );
	}

	// установка качества изображения в процентах
	$image->set_quality( 100 );

	// изменение размера изображения с последующим кропом
	$image->resize( $atts['width'], $atts['height'], true );

	// cохранение изображения с указанием его mime-type
	$image->save( $filedata['file'], $attachment['post_mime_type'] );

	// файл аттачмента помещается в медиатеку и привязывается к указанному посту
	$attachment_id = wp_insert_attachment( $attachment, $filedata['file'], $post_id );
//	wp_send_json_success( $attachment_id );
	// обновление атрибута alt загруженного изображения
	update_post_meta( $attachment_id, '_wp_attachment_image_alt', $atts['post_title'] );

	switch ( $atts['type'] ) {
		case 'avatar':
		case 'cover':

			// генерация времени загрузки, для борьбы с отображением закэшированного изображения
			$date = '?' . date( 'His' );
			switch ( $atts['type'] ) {
				case 'avatar':

					// обновление ссылки на файл аватара в поле, которое использует ulogin
					update_user_meta( $user_id, 'user_photo', $filedata['url'] . $date );

					// обновление ссылки на файл аватара
					update_user_meta( $user_id, 'user_' . $atts['type'], $filedata['url'] . $date );

					// обновление id файла аватара
					update_user_meta( $user_id, 'user_' . $atts['type'] . '_id', $attachment_id );
					break;
				case 'cover':

					// обновление ссылки на файл
					update_user_meta( $user_id, 'user_' . $atts['type'], $filedata['url'] );

					// обновление id файла
					update_user_meta( $user_id, 'user_' . $atts['type'] . '_id', $attachment_id );
					break;
			}

			break;
		case 'post_thumbnail':

			// определение текущей миниатюры поста
			$old_attachment_id = get_post_thumbnail_id( $post_id );

			// удаление текущей миниатюры поста из медиатеки
			wp_delete_attachment( $old_attachment_id );

			// установка изображения в качестве миниатюры поста
			set_post_thumbnail( $post_id, $attachment_id );

			break;
	}

	// генерация миниатюр аттачмента
	$attachment_data = wp_generate_attachment_metadata( $attachment_id, $filedata['file'] );

	// обновление метаданных аттачмента
	wp_update_attachment_metadata( $attachment_id, $attachment_data );

	// определение заголовка для изображения
	wp_update_post( array(
		'ID'         => $attachment_id,
		'post_title' => $atts['post_title'],
	) );

	// сброс кэша пользователя, иначе загружаемые аватарки и обложки не обновляются некоторое время,
	// пользователи думают, что ничего не загрузилось и загружают картинку еще по 10 раз
	clean_user_cache( $user_id );

//	if ( true == WP_DEBUG ) {
//		$atts = json_encode( $atts );
//
//		echo $atts;
//		die();
//	}

	$post = get_post( $attachment_id, ARRAY_A );

	wp_send_json_success( $post );
}

add_action( 'wp_ajax_' . 'oi_image_uploader', '\oifrontend\image_uploader\upload_file' );


// eof
