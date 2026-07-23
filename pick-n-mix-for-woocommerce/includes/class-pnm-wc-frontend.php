<?php
/**
 * Frontend: single product selection form and assets.
 *
 * @package PickNMixForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class PNM_WC_Frontend
 */
class PNM_WC_Frontend {

	/**
	 * Singleton instance.
	 *
	 * @var PNM_WC_Frontend|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return PNM_WC_Frontend
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hook into the frontend.
	 */
	private function __construct() {
		// WooCommerce fires woocommerce_{type}_add_to_cart for the product type.
		add_action( 'woocommerce_' . PNM_WC_PRODUCT_TYPE . '_add_to_cart', array( $this, 'render_add_to_cart' ), 30 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue the frontend CSS/JS on pick n mix product pages.
	 */
	public function enqueue_assets() {
		if ( ! is_product() ) {
			return;
		}

		global $product;
		if ( ! $product instanceof WC_Product_Pick_N_Mix ) {
			$product = wc_get_product( get_the_ID() );
		}
		if ( ! $product instanceof WC_Product_Pick_N_Mix ) {
			return;
		}

		wp_enqueue_style(
			'pnm-wc',
			PNM_WC_URL . 'assets/css/pick-n-mix.css',
			array(),
			PNM_WC_VERSION
		);

		wp_enqueue_script(
			'pnm-wc',
			PNM_WC_URL . 'assets/js/pick-n-mix.js',
			array( 'jquery' ),
			PNM_WC_VERSION,
			true
		);

		wp_localize_script(
			'pnm-wc',
			'pnmWc',
			array(
				'min'          => $product->get_pnm_min(),
				'max'          => $product->get_pnm_max(),
				'i18nRemaining'=> __( 'Choose %d more', 'pick-n-mix-for-woocommerce' ),
				'i18nFull'     => __( 'Box is full', 'pick-n-mix-for-woocommerce' ),
				'i18nReady'    => __( 'Your box is ready', 'pick-n-mix-for-woocommerce' ),
				'i18nOver'     => __( 'Please remove %d item(s)', 'pick-n-mix-for-woocommerce' ),
			)
		);
	}

	/**
	 * Render the pick n mix selection form in place of the normal add to cart.
	 */
	public function render_add_to_cart() {
		global $product;

		if ( ! $product instanceof WC_Product_Pick_N_Mix ) {
			return;
		}

		$selectable = $product->get_selectable_products();
		$min        = $product->get_pnm_min();
		$max        = $product->get_pnm_max();

		if ( empty( $selectable ) ) {
			wc_print_notice( __( 'This box has no products to choose from yet. Please check back soon.', 'pick-n-mix-for-woocommerce' ), 'notice' );
			return;
		}

		if ( ! $product->is_in_stock() ) {
			wc_print_notice( __( 'This box is currently out of stock.', 'pick-n-mix-for-woocommerce' ), 'error' );
			return;
		}

		$rule_label = ( $min === $max )
			? sprintf(
				/* translators: %d: number of items */
				_n( 'Pick %d item to fill your box', 'Pick %d items to fill your box', $max, 'pick-n-mix-for-woocommerce' ),
				$max
			)
			: sprintf(
				/* translators: 1: minimum, 2: maximum */
				__( 'Pick between %1$d and %2$d items to fill your box', 'pick-n-mix-for-woocommerce' ),
				$min,
				$max
			);
		?>
		<form class="cart pnm-wc-form" method="post" enctype="multipart/form-data"
			data-min="<?php echo esc_attr( $min ); ?>" data-max="<?php echo esc_attr( $max ); ?>">

			<div class="pnm-wc-header">
				<p class="pnm-wc-rule"><?php echo esc_html( $rule_label ); ?></p>
				<div class="pnm-wc-status" aria-live="polite">
					<span class="pnm-wc-count"><span class="pnm-wc-count-current">0</span> / <?php echo esc_html( $max ); ?></span>
					<span class="pnm-wc-message"></span>
				</div>
				<div class="pnm-wc-progress"><span class="pnm-wc-progress-bar" style="width:0%"></span></div>
			</div>

			<ul class="pnm-wc-products">
				<?php foreach ( $selectable as $product_id => $child ) : ?>
					<?php
					$max_for_item = $max;
					if ( $child->managing_stock() && ! $child->backorders_allowed() ) {
						$stock        = (int) $child->get_stock_quantity();
						$max_for_item = ( $stock > 0 ) ? min( $max, $stock ) : 0;
					}
					?>
					<li class="pnm-wc-product <?php echo ( 0 === $max_for_item ) ? 'pnm-wc-product--oos' : ''; ?>">
						<div class="pnm-wc-product-image"><?php echo wp_kses_post( $child->get_image( 'woocommerce_thumbnail' ) ); ?></div>
						<div class="pnm-wc-product-info">
							<span class="pnm-wc-product-name"><?php echo esc_html( $child->get_name() ); ?></span>
							<?php if ( $child->get_short_description() ) : ?>
								<span class="pnm-wc-product-desc"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $child->get_short_description() ), 16 ) ); ?></span>
							<?php endif; ?>
							<?php if ( 0 === $max_for_item ) : ?>
								<span class="pnm-wc-product-stock"><?php esc_html_e( 'Out of stock', 'pick-n-mix-for-woocommerce' ); ?></span>
							<?php endif; ?>
						</div>
						<div class="pnm-wc-product-qty">
							<button type="button" class="pnm-wc-minus" aria-label="<?php esc_attr_e( 'Remove one', 'pick-n-mix-for-woocommerce' ); ?>" <?php disabled( 0, $max_for_item ); ?>>&minus;</button>
							<input
								type="number"
								class="pnm-wc-qty-input"
								name="pnm_qty[<?php echo esc_attr( $product_id ); ?>]"
								value="0"
								min="0"
								max="<?php echo esc_attr( $max_for_item ); ?>"
								step="1"
								inputmode="numeric"
								readonly
								<?php disabled( 0, $max_for_item ); ?>
							/>
							<button type="button" class="pnm-wc-plus" aria-label="<?php esc_attr_e( 'Add one', 'pick-n-mix-for-woocommerce' ); ?>" <?php disabled( 0, $max_for_item ); ?>>+</button>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>

			<div class="pnm-wc-footer">
				<div class="pnm-wc-price">
					<span class="pnm-wc-price-label"><?php esc_html_e( 'Box price:', 'pick-n-mix-for-woocommerce' ); ?></span>
					<span class="pnm-wc-price-value"><?php echo wp_kses_post( wc_price( $product->get_price() ) ); ?></span>
				</div>

				<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" />
				<input type="hidden" name="quantity" value="1" />

				<button type="submit" class="single_add_to_cart_button button alt pnm-wc-submit" disabled>
					<?php echo esc_html( $product->single_add_to_cart_text() ); ?>
				</button>
			</div>
		</form>
		<?php
	}
}
