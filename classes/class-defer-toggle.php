<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PIMGDefer_Toggle {
	function __construct() {
		// Add img toggle filter
		// TODO: Apply toggling by image
		// add_filter( 'attachment_fields_to_edit', array( $this, 'add_attachment_field_credit' ), 10, 2 );
		// TODO: Apply toggling by image
		// add_filter( 'attachment_fields_to_save', array( $this, 'add_attachment_field_credit_save' ), 10, 2 );

		// Metabox
		global $pimgdefer_allowed_post_types;
		if ( ! empty( $pimgdefer_allowed_post_types ) ) {
			foreach ( $pimgdefer_allowed_post_types as $allowed ) {
				add_action( "add_meta_boxes_{$allowed}", array( $this, 'adding_custom_meta_boxes' ) );
			}
		}

		// Save metabox action
		add_action( 'save_post', array( $this, 'save_pimgdefer_option' ), 10, 3 );

		// Setup Globals
		add_action( 'wp_head', array( $this, 'setup_pimgdefer_globals' ), 10, 1 );
	}

	function setup_pimgdefer_globals( $wp_object ) {
		global $pimgdefer_allowed_post_types, $prevent_deffering_flag;

		if ( ! is_array( $pimgdefer_allowed_post_types ) ) {
			return;
		}

		$query_object = get_object_vars( get_queried_object() );
		if ( isset( $query_object ) && ! empty( $query_object ) ) {
			if ( isset( $query_object['post_type'] ) && isset( $query_object['ID'] ) ) {
				if ( in_array( $query_object['post_type'], $pimgdefer_allowed_post_types ) ) {
					$prevent_deffering_flag = (bool) get_post_meta( $query_object['ID'], 'pimgdefer_prevent_single_defering', true );
				}
			}
		}
	}

	function save_pimgdefer_option( $post_id, $post, $update ) {
		global $pimgdefer_allowed_post_types;
		if ( ! is_array( $pimgdefer_allowed_post_types ) ) {
			return;
		}

		$post_type = get_post_type( $post_id );
		if ( ! in_array( $post_type, $pimgdefer_allowed_post_types ) ) {
			return;
		}

		// Checkboxes are present if checked, absent if not.
		if ( isset( $_POST['pimgdefer_prevent_single_defering'] ) ) {
			update_post_meta( $post_id, 'pimgdefer_prevent_single_defering', true );
		} else {
			update_post_meta( $post_id, 'pimgdefer_prevent_single_defering', false );
		}
	}

	function adding_custom_meta_boxes( $post ) {
		add_meta_box(
			'pimgdefer_prevent_single_defering',
			__( 'Prevent defering this post?', PIMGDEFER_DOMAIN ),
			array( $this, 'render_meta_box_content' ),
			$post->post_type,
			'side',
			'high'
		);
	}

	public function render_meta_box_content( $post ) {
		wp_nonce_field( 'pimgdefer_input_box', 'pimgdefer_input_nonce' );

		$checked = (bool) get_post_meta( $post->ID, 'pimgdefer_prevent_single_defering', true );

		// Display the form, using the current value.
		?>
		<div style="">
			<p>Prevent defering images on this post?</p>
			<label>
				Yes
				<input type="checkbox" name="pimgdefer_prevent_single_defering" value="1"<?php echo ( $checked ) ? ' checked' : ''; ?>>
			</label>
		</div>
		<?php
	}

	function add_attachment_field_credit( $form_fields, $post ) {
		$pimgdefer_toggle = (bool) get_post_meta( $post->ID, 'pimgdefer_prevent_defering', true );

		$form_fields['pimgdefer-prevent-defering'] = array(
			'label' => 'Prevent defering?',
			'input' => 'html',
			'html' => '<label for="attachments-' . $post->ID . '-pimgdefer"> ' .
				'<input type="checkbox" id="attachments-' . $post->ID . '-pimgdefer" name="attachments[' . $post->ID . '][pimgdefer-prevent-defering]" value="1"' . ( $pimgdefer_toggle ? ' checked="checked"' : '' ) . ' /> Yes</label>  ',
			'value' => $pimgdefer_toggle,
			'helps' => 'Prevent defering funcionality on this image',
		);
		return $form_fields;
	}

	function add_attachment_field_credit_save( $post, $attachment ) {
		if ( isset( $attachment['pimgdefer-prevent-defering'] ) ) {
			update_post_meta( $post['ID'], 'pimgdefer_prevent_defering', $attachment['pimgdefer-prevent-defering'] );
		}
		return $post;
	}
}
