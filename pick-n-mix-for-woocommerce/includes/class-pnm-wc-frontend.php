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

		// Hide the big featured image on a pick n mix product page; the treats
		// themselves are the visuals here.
		add_action( 'wp', array( $this, 'maybe_hide_product_image' ) );
	}

	/**
	 * Remove the single product featured image/gallery for pick n mix boxes so
	 * the layout starts straight at the treats.
	 */
	public function maybe_hide_product_image() {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		$product = wc_get_product( get_queried_object_id() );
		if ( ! $product instanceof WC_Product_Pick_N_Mix ) {
			return;
		}

		remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
		add_filter( 'woocommerce_single_product_image_thumbnail_html', '__return_empty_string' );

		// Let themes/CSS respond to the imageless layout if they wish.
		add_filter(
			'body_class',
			function ( $classes ) {
				$classes[] = 'pnm-wc-no-product-image';
				return $classes;
			}
		);
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

		$min = $product->get_pnm_min();
		$max = $product->get_pnm_max();

		// Pre-render the box price for every valid size so the bag can update live.
		$prices_html = array();
		for ( $count = $min; $count <= $max; $count++ ) {
			$price = $product->get_price_for_count( $count );
			if ( '' !== $price ) {
				$prices_html[ $count ] = wc_price( $price );
			}
		}

		wp_localize_script(
			'pnm-wc',
			'pnmWc',
			array(
				'min'           => $min,
				'max'           => $max,
				'pricesHtml'    => (object) $prices_html,
				'i18nRemaining' => __( 'Add %d more', 'pick-n-mix-for-woocommerce' ),
				'i18nFull'      => __( 'Bag is full!', 'pick-n-mix-for-woocommerce' ),
				'i18nReady'     => __( 'Your bag is ready', 'pick-n-mix-for-woocommerce' ),
				'i18nOver'      => __( 'Take out %d', 'pick-n-mix-for-woocommerce' ),
				'i18nEmpty'     => __( 'Your bag is empty — tap the treats to fill it up!', 'pick-n-mix-for-woocommerce' ),
				'i18nRemove'    => __( 'Remove', 'pick-n-mix-for-woocommerce' ),
				'i18nFrom'      => __( 'From', 'pick-n-mix-for-woocommerce' ),
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
				_n( 'Pick any %d treat to fill your bag', 'Pick any %d treats to fill your bag', $max, 'pick-n-mix-for-woocommerce' ),
				$max
			)
			: sprintf(
				/* translators: 1: minimum, 2: maximum */
				__( 'Pick between %1$d and %2$d treats to fill your bag', 'pick-n-mix-for-woocommerce' ),
				$min,
				$max
			);

		$color_style = PNM_WC_Helpers::build_color_style( $product->get_pnm_colors() );
		?>
		<form class="cart pnm-wc-form pnm-wc-stall" method="post" enctype="multipart/form-data"
			style="<?php echo esc_attr( $color_style ); ?>"
			data-min="<?php echo esc_attr( $min ); ?>" data-max="<?php echo esc_attr( $max ); ?>">

			<div class="pnm-wc-stall-layout">

				<div class="pnm-wc-stall-main">
					<div class="pnm-wc-stall-signboard">
						<span class="pnm-wc-stall-sign-icon" aria-hidden="true">🍬</span>
						<p class="pnm-wc-rule"><?php echo esc_html( $rule_label ); ?></p>
					</div>

					<ul class="pnm-wc-shelf">
						<?php foreach ( $selectable as $product_id => $child ) : ?>
							<?php
							$max_for_item = $max;
							if ( $child->managing_stock() && ! $child->backorders_allowed() ) {
								$stock        = (int) $child->get_stock_quantity();
								$max_for_item = ( $stock > 0 ) ? min( $max, $stock ) : 0;
							}
							$is_oos    = ( 0 === $max_for_item );
							$thumb_url = wp_get_attachment_image_url( $child->get_image_id(), 'woocommerce_gallery_thumbnail' );
							if ( ! $thumb_url ) {
								$thumb_url = wc_placeholder_img_src( 'woocommerce_gallery_thumbnail' );
							}
							?>
							<li class="pnm-wc-jar <?php echo $is_oos ? 'pnm-wc-jar--oos' : ''; ?>"
								data-product-id="<?php echo esc_attr( $product_id ); ?>"
								data-max="<?php echo esc_attr( $max_for_item ); ?>"
								data-name="<?php echo esc_attr( $child->get_name() ); ?>"
								data-thumb="<?php echo esc_url( $thumb_url ); ?>">

								<button type="button" class="pnm-wc-jar-btn" <?php disabled( true, $is_oos ); ?>
									aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name */ __( 'Add %s to your bag', 'pick-n-mix-for-woocommerce' ), $child->get_name() ) ); ?>">
									<span class="pnm-wc-jar-badge" aria-hidden="true">0</span>
									<span class="pnm-wc-jar-image"><?php echo wp_kses_post( $child->get_image( 'woocommerce_thumbnail' ) ); ?></span>
									<span class="pnm-wc-jar-name"><?php echo esc_html( $child->get_name() ); ?></span>
									<?php if ( $is_oos ) : ?>
										<span class="pnm-wc-jar-stock"><?php esc_html_e( 'Sold out', 'pick-n-mix-for-woocommerce' ); ?></span>
									<?php else : ?>
										<span class="pnm-wc-jar-add"><?php esc_html_e( 'Add to bag', 'pick-n-mix-for-woocommerce' ); ?></span>
									<?php endif; ?>
								</button>

								<input
									type="number"
									class="pnm-wc-qty-input"
									name="pnm_qty[<?php echo esc_attr( $product_id ); ?>]"
									value="0"
									min="0"
									max="<?php echo esc_attr( $max_for_item ); ?>"
									step="1"
									hidden
								/>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>

				<aside class="pnm-wc-bag-panel" aria-label="<?php esc_attr_e( 'Your pick n mix bag', 'pick-n-mix-for-woocommerce' ); ?>">
					<div class="pnm-wc-bag">
						<div class="pnm-wc-bag-top">
							<span class="pnm-wc-bag-title"><?php esc_html_e( 'Your bag', 'pick-n-mix-for-woocommerce' ); ?></span>
							<span class="pnm-wc-count"><span class="pnm-wc-count-current">0</span>&nbsp;/&nbsp;<?php echo esc_html( $max ); ?></span>
						</div>

						<div class="pnm-wc-progress"><span class="pnm-wc-progress-bar" style="width:0%"></span></div>

						<div class="pnm-wc-bag-body">
							<ul class="pnm-wc-bag-items" aria-live="polite"></ul>
							<p class="pnm-wc-bag-empty"><?php esc_html_e( 'Your bag is empty — tap the treats to fill it up!', 'pick-n-mix-for-woocommerce' ); ?></p>
						</div>

						<div class="pnm-wc-bag-foot">
							<p class="pnm-wc-message" aria-live="polite"></p>
							<div class="pnm-wc-price">
								<span class="pnm-wc-price-label"><?php esc_html_e( 'Bag price', 'pick-n-mix-for-woocommerce' ); ?></span>
								<span class="pnm-wc-price-value"><?php echo esc_html__( 'From', 'pick-n-mix-for-woocommerce' ) . ' ' . wp_kses_post( wc_price( $product->get_price_for_count( $min ) ) ); ?></span>
							</div>

							<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" />
							<input type="hidden" name="quantity" value="1" />

							<button type="submit" class="single_add_to_cart_button button alt pnm-wc-submit" disabled>
								<?php echo esc_html( $product->single_add_to_cart_text() ); ?>
							</button>
						</div>
					</div>
				</aside>

			</div>
		</form>
		<?php
	}
}
