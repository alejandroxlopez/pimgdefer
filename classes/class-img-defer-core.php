<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Img_Defer_Core {

	function __construct() {
		add_filter( 'the_content', array( $this, 'process_imgdefer_on_the_content' ), 999, 1 );
		add_filter( 'get_header_image_tag', array( $this, 'process_imgdefer_on_the_content' ), 999, 1 );
		add_filter( 'post_thumbnail_html', array( $this, 'process_imgdefer_on_the_content' ), 999 );
		add_filter( 'get_avatar', array( $this, 'process_imgdefer_on_the_content' ), 999 );
		add_filter( 'wp_get_attachment_image_attributes', array( $this, 'process_imgdefer_on_attachment_image_attributes' ), 999 );
	}

	public function process_imgdefer_on_the_content( $content ) {
		if ( is_feed() || is_preview() ) {
			return $content;
		}

		$content = preg_replace_callback( '#<(img)([^>]+?)(>(.*?)</\\1>|[\/]?>)#si', array( $this, 'process_image' ), $content );
		return $content;
	}

	public function process_image( $matches ) {
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

		$new_attributes_str = $this->build_attributes_string( $new_attributes );
		$scaped_placeholder_image = esc_url( $placeholder_image );

		if ( ! isset( $image_srcset ) ) {
			return sprintf( '<img src="%1$s" data-src="%2$s" %3$s>', $scaped_placeholder_image, esc_url( $image_src ), $new_attributes_str );
		} else {
			return sprintf( '<img src="%1$s" srcset="%2$s" data-src="%3$s" data-srcset="%4$s" %5$s>', $scaped_placeholder_image, $scaped_placeholder_image, esc_url( $image_src ), $image_srcset, $new_attributes_str );
		}
	}

	public function build_attributes_string( $attributes ) {
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
}
