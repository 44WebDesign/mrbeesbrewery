=== Pick n Mix for WooCommerce ===
Contributors: 44webdesign
Tags: woocommerce, pick n mix, mix and match, bundle, product box
Requires at least: 5.6
Tested up to: 6.5
Requires PHP: 7.2
WC requires at least: 5.0
WC tested up to: 9.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Let customers build their own "pick n mix" box from your existing simple products and buy it for one fixed price.

== Description ==

Pick n Mix for WooCommerce adds a new **Pick n Mix box** product type. You choose which of your existing simple products can go in the box and how many the customer must pick (for example "pick any 3"). The customer builds their box on the product page and pays a single fixed price, no matter which items they choose.

Perfect for things like:

* Build-your-own dog treat boxes ("choose any 3 treats for £12")
* Mixed sweet bags
* Sample / taster boxes
* Gift bundles

= Features =

* A dedicated **Pick n Mix box** product type.
* Set a **price for each box size** – e.g. 2 treats for £6, 3 for £8, 4 for £10 – or a single price if min = max.
* Require an exact number of items (min = max) or a range (e.g. between 2 and 4).
* Pick the selectable products individually and/or by category.
* Clean product-page picker with quantity steppers, a live counter and progress bar.
* Add-to-cart is disabled until the box meets the rules; server-side validation backs it up.
* Box contents are shown in the cart, at checkout, on the order and in emails.
* Optional automatic stock reduction of the chosen child products when an order's stock is reduced.
* Works with WooCommerce High-Performance Order Storage (HPOS).

== Installation ==

1. Upload the `pick-n-mix-for-woocommerce` folder to `/wp-content/plugins/`, or install the zip via **Plugins → Add New → Upload**.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Make sure WooCommerce is installed and active.

== How to set up a box ==

1. Go to **Products → Add New**.
2. Give the box a name (e.g. "Build Your Own Treat Box"), description and image.
3. In the **Product data** dropdown choose **Pick n Mix box**.
4. Open the **Pick n Mix** tab and set:
   * **Minimum / Maximum items** – set both the same for an exact count (e.g. 3 and 3 = "pick any 3").
   * **Box price per size** – a price row appears for every size between the minimum and maximum; enter the price for 2 items, 3 items, and so on.
   * **Selectable products** and/or **Selectable categories** – what the customer can put in the box.
5. Publish. The box now shows a picker on its product page.

== Frequently Asked Questions ==

= How is the box priced? =
By the number of items in it, not by which items are chosen. You set a price for each box size (2 items, 3 items, 4 items…), and the customer pays the price for the size they build. Set the minimum and maximum to the same number if you only want one fixed price.

= Can customers buy more than one box? =
Yes. Each configured box is added as one cart line. Adding another box with the same contents increases the quantity; a different combination becomes a separate line.

= Do the chosen products' stock levels update? =
If you enable stock management on the child products, their stock is reduced automatically when the order's stock is reduced. Out-of-stock items can't be added to a box.

== Changelog ==

= 1.0.0 =
* Initial release.
