<?php
// Here is the regular expresion to search and return every 
// <img> tag inside a content.
define( 'IMG_TAG_REGEX', '#<(img)([^>]+?)(>(.*?)</\\1>|[\/]?>)#si' );

// This is the string format used by the sprintf function to create
// the <img> tag when the rendering is done with just src..
define( 'IMG_TAG_SRC', '<img src="%1$s" data-src="%2$s" %3$s>' );

// This is the string format used by the sprintf function to create 
// the <img> tag when the rendering is done with src and srcset
define( 'IMG_TAG_SRC_SRCSET', '<img src="%1$s" srcset="%2$s" data-src="%3$s" data-srcset="%4$s" %5$s>' );

/**
 * This function returns a global variable which has a true or false value
 * depending if we should defer the current page/post or not.
 *
 * @return bool
 */
function prevent_deferring() {
	global $prevent_deffering_flag;
	return (bool) $prevent_deffering_flag;
}

/**
 * Here we return the default tiny image, this image is replaced with Javascript
 * with the original one.
 * 
 * Remember the PIMGDEFER_URL constant should be change with the route of your small image,
 * right now it points to this plugin route.
 *
 * @return void
 */
function get_default_tiny_image_placeholder() {
	return  PIMGDEFER_URL . 'img/0.gif';
}

/**
 * Function to register the Javascript for deffering images, this registers the minified version
 * 
 * Remember the PIMGDEFER_URL constant should be change with the route of your small image,
 * right now it points to this plugin route.
 *
 * @return void
 */
function register_deferring_scripts() {
	if ( prevent_deferring() ) {
		return;
	}
	wp_register_script( 'pimgdefer', PIMGDEFER_URL . 'scripts/defer.min.js', array( 'jquery' ), false, true );
}
add_action( 'init', 'register_deferring_scripts' );

/**
 * Function to enqueue the registered Javascript for deffering images.
 *
 * @return void
 */
function enqueue_deferring_scripts() {
	wp_enqueue_script( 'pimgdefer' );
}
add_action( 'wp_enqueue_scripts', 'enqueue_deferring_scripts', 10 );

/**
 * This function starts the proccesing of a HTML or TEXT content
 * looking for <img> tags to create the deffering attributes.
 *
 * @param String $content
 * @return String new formated content
 */
function process_defer_on_content( $content ) {
	if ( is_feed() || is_preview() || prevent_deferring() ) {
		return $content;
	}
	
	// Here wee look for the <img> tags and run the "process_image" on each one found.
	$content = preg_replace_callback( IMG_TAG_REGEX, 'process_image', $content );
	return $content;
}

/**
 * Core function which gets an Array of values matched when the regular expresion is processed
 * process the matches and returns a new <img> tag with the required configuration to be deffered.
 *
 * @param array $matches
 * @return string new formate <img> tag
 */
function process_image( $matches ) {
	$placeholder_image = get_default_tiny_image_placeholder();

	$old_attributes_str = $matches[2];
	if ( false !== strpos( $old_attributes_str, 'data-src' ) || false !== strpos( $old_attributes_str, 'data-srcset' ) ) {
		return $matches[0];
	}

	$old_attributes = wp_kses_hair( $old_attributes_str, wp_allowed_protocols() );

	if ( isset( $old_attributes['data-prev-defering'] ) && $old_attributes['data-prev-defering']['value'] == 'true' ) {
		return $matches[0];
	}

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
		return sprintf( IMG_TAG_SRC, $scaped_placeholder_image, esc_url( $image_src ), $new_attributes_str );
	} else {
		return sprintf( IMG_TAG_SRC_SRCSET, $scaped_placeholder_image, $scaped_placeholder_image, esc_url( $image_src ), $image_srcset, $new_attributes_str );
	}
}

/**
 * Creates an string with the format 'attr="value"' to append in the new formated <img> tag.
 *
 * @param array $attributes
 * @return string fomatted attr="value" string.
 */
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

