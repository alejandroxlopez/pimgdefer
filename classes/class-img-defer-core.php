<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PIMGDefer_Core {

	function __construct() {
		// The content search and change to defer
		add_filter( 'the_content', array( 'PIMGDefer_Core', 'process_imgdefer_on_the_content' ), 999, 1 );

		// Tweenty something support
		add_filter( 'get_header_image_tag', array( 'PIMGDefer_Core', 'process_imgdefer_on_the_content' ), 999, 1 );

		// HTML post thumnail
		add_filter( 'post_thumbnail_html', array( 'PIMGDefer_Core', 'process_imgdefer_on_the_content' ), 999 );

		// Avatar defering
		add_filter( 'get_avatar', array( 'PIMGDefer_Core', 'process_imgdefer_on_the_content' ), 999 );

		// WP Attachment function support
		add_filter( 'wp_get_attachment_image_attributes', array( $this, 'process_imgdefer_on_attachment_image_attributes' ), 999, 2 );
	}

	public static function process_imgdefer_on_the_content( $content ) {
		if ( is_feed() || is_preview() || prevent_pimgdefer() ) {
			return $content;
		}

		$content = preg_replace_callback( '#<(img)([^>]+?)(>(.*?)</\\1>|[\/]?>)#si', array( 'PIMGDefer_Core', 'process_image' ), $content );
		return $content;
	}

	public static function process_image( $matches ) {
		$placeholder_image = get_default_imgdefer();

		$old_attributes_str = $matches[2];
		if ( false !== strpos( $old_attributes_str, 'data-src' ) || false !== strpos( $old_attributes_str, 'data-srcset' ) ) {
			return $matches[0];
		}

		$old_attributes = wp_kses_hair( $old_attributes_str, wp_allowed_protocols() );

		if( isset( $old_attributes['data-prev-defering'] ) && $old_attributes['data-prev-defering']['value'] == 'true' ) {
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

		$new_attributes_str = self::build_attributes_string( $new_attributes );
		$scaped_placeholder_image = esc_url( $placeholder_image );

		if ( ! isset( $image_srcset ) ) {
			return sprintf( '<img src="%1$s" data-src="%2$s" %3$s>', $scaped_placeholder_image, esc_url( $image_src ), $new_attributes_str );
		} else {
			return sprintf( '<img src="%1$s" srcset="%2$s" data-src="%3$s" data-srcset="%4$s" %5$s>', $scaped_placeholder_image, $scaped_placeholder_image, esc_url( $image_src ), $image_srcset, $new_attributes_str );
		}
	}

	public static function build_attributes_string( $attributes ) {
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

	public static function build_attributes_string_by_key( $attributes ) {
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

	function process_imgdefer_on_attachment_image_attributes( $attr, $attachment ) {
		if ( ! is_admin() || prevent_pimgdefer() ) {
			// $prevent_defering = (bool) get_post_meta( $attachment->ID, 'pimgdefer_prevent_defering', true );
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
