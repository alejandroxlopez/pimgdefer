<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PIMGDefer_Config {
	var $current_post_types = array();
	var $discard_post_types = array( 'revision', 'attachment', 'nav_menu_item', 'custom_css', 'acf-field-group', 'acf-field', 'customize_changeset' );

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'plugin_custom_options' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		register_activation_hook( PIMGDEFER_MAIN_FILE, array( $this, 'pimgdefer_install' ) );
		register_deactivation_hook( PIMGDEFER_MAIN_FILE, array( $this, 'pimgdefer_uninstall' ) );
	}

	function pimgdefer_install() {
		update_option( 'pimgdefer_post_types', array( 'page', 'post' ), false );
	}

	function pimgdefer_uninstall() {
		delete_option( 'pimgdefer_post_types' );
	}

	public function plugin_custom_options() {
		$this->current_post_types = get_post_types(
			array(),
			'objects'
		);

		add_options_page(
			'Image Defer Options',
			'Img Defer Opts',
			'manage_options',
			'pimgdefer_options',
			array( $this, 'content_custom_options' )
		);
	}

	function register_settings() {
		register_setting(
			'pimgdefer_plugin_options',
			'pimgdefer_post_types',
			array( $this, 'check_post_types' )
		);

		 add_settings_section(
			 'pimgdefer_options_section',
			 'Main Settings',
			 false,
			 'pimgdefer_options'
		 );

		add_settings_field(
			'pimgdefer_post_types',
			'Apply the plugin funcionality',
			array( $this, 'pimgdefer_post_types_checkboxes' ),
			'pimgdefer_options',
			'pimgdefer_options_section'
		);
	}

	function check_post_types( $post_types ) {
		$new_post_types = array();
		if ( is_array( $post_types ) ) {
			foreach ( $post_types as $posttype ) {
				if ( post_type_exists( $posttype ) ) {
					$new_post_types[] = $posttype;
				}
			}
		}
		return $new_post_types;
	}

	function pimgdefer_post_types_checkboxes( $args = array() ) {
		$pimgdefer_post_types = get_option( 'pimgdefer_post_types', array() );
		foreach ( $this->current_post_types as $post_type ) {
			if( ! in_array($post_type->name, $this->discard_post_types ) ) {
				$checked = '';
				if ( in_array( $post_type->name, $pimgdefer_post_types ) ) {
					$checked = 'checked';
				}
				print '<label>';
				print   "<input type=\"checkbox\" value=\"{$post_type->name}\" name=\"pimgdefer_post_types[]\" id=\"pimgdefer_post_types\" {$checked} />";
				print   "{$post_type->label}";
				print '</label><br>';
			}
		}
	}

	public function content_custom_options() {
	?>
		<div class="wrap">
			<h1>Phila page list helper configuration</h1>
			<form method="post" action="options.php">
			<?php
				settings_fields( 'pimgdefer_plugin_options' );

				do_settings_sections( 'pimgdefer_options' );
				submit_button();
			?>
			</form>
		</div>
	<?php
	}
}