/**
 * Creates an string with the format 'attr="value"' to append in the new formated <img> tag.
 *
 * @param array $attributes Associative array with the parameters to concatenate in a string.
 * @return string fomatted attr="value" string.
 */
function build_attributes_string_by_key( $attributes ) {
	$string = array();
	foreach ( $attributes as $name => $value ) {
		if ( empty( $value ) ) {
			$string[] = sprintf( '%s', $name );
		} else {
			$string[] = sprintf( '%s="%s"', $name, esc_attr( $value ) );
		}
	}

	return implode( ' ', $string );
}

/**
 * Executes the logic to bild an <img> tag from an array of parameters.
 * The new <img> tag created will be configured to be deffered.
 *
 * @param array $attrs Associative array with the attributes of the image. It is mandatory
 * to pass at least 'src' attribute.
 * @return string the formated <img> tag string.
 */
function apply_pimgdefer_on_array( $attrs = array() ) {
	if( empty( $attrs['img'] ) ) {
		return '';
	}
	
	$extra_attributes = '';

	if ( prevent_deferring() ) {
		$extra_attributes = build_attributes_string_by_key( $attrs );
		return sprintf( '<img %1$s>', $extra_attributes );
	}

	$src = esc_url( $attrs['src'] );
	$src_placeholder = get_default_tiny_image_placeholder();

	unset( $attrs['src'] );

	if ( isset( $attrs['srcset'] ) && is_string( $attrs['srcset'] ) ) {
		$srcset = $attrs['srcset'];
		$srcset_placeholder = get_default_tiny_image_placeholder();

		unset( $attrs['srcset'] );

		if ( ! empty( $attrs ) ) {
			$extra_attributes = build_attributes_string_by_key( $attrs );
		}
		return sprintf( IMG_TAG_SRC_SRCSET, $src_placeholder, $srcset_placeholder, $src, $srcset, $extra_attributes );
	}

	if ( ! empty( $attrs ) ) {
		$extra_attributes = build_attributes_string_by_key( $attrs );
	}
	return sprintf( IMG_TAG_SRC, $src_placeholder, $src, $extra_attributes );
}

/**
 * This function receives a string or an array value, and tries to figure out which function use
 * to create the <img> tag. With an associative array of attributes or procesing the src parameter
 * as a HTML/Text code.
 *
 * @param mixed $src this can be an associative array with the image src attribute or an HTML string
 * with several <img> tags.
 * @param array $attrs these are extra parameters to use in the new <img> tag, like title, alt, etc.
 * @return string the created <img> tag.
 */
function get_deferred_image( $src, $attrs = array() ) {
	$defaults = array(
		'src' => '',
	);

	if ( empty( $src ) && ! isset( $attrs['src'] ) ) {
		// No hay de donde hacer un caldo
		return '';
	}

	if ( is_array( $src ) && isset( $src['src'] ) ) {
		if ( is_array( $attrs ) ) {
			$src = wp_parse_args( $src, $attrs );
		}
		return apply_pimgdefer_on_array( $src );
	}

	if ( filter_var( $src, FILTER_VALIDATE_URL ) ) {
		$defaults['src'] = $src;
		if ( is_array( $attrs ) ) {
			$defaults = wp_parse_args( $defaults, $attrs );
		}
		return apply_pimgdefer_on_array( $defaults );
	}

	if ( is_string( $src ) && ( is_array( $attrs ) && ! empty( $attrs ) ) ) {
		$defaults['src'] = $src;
		$defaults = wp_parse_args( $defaults, $attrs );
		return apply_pimgdefer_on_array( $defaults );

	}

	if ( is_string( $src ) ) {
		return process_defer_on_content( $src );
	}

	return '';
}

/**
 * This is just a wrapper function for get_deffered_image to print
 * the created <img> tag.
 */
function the_deferred_image( $src, $attr = array() ) {
	echo get_deferred_image( $src, $attr );
}
