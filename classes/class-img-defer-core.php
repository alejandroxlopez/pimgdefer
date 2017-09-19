<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PIMGDefer_Core {

	function __construct() {
		// The content search and change to defer
		add_filter( 'the_content', 'process_defer_on_content', 999, 1 );

		// Tweenty something support
		add_filter( 'get_header_image_tag', 'process_defer_on_content', 999, 1 );

		// HTML post thumnail
		add_filter( 'post_thumbnail_html', 'process_defer_on_content', 999 );

		// Avatar defering
		add_filter( 'get_avatar', 'process_defer_on_content', 999 );

		// WP Attachment function support
		add_filter( 'wp_get_attachment_image_attributes', array( $this, 'process_imgdefer_on_attachment_image_attributes' ), 999, 2 );
	}

	function process_imgdefer_on_attachment_image_attributes( $attr, $attachment ) {
		if ( ! is_admin() || prevent_deferring() ) {
			// $prevent_defering = (bool) get_post_meta( $attachment->ID, 'pimgdefer_prevent_defering', true );
            $src_temp = $attr['src'];
            $srcset_temp = $attr['srcset'];
            $attr['src'] = get_default_tiny_image_placeholder();
            $attr['srcset'] = get_default_tiny_image_placeholder();
            $attr['data-src'] = $src_temp;
            $attr['data-srcset'] = $srcset_temp;
		}

		return $attr;
	}
}
