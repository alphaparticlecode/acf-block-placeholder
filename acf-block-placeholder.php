<?php
/**
 * Plugin Name:       ACF Block Placeholder
 * Plugin URI:        https://wordpress.org/plugins/acf-block-placeholder/
 * Description:       Generates a placeholder in ACF blocks that don't have any content in them yet to create a better editor experience.
 * Version:           1.0
 * Requires at least: 5.6
 * Requires PHP:      7.2
 * Author:            Alpha Particle
 * Author URI:        https://alphaparticle.com/
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       acf-block-placeholder
 */

// TO-DO: If ACF isn't active, throw an admin notice

class ACF_Block_Placeholder {

	/**
	 * Static property to hold our singleton instance
	 *
	 */
	static $instance = false;

	/**
	 * This is our constructor
	 *
	 * @return void
	 */
	private function __construct() {
		add_filter('acf/register_block_type_args', [ $this, 'my_acf_register_block_type_args' ] );

		add_action('acf/input/admin_enqueue_scripts', [ $this, 'my_acf_admin_enqueue_scripts' ] );
	}

	/**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return ACF_Block_Placeholder
	 */

	public static function getInstance() {
		if ( !self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	public function my_acf_register_block_type_args( $args ){
		//TO-DO: Figure out a way to pass the already-registered callback
		// as a param to our function so it can be called after our
		// work is done and we can support blocks with callbacks instead of templates

		// If this block is already using a render callback,
		// we don't want to mess with that.
		if( empty( $args['render_callback'] ) ) {
			$args['render_callback'] = [$this, 'maybe_generate_acf_block_placeholder'];
		}
		
		return $args;
	}

	public function maybe_generate_acf_block_placeholder( $block, $content = '', $is_preview = false, $post_id = 0 ) {

		if( $is_preview && $this->is_block_empty( $block ) ) {
			echo '<div class="acf-block-placeholder"><div><h1>' . $block['title'] . '</h1><p>This block is currently empty. Switch to edit mode or add data using the sidebar.</p></div></div>';
		} else {
			// Call the block template as normal
			// Locate template.
			if( file_exists($block['render_template']) ) {
				$path = $block['render_template'];
			} else {
				$path = locate_template( $block['render_template'] );
			}
			
			// Include template.
			if( file_exists($path) ) {
				include( $path );
			}
		}
	}

	private function is_block_empty( $block ) {
		$fields    = acf_get_block_fields($block);
		$field_ids = wp_list_pluck( $fields, 'key' );

		$block_data = array_chunk($block['data'], 2);

		foreach( $block_data as $field_data ) {
			$current_value = $field_data[0];
			$field_key = $field_data[1];

			if( empty( $current_value ) || false !== strpos( $current_value, 'field_' ) ) {
				// If the field doesn't have anything in it, we can move on.
				continue;
			} else {
				// Otherwise, it gets a bit more complicated and we need
				// to check if the current value of the field is equal
				// to the default for this field to be considered "empty".
				$field_key_index = array_search( $field_key, $field_ids );
				
				$field_default_value = $fields[$field_key_index];

				if( empty( $fields[$field_key_index]['default_value'] ) || $current_value != $fields[$field_key_index]['default_value'] ) {
					return false;
				}
			}
		}

		return true;
	}

	public function my_acf_admin_enqueue_scripts() {
		$plugin_url = plugin_dir_url( __FILE__ );
		wp_enqueue_style( 'acf-placeholder-css', $plugin_url . 'css/acf-placeholder.css', false, '1.0.0' );
	}
}

$ACF_Block_Placeholder = ACF_Block_Placeholder::getInstance();
