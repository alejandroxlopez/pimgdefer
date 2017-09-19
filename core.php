<?php
define( 'IMG_TAG_REGEX', '#<(img)([^>]+?)(>(.*?)</\\1>|[\/]?>)#si' );
define( 'IMG_TAG_SRC', '<img src="%1$s" data-src="%2$s" %3$s>' );
define( 'IMG_TAG_SRC_SRCSET', '<img src="%1$s" srcset="%2$s" data-src="%3$s" data-srcset="%4$s" %5$s>' );

function prevent_deferring() {
	global $prevent_deffering_flag;
	return (bool) $prevent_deffering_flag;
}

function get_default_tiny_image_placeholder() {
	return  PIMGDEFER_URL . 'img/0.gif';
}

function register_deferring_scripts() {
	if ( prevent_deferring() ) {
		return;
	}
	wp_register_script( 'pimgdefer', PIMGDEFER_URL . 'scripts/defer.min.js', array( 'jquery' ), false, true );
}
add_action( 'init', 'register_deferring_scripts' );

function enqueue_deferring_scripts() {
	wp_enqueue_script( 'pimgdefer' );
}
add_action( 'wp_enqueue_scripts', 'enqueue_deferring_scripts', 10 );

function process_defer_on_content( $content ) {
	if ( is_feed() || is_preview() || prevent_deferring() ) {
		return $content;
	}

	$content = preg_replace_callback( IMG_TAG_REGEX, 'process_image', $content );
	return $content;
}

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

function apply_pimgdefer_on_array( $attrs = array() ) {
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

// Apply deffer and echo on string or array
function the_deferred_image( $src, $attr = array() ) {
	echo get_deferred_image( $src, $attr );
}
