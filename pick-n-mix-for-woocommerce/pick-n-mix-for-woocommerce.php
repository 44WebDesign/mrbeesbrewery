<?php
/**
 * Plugin Name: Pick n Mix for WooCommerce
 * Plugin URI:  https://example.com/pick-n-mix-for-woocommerce
 * Description: Create "pick n mix" box products. The customer chooses a set number of your existing simple products (e.g. pick any 3 dog treats) and buys the box for one fixed price.
 * Version:     1.2.0
 * Author:      44 Web Design
 * Text Domain: pick-n-mix-for-woocommerce
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.2
 * WC requires at least: 5.0
 * WC tested up to: 9.0
 *
 * @package PickNMixForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

define( 'PNM_WC_VERSION', '1.2.0' );
define( 'PNM_WC_FILE', __FILE__ );
define( 'PNM_WC_PATH', plugin_dir_path( __FILE__ ) );
define( 'PNM_WC_URL', plugin_dir_url( __FILE__ ) );
define( 'PNM_WC_PRODUCT_TYPE', 'pick_n_mix' );

/**
 * Main plugin bootstrap.
 */
final class PNM_WC_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var PNM_WC_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return PNM_WC_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor. Hook into WordPress once plugins are loaded.
	 */
	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Initialise the plugin, but only if WooCommerce is active.
	 */
	public function init() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}

		load_plugin_textdomain( 'pick-n-mix-for-woocommerce', false, dirname( plugin_basename( PNM_WC_FILE ) ) . '/languages' );

		$this->includes();

		// Register the custom product type class with WooCommerce.
		add_filter( 'woocommerce_product_class', array( $this, 'product_class' ), 10, 2 );

		// Boot the sub-modules.
		PNM_WC_Settings::instance();
		PNM_WC_Admin::instance();
		PNM_WC_Frontend::instance();
		PNM_WC_Cart::instance();

		// Declare High-Performance Order Storage (HPOS) compatibility.
		add_action( 'before_woocommerce_init', array( $this, 'declare_hpos_compatibility' ) );
	}

	/**
	 * Load the plugin's class files.
	 */
	private function includes() {
		require_once PNM_WC_PATH . 'includes/class-wc-product-pick-n-mix.php';
		require_once PNM_WC_PATH . 'includes/class-pnm-wc-helpers.php';
		require_once PNM_WC_PATH . 'includes/class-pnm-wc-settings.php';
		require_once PNM_WC_PATH . 'includes/class-pnm-wc-admin.php';
		require_once PNM_WC_PATH . 'includes/class-pnm-wc-frontend.php';
		require_once PNM_WC_PATH . 'includes/class-pnm-wc-cart.php';
	}

	/**
	 * Map the pick_n_mix product type to our product class.
	 *
	 * @param string $classname   Default class name.
	 * @param string $product_type Product type slug.
	 * @return string
	 */
	public function product_class( $classname, $product_type ) {
		if ( PNM_WC_PRODUCT_TYPE === $product_type ) {
			$classname = 'WC_Product_Pick_N_Mix';
		}
		return $classname;
	}

	/**
	 * Declare compatibility with WooCommerce HPOS (custom order tables).
	 */
	public function declare_hpos_compatibility() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', PNM_WC_FILE, true );
		}
	}

	/**
	 * Admin notice shown when WooCommerce is not active.
	 */
	public function woocommerce_missing_notice() {
		echo '<div class="notice notice-error"><p>';
		echo esc_html__( 'Pick n Mix for WooCommerce requires WooCommerce to be installed and active.', 'pick-n-mix-for-woocommerce' );
		echo '</p></div>';
	}
}

PNM_WC_Plugin::instance();
