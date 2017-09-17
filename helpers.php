<?php
function get_default_imgdefer() {
	return  PIMGDEFER_URL . 'img/0.gif';
}

function prevent_pimgdefer() {
	global $pimgdefer_prevent_defering;
	return ( bool ) $pimgdefer_prevent_defering;
}

function apply_pimgdefer_on_content( $content = '' ) {
	return PIMGDefer_Core::process_imgdefer_on_the_content( $content );
}

function apply_pimgdefer_on_array( $attrs = array() ) {
	$extra_attributes = array();

	if( prevent_pimgdefer() ) {
		$extra_attributes = PIMGDefer_Core::build_attributes_string_by_key( $attrs );
		return sprintf( '<img %1$s>', $extra_attributes);
	}
	

	$src = esc_url( $attrs['src'] );
	$src_placeholder = get_default_imgdefer();

	unset( $attrs['src'] );

	if( isset( $attrs['srcset'] ) && is_string( $attrs['srcset'] ) ) {
		$srcset = $attrs['srcset'];
		$srcset_placeholder = get_default_imgdefer();
		
		unset( $attrs['srcset'] );

		if( ! empty( $attrs ) ) {
			$extra_attributes = PIMGDefer_Core::build_attributes_string_by_key( $attrs );
		}
		return sprintf( '<img src="%1$s" srcset="%2$s" data-src="%3$s" data-srcset="%4$s" %5$s>', $src_placeholder, $srcset_placeholder, $src, $srcset, $extra_attributes );
	}

	if( ! empty( $attrs ) ) {
		$extra_attributes = PIMGDefer_Core::build_attributes_string_by_key( $attrs );
	}
	return sprintf( '<img src="%1$s" data-src="%2$s" %3$s>', $src_placeholder, $src, $extra_attributes );
}

function get_pimgdefer( $src = '', $attrs = array() ) {
	if( empty( $src ) && empty( $attrs['src'] ) ) {
		// No hay de donde hacer un caldo
		return "";
	}

	if( empty( $src ) && ( isset( $attrs['src'] ) && is_string( $attrs['src'] ) ) ) {
		return apply_pimgdefer_on_array( $attrs );
	}

	if( filter_var( $src, FILTER_VALIDATE_URL ) && empty( $attrs ) ) {
		$attrs = array();
		$attrs['src'] = $src;
		return apply_pimgdefer_on_array( $attrs );
	}

	if( is_string( $src ) && ( is_array( $attrs ) && ! empty( $attrs ) ) ) {

		$attrs['src'] = $src;
		return apply_pimgdefer_on_array( $attrs );

	}

	if( is_string( $src ) ) {
		return apply_pimgdefer_on_content( $src );
	}

	return "";
}

// Apply deffer and echo on string or array
function the_pimgdefer( $src = "", $attr = array() ) {
	echo get_pimgdefer( $src, $attr );
}