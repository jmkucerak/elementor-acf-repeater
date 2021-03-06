<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Defines common functions for dynamic tags.
 */
class Module {
	/**
	 * Constructor.  Define hooks and filters.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function __construct() {
		add_action( 'elementor/editor/before_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'elementor/ajax/register_actions', [ $this, 'register_ajax_actions' ] );
	}

	/**
	 * Enqueue javascript for Elementor editor.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_register_script( 'elementor-acf-repeater', plugin_dir_url( __DIR__ ) . '/assets/js/elementor-acf-repeater.js', [], '1.0.0', true );
		wp_enqueue_script( 'elementor-acf-repeater', plugin_dir_url( __DIR__ ) . '/assets/js/elementor-acf-repeater.js', [], '1.0.0', true );
	}

	/**
	 * Register ajax actions with Elementor.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @param Elementor\Core\Base\Module $ajax Instance of Elementor base Module.
	 * @return void
	 */
	public function register_ajax_actions( $ajax ) {
		$ajax->register_ajax_action( 'update_dynamic_tag_controls', [ $this, 'ajax_update' ] );
	}

	/**
	 * Handles ajax request to update Dynamic Tags.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @param array $params Data used for the ajax request.
	 * @return array $result Array of options.
	 */
	public function ajax_update( $params ) {
		// Bail early if a post_id was not given.
		if ( ! isset( $params['post_id'] ) ) {
			return [ 'error' => 'No post id given.' ];
		}

		// Instantiate an empty result.
		$result = [
			'tags' => [],
		];
		// Store all the registered tag classes.
		$tag_names = self::get_tag_classes_names();

		// Iterate over the tag classes.
		foreach ( $tag_names as $name ) {
			// Convert class name to file name.
			$class_file = strtolower( str_replace( '_', '-', $name ) );
			include_once $class_file . '.php'; // Include tag class file.

			// Store supported types for the tag.
			$types = $name::$supported_fields;

			// Store the control options for the tag.
			$result['tags'][ $class_file ] = self::get_control_options( $types );
		}

		return $result;
	}

	/**
	 * Return array of tag classes
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return array
	 */
	public static function get_tag_classes_names() {
		return [
			'Elementor_ACF_Repeater_Text',
			'Elementor_ACF_Repeater_Image',
			'Elementor_ACF_Repeater_URL',
			'Elementor_ACF_Repeater_Gallery',
			'Elementor_ACF_Repeater_File',
		];
	}

	/**
	 * Returns a dynamic tag control options.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @param array $types The supported types for the tag.
	 * @return array $groups Array of options.
	 */
	public static function get_control_options( $types ) {
		// Instantiate return value.
		$groups = [];

		// Store repeater field key meta for the current post.
		$repeater_key = get_post_meta( get_the_ID(), '_ear_field', true );

		// Bail if no repeater key is set.
		if ( '' === $repeater_key ) {
			return $groups;
		}

		// Store the sub fields of the repeater field.
		$repeater   = acf_get_field( $repeater_key );
		$sub_fields = $repeater['sub_fields'];

		// Instantiate options.
		$options = [];

		// Iterate over all sub fields.
		foreach ( $sub_fields as $sub_field ) {
			// Add sub field key and label if it is a supported type.
			if ( in_array( $sub_field['type'], $types ) ) {
				$key = $sub_field['key'];

				$options[ $key ] = $sub_field['label'];
			}
		}

		// Add a label and options to the groups.
		$groups[] = [
			'label'   => $repeater['label'],
			'options' => $options,
		];

		return $groups;
	}
}
