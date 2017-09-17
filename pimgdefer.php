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

// Globals
global $pimgdefer_allowed_post_types, $pimgdefer_prevent_defering;

$pimgdefer_prevent_defering = false;
$pimgdefer_allowed_post_types = get_option( 'pimgdefer_post_types', array() );

// Plugin constants.
$p_path      = trailingslashit( dirname( __FILE__ ) );
$p_dir       = plugin_dir_url( __FILE__ );
$plugin_constants = array(
	'PIMGDEFER_VERSION'    => '0.0.1',
	'PIMGDEFER_MAIN_FILE'  => __FILE__,
	'PIMGDEFER_URL'        => $p_dir,
	'PIMGDEFER_PATH'       => $p_path,
    'PIMGDEFER_DOMAIN'     => 'pimgdefer',
	'PIMGDEFER_SRC_REGEX'  => "/(<img[^>]*src *= *[\"']?)([^\"']*)/i",
	'PIMGDEFER_SRCSET_REGEX'  => "/(<img[^>]*srcset *= *[\"']?)([^\"']*)/i",
);

foreach ( $plugin_constants as $constant => $value ) {
	if ( ! defined( $constant ) ) {
		define( $constant, $value );
	}
}

function register_imgdefer_scripts() {
	if( prevent_pimgdefer() ) return;
	wp_register_script( 'pimgdefer', PIMGDEFER_URL . 'scripts/defer.js', array( 'jquery' ), false, true );
}
add_action( 'init', 'register_imgdefer_scripts' );

function enqueue_imgdefer_scripts() {
	wp_enqueue_script( 'pimgdefer' );
}
add_action( 'wp_enqueue_scripts', 'enqueue_imgdefer_scripts', 10 );

include_once PIMGDEFER_PATH . 'helpers.php';
include_once PIMGDEFER_PATH . 'classes/class-img-defer-core.php';
include_once PIMGDEFER_PATH . 'classes/class-defer-toggle.php';
include_once PIMGDEFER_PATH . 'classes/class-config.php';

new PIMGDefer_Config();
new PIMGDefer_Core();
new PIMGDefer_Toggle();
