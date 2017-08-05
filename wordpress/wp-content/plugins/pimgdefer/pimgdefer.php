<?php
/*
Plugin Name: Img Defer
Plugin URI: http://chris.wptoolkit.us
Description: Make a defer image on load page
Version: 1.0
Author: Chris Diehl
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
$plugin_path      = trailingslashit( dirname( __FILE__ ) );
$puglin_dir       = plugin_dir_url( __FILE__ );
$plugin_constants = array(
	'PIMGDEFER_VERSION'    => '1.0.0',
	'PIMGDEFER_MAIN_FILE'  => __FILE__,
	'PIMGDEFER_URL'        => $puglin_dir,
	'PIMGDEFER_PATH'       => $plugin_path
);

foreach ( $plugin_constants as $constant => $value ) {
	if ( ! defined( $constant ) ) {
		define( $constant, $value );
	}
}