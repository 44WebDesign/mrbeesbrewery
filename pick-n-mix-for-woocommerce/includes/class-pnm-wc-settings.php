<?php
/**
 * Global settings: a "Pick n Mix" subsection under WooCommerce → Products
 * holding the default colour palette used by every box.
 *
 * @package PickNMixForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class PNM_WC_Settings
 */
class PNM_WC_Settings {

	/**
	 * Singleton instance.
	 *
	 * @var PNM_WC_Settings|null
	 */
	private static $instance = null;

	/**
	 * Option name prefix for the global colour defaults.
	 */
	const OPTION_PREFIX = 'pnm_wc_default_color_';

	/**
	 * Get the singleton instance.
	 *
	 * @return PNM_WC_Settings
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hook into the WooCommerce Products settings tab.
	 */
	private function __construct() {
		add_filter( 'woocommerce_get_sections_products', array( $this, 'add_section' ) );
		add_filter( 'woocommerce_get_settings_products', array( $this, 'get_settings' ), 10, 2 );
	}

	/**
	 * Register the "Pick n Mix" subsection.
	 *
	 * @param array $sections Existing sections.
	 * @return array
	 */
	public function add_section( $sections ) {
		$sections['pnm_wc'] = __( 'Pick n Mix', 'pick-n-mix-for-woocommerce' );
		return $sections;
	}

	/**
	 * Provide the fields for our subsection.
	 *
	 * @param array  $settings        Settings for the current section.
	 * @param string $current_section Current section id.
	 * @return array
	 */
	public function get_settings( $settings, $current_section ) {
		if ( 'pnm_wc' !== $current_section ) {
			return $settings;
		}

		$fields = array(
			array(
				'title' => __( 'Pick n Mix default colours', 'pick-n-mix-for-woocommerce' ),
				'type'  => 'title',
				'desc'  => __( 'These colours are used by every Pick n Mix box that has not set its own colours on the product. Individual products can still override them.', 'pick-n-mix-for-woocommerce' ),
				'id'    => 'pnm_wc_defaults_title',
			),
		);

		foreach ( PNM_WC_Helpers::color_fields() as $slug => $field ) {
			$fields[] = array(
				'title'    => $field['label'],
				'id'       => self::OPTION_PREFIX . $slug,
				'type'     => 'color',
				'default'  => $field['default'],
				'css'      => 'width:6em;',
				'autoload' => false,
			);
		}

		$fields[] = array(
			'type' => 'sectionend',
			'id'   => 'pnm_wc_defaults_end',
		);

		return $fields;
	}
}
