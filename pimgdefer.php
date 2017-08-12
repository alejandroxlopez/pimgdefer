<?php
/*
Plugin Name: Img Defer
Plugin URI: http://chris.wptoolkit.us
Description: Make a defer image on load page
Version: 0.0.1
Author: Chris Diehl, Ajandro Lopez
Author URI: http://chris.wptoolkit.us
License: GPL2
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'log_me' ) ) {
	function log_me( $message ) {
		if ( WP_DEBUG === true ) {
			if ( is_array( $message ) || is_object( $message ) ) {
				error_log( print_r( $message, true ) );
			} else {
				error_log( $message );
			}
		}
	}
}

// Plugin constants.
$p_path      = trailingslashit( dirname( __FILE__ ) );
$p_dir       = plugin_dir_url( __FILE__ );
$plugin_constants = array(
	'PIMGDEFER_VERSION'    => '0.0.1',
	'PIMGDEFER_MAIN_FILE'  => __FILE__,
	'PIMGDEFER_URL'        => $p_dir,
	'PIMGDEFER_PATH'       => $p_path,
	'PIMGDEFER_SRC_REGEX'  => "/(<img[^>]*src *= *[\"']?)([^\"']*)/i",
	'PIMGDEFER_SRCSET_REGEX'  => "/(<img[^>]*srcset *= *[\"']?)([^\"']*)/i",
);

foreach ( $plugin_constants as $constant => $value ) {
	if ( ! defined( $constant ) ) {
		define( $constant, $value );
	}
}

/**
 *
 *
 * @return void
 */
function get_default_imgdefer() {
	return  PIMGDEFER_URL . 'img/0.gif';
}

function register_imgdefer_scripts() {
	wp_register_script( 'pimgdefer', PIMGDEFER_URL . 'scripts/defer.js', array( 'jquery' ), false, true );
}
add_action( 'init', 'register_imgdefer_scripts' );

function enqueue_imgdefer_scripts() {
	wp_enqueue_script( 'pimgdefer' );
}
add_action( 'wp_enqueue_scripts', 'enqueue_imgdefer_scripts', 10 );

function build_attributes_string( $attributes ) {
	$string = array();
	foreach ( $attributes as $name => $attribute ) {
		$value = $attribute['value'];
		if ( '' === $value ) {
			$string[] = sprintf( '%s', $name );
		} else {
			$string[] = sprintf( '%s="%s"', $name, esc_attr( $value ) );
		}
	}
	return implode( ' ', $string );
}

function process_image( $matches ) {
	$placeholder_image = get_default_imgdefer();

	$old_attributes_str = $matches[2];
	if ( false !== strpos( $old_attributes_str, 'data-src' ) || false !== strpos( $old_attributes_str, 'data-srcset' ) ) {
		return $matches[0];
	}

	$old_attributes = wp_kses_hair( $old_attributes_str, wp_allowed_protocols() );

	if ( empty( $old_attributes['src'] ) ) {
		return $matches[0];
	}

	$image_src = $old_attributes['src']['value'];
	if ( isset( $old_attributes['srcset'] ) ) {
		$image_srcset = $old_attributes['srcset']['value'];
	}

	$new_attributes = $old_attributes;
	unset( $new_attributes['src'], $new_attributes['data-src'] );

	if ( isset( $new_attributes['srcset'] ) ) {
		unset( $new_attributes['srcset'] );
	}

	if ( isset( $new_attributes['data-srcset'] ) ) {
		unset( $new_attributes['data-srcset'] );
	}

	$new_attributes_str = build_attributes_string( $new_attributes );
	$scaped_placeholder_image = esc_url( $placeholder_image );

	if ( ! isset( $image_srcset ) ) {
		return sprintf( '<img src="%1$s" data-src="%2$s" %3$s>', $scaped_placeholder_image, esc_url( $image_src ), $new_attributes_str );
	} else {
		return sprintf( '<img src="%1$s" srcset="%2$s" data-src="%3$s" data-srcset="%4$s" %5$s>', $scaped_placeholder_image, $scaped_placeholder_image, esc_url( $image_src ), $image_srcset, $new_attributes_str );
	}
}

function process_imgdefer_on_the_content( $content ) {
	if ( is_feed() || is_preview() ) {
		return $content;
	}

	$content = preg_replace_callback( '#<(img)([^>]+?)(>(.*?)</\\1>|[\/]?>)#si', 'process_image', $content );
	return $content;
}

add_filter( 'the_content', 'process_imgdefer_on_the_content', 999, 1 );
add_filter( 'get_header_image_tag', 'process_imgdefer_on_the_content', 999, 1 );
add_filter( 'post_thumbnail_html', 'process_imgdefer_on_the_content', 999 );
add_filter( 'get_avatar', 'process_imgdefer_on_the_content', 999 );

function process_imgdefer_on_attachment_image_attributes( $attr ) {
	// log_me( $attr );
	if ( ! is_admin() ) {
		$src_temp = $attr['src'];
		$srcset_temp = $attr['srcset'];
		$attr['src'] = get_default_imgdefer();
		$attr['srcset'] = get_default_imgdefer();
		$attr['data-src'] = $src_temp;
		$attr['data-srcset'] = $srcset_temp;
	}

	return $attr;
}

add_filter( 'wp_get_attachment_image_attributes', 'process_imgdefer_on_attachment_image_attributes', 999 );
