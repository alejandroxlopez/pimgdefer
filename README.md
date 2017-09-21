# Image Deferral Plugin
This plugin will defer the loading of your images until after the initial page load.

## How?
The plugin tells your server to switch all image sources with a base64 1px image. Additionally, data-attributes reserve the original pathway to the image's location. When the HTML file is served, the browser will read the DOM and load the base64 image as a temporary place holder.

After the DOM finishes loading, a script will run .onload and replace all of the sources with the image pathway found in the data-source. The effect is that your text content should load quicker, then the large image files will load after the rest of the page, thus increasing your pagespeed.


## Stand-alone
"core.php" is the stand-alone version of the plugin, if you want apply deferring to your theme without installing the plugin, you can copy and paste the core.php content in your functions.php and setup your PIMGDEFER_URL constant to your theme URL (get_template_directory_uri function of WordPress), you should also copy the 'scripts' and 'img' folders to your theme, because core.php will enqueue the Javascript to your theme and grap the 0.gif tiny image from the "img" folder.

When you setup your the core.php code in your theme, you just use "get_deferred_image" or "the_deferred_image" functions to convert <img /> tags to deferring format.


## Troubleshooting
In case you disable or remove the plugin but you are using the "get_deferred_image" or "the_deferred_image" templating helpers, you have to add this code in your functions.php so your theme keeps working as intended.

```
<?php
if( ! function_exists( 'apply_pimgdefer_on_array' ) ) {
	function apply_pimgdefer_on_array( $attrs ) {
		if( empty( $attrs['src'] ) ) {
			return '';
		}
		
		$params = array();
		foreach ( $attrs as $name => $value ) {
			if ( empty( $value ) ) {
				$params[] = sprintf( '%s', $name );
			} else {
				$params[] = sprintf( '%s="%s"', $name, esc_attr( $value ) );
			}
		}
		
		$string = implode( ' ', $params );
		return '<img ' . $string . '>';
	}
}

if( ! function_exists( 'get_deferred_image' ) ) {
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
			return $src;
		}
	
		return '';
	}
}

if( ! function_exists( 'the_deferred_image' ) ) {
	function the_deferred_image( $src, $attr = array() ) {
		echo get_deferred_image( $src, $attr );
	}
}
```
