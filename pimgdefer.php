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

if( ! function_exists('log_me') )
{
	function log_me($message) {
		if (WP_DEBUG === true) {
			if (is_array($message) || is_object($message)) {
				error_log(print_r($message, true));
			} else {
				error_log($message);
			}
		}
	}
}

// Plugin constants.
$p_path      = trailingslashit( dirname( __FILE__ ) );
$p_dir       = plugin_dir_url( __FILE__ );
$plugin_constants = array(
	'PIMGDEFER_VERSION'    => '1.0.0',
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
    return "data:image/png;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=";
}

function register_imgdefer_scripts() {
    wp_register_script( 'pimgdefer', PIMGDEFER_URL . 'scripts/defer.js', array( 'jquery' ), false, true );
}
add_action( 'init', 'register_imgdefer_scripts' );

function enqueue_imgdefer_scripts() {
    wp_enqueue_script( 'pimgdefer' );
}
add_action('wp_enqueue_scripts', 'enqueue_imgdefer_scripts', 10);

function change_imgdefer_src( $matches ) {
    return $matches[1] . get_default_imgdefer() . '" data-src="'. $matches['2'];
}

function change_imgdefer_srcset( $matches ) {
    return $matches[1] . get_default_imgdefer() . '" data-srcset="'. $matches['2'];
}

function process_imgdefer_on_the_content( $content ) {
    $content = preg_replace_callback(PIMGDEFER_SRC_REGEX, 'change_imgdefer_src', $content);
    $content = preg_replace_callback(PIMGDEFER_SRCSET_REGEX, 'change_imgdefer_srcset', $content);
    return $content;
}

add_filter( 'the_content', 'process_imgdefer_on_the_content', 999, 1 );
add_filter( 'get_header_image_tag', 'process_imgdefer_on_the_content', 999, 1 );

// function process_imgdefer_on_attachment_image_attributes( $attr ) {
//     if( ! is_admin() ) {
//         $src_temp = $attr['src'];
//         $srcset_temp = $attr['srcset'];
//         $attr['src'] = get_default_imgdefer();
//         $attr['srcset'] = get_default_imgdefer();
//         $attr['data-src'] = $src_temp;
//         $attr['data-srcset'] = $srcset_temp;
//     }

//     return $attr;
// }

// add_filter( 'wp_get_attachment_image_attributes', 'process_imgdefer_on_attachment_image_attributes', 999);

////////////////////////////////
//Check this out
///////////////////////////////

// function customFormatGallery($string,$attr){

//     $posts = get_posts(array('include' => $attr['ids'],'post_type' => 'attachment'));

//     foreach($posts as $imagePost){
//         $output .= "<img src='data:image/png;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs='  data-src='".wp_get_attachment_image_src($imagePost->ID, 'small')[0]."' /> ";
//         $output .= "<img src='data:image/png;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=' data-media=\"(min-width: 400px)\" data-src='".wp_get_attachment_image_src($imagePost->ID, 'medium')[0]."' />";
//         $output .= "<img src='data:image/png;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=' data-media=\"(min-width: 950px)\" data-src='".wp_get_attachment_image_src($imagePost->ID, 'large')[0]."' />";
//         $output .= "<img src='data:image/png;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=' data-media=\"(min-width: 1200px)\"> data-src='".wp_get_attachment_image_src($imagePost->ID, 'extralarge')[0]."' />";
//     }

//     return $output;
// }
// add_filter('post_gallery','customFormatGallery',10,2);