<?php
// define( 'PIMGDEFER_URL', get_template_directory_uri() ) // Uncomment this line if you are using this code in your theme.

// Regular expresion to search and return each <img> tag
define( 'IMG_TAG_REGEX', '#<(img)([^>]+?)(>(.*?)</\\1>|[\/]?>)#si' );

// String format to print the rendered <img> tag.
define( 'IMG_TAG_SRC', '<img src="%1$s" data-src="%2$s" %3$s>' );

// String format to print the rendered <img> tag with srcset
define( 'IMG_TAG_SRC_SRCSET', '<img src="%1$s" srcset="%2$s" data-src="%3$s" data-srcset="%4$s" %5$s>' );

/**
 * Returns a boolean value if we should execute the deferring process or not.
 *
 * @return bool
 */
function prevent_deferring() {
	global $prevent_deferring_flag;
	return (bool) $prevent_deferring_flag;
}

/**
 * Here we return the small default image; The deferring process will replace this image with the original when the page loads.
 * 
 * Remember: PIMGDEFER_URL should be changed with your actuall image route.
 * right now it points to this plugin route.
 *
 * @return void
 */
function get_default_tiny_image_placeholder() {
	return  PIMGDEFER_URL . 'img/0.gif';
}

/**
 * Register the Javascript for deffering images.
 * Remember: PIMGDEFER_URL should have the route of your theme if you are using this code as standalone.
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
 * Function to enqueue the registered deferring Javascript.
 *
 * @return void
 */
function enqueue_deferring_scripts() {
	wp_enqueue_script( 'pimgdefer' );
}
add_action( 'wp_enqueue_scripts', 'enqueue_deferring_scripts', 10 );

/**
 * Process a string of content "HTML" looking for each <img> tags
 * to apply the necessary attributes for deferring attributes.
 *
 * @param String $content
 * @return String new formated content
 */
function process_defer_on_content( $content ) {
	if ( is_feed() || is_preview() || prevent_deferring() ) {
		return $content;
	}
	
	// Here we look for the <img> tags and run the "process_image" on each one.
	$content = preg_replace_callback( IMG_TAG_REGEX, 'process_image', $content );
	return $content;
}

/**
 * Get an array of "matches" from the regex search and creates an <img> tag with the 
 * data-img and data-imgsrc for deferring.
 *
 * @param array $matches
 * @return string the deferring formatted <img> tag.
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
 * Creates a string with the format 'attr="value"' from an associative array of values
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
 * Creates a string with the format 'attr="value"' from an associative array of key => 'value'
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
 * Creates an <img> tag from an array of key => 'values'
 *
 * @param array $attrs Associative array with the attributes of the image. the SRC attribute is mandatory.
 * @return string the deferring formatted <img> tag.
 */
function apply_pimgdefer_on_array( $attrs = array() ) {
	if( empty( $attrs['src'] ) ) {
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
 * With this function you can create <img> tags to be deferred from multiple sources:
 * A html tag <img src="image.png">
 * A array ['src' => 'image.png']
 * A string and array get_deferred_image( 'image.png', ['alt'=>'My example image'] )
 * A string URL (Must be an URL) get_deferred_image( 'http://example.com/image.png );
 *
 * @param mixed string or array
 * @param array $attrs Extra parameters to use in the new <img> tag ( title, alt, width, height, etc ).
 * @return string the deferring formatted <img> tag.
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
 * This is just a wrapper function to print the 'get_deffered_image' result.
 */
function the_deferred_image( $src, $attr = array() ) {
	echo get_deferred_image( $src, $attr );
}
