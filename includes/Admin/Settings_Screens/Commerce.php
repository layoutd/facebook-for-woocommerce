<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\Admin\Settings_Screens;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Facebook\Admin;
use SkyVerge\WooCommerce\Facebook\Handlers\Connection as Connection_Handler;
use SkyVerge\WooCommerce\PluginFramework\v5_5_4 as Framework;

/**
 * The Commerce settings screen object.
 */
class Commerce extends Admin\Abstract_Settings_Screen {


	/** @var string screen ID */
	const ID = 'commerce';


	/**
	 * Connection constructor.
	 */
	public function __construct() {

		$this->id    = self::ID;
		$this->label = __( 'Instagram Checkout', 'facebook-for-woocommerce' );
		$this->title = __( 'Instagram Checkout', 'facebook-for-woocommerce' );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		add_action( 'woocommerce_admin_field_commerce_google_product_categories', [ $this, 'render_google_product_category_field' ] );
	}


	/**
	 * Enqueues the assets.
	 *
	 * @internal
	 *
	 * @since 2.1.0-dev.1
	 */
	public function enqueue_assets() {

		if ( Admin\Settings::PAGE_ID !== Framework\SV_WC_Helper::get_requested_value( 'page' ) || ( self::ID !== Framework\SV_WC_Helper::get_requested_value( 'tab' ) ) ) {
			return;
		}

		wp_enqueue_script( 'facebook-for-woocommerce-settings-commerce', facebook_for_woocommerce()->get_plugin_url() . '/assets/js/admin/settings-commerce.min.js', [ 'facebook-for-woocommerce-modal', 'jquery-tiptip' ], \WC_Facebookcommerce::PLUGIN_VERSION );

		wp_localize_script( 'facebook-for-woocommerce-settings-commerce', 'facebook_for_woocommerce_settings_commerce', [
			'default_google_product_category_modal_message'       => $this->get_default_google_product_category_modal_message(),
			'default_google_product_category_modal_message_empty' => $this->get_default_google_product_category_modal_message_empty(),
			'default_google_product_category_modal_buttons'       => $this->get_default_google_product_category_modal_buttons(),
		] );
	}


	/**
	 * Gets the message for Default Google Product Category modal.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @return string
	 */
	private function get_default_google_product_category_modal_message() {

		return wp_kses_post( __( 'Products and categories that inherit this global setting (i.e. they do not have a specific Google product category set) will use the new default immediately. Are you sure you want to proceed?', 'facebook-for-woocommerce' ) );
	}


	/**
	 * Gets the message for Default Google Product Category modal when the selection is empty.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @return string
	 */
	private function get_default_google_product_category_modal_message_empty() {

		return sprintf(
			/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag */
			esc_html__( 'Products and categories that inherit this global setting (they do not have a specific Google product category set) will use the new default immediately.  %1$sIf you have cleared the Google Product Category%2$s, items inheriting the default will not be available for Instagram checkout. Are you sure you want to proceed?', 'facebook-for-woocommerce' ),
			'<strong>', '</strong>'
		);
	}


	/**
	 * Gets the markup for the buttons used in the Default Google Product Category modal.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @return string
	 */
	private function get_default_google_product_category_modal_buttons() {

		ob_start();

		?>
		<button
			class="button button-large"
			onclick="jQuery( '.modal-close' ).trigger( 'click' )"
		><?php esc_html_e( 'Cancel', 'facebook-for-woocommerce' ); ?></button>
		<button
			id="btn-ok"
			class="button button-large button-primary"
		><?php esc_html_e( 'Update default Google product category', 'facebook-for-woocommerce' ); ?></button>
		<?php

		return ob_get_clean();
	}


	/**
	 * Renders the screen.
	 *
	 * @since 2.1.0-dev.1
	 */
	public function render() {

		// if not connected, fall back to standard display
		if ( ! facebook_for_woocommerce()->get_connection_handler()->is_connected() ) {
			parent::render();
			return;
		}

		$commerce_handler = facebook_for_woocommerce()->get_commerce_handler();

		if ( ! $commerce_handler->is_available() ) {
			$this->render_us_only_limitation_notice();
			return;
		}

		?>

		<h2><?php esc_html_e( 'Instagram Checkout', 'facebook-for-woocommerce' ); ?></h2>

		<table class="form-table">
			<tbody>
				<tr valign="top" class="">
					<th scope="row" class="titledesc"><?php esc_html_e( 'Sell on Instagram', 'facebook-for-woocommerce' ); ?></th>
					<td class="forminp">
						<?php if ( $commerce_handler->is_connected() ) : ?>
							<p><span class="dashicons dashicons-yes-alt" style="color:#4CB454"></span> <?php esc_html_e( 'Your store is connected to Instagram.', 'facebook-for-woocommerce' ); ?></p>
						<?php else: ?>
							<p><span class="dashicons dashicons-dismiss" style="color:#dc3232"></span> <?php esc_html_e( 'Your store is not connected to Instagram.', 'facebook-for-woocommerce' ); ?></p>

							<p style="margin-top:24px">
								<a class="button button-primary" href="<?php echo esc_url( $this->get_connect_url() ); ?>"><?php esc_html_e( 'Connect', 'facebook-for-woocommerce' ); ?></a>
							</p>
						<?php endif; ?>
					</td>
				</tr>
			</tbody>
		</table>

		<?php

		if ( $commerce_handler->is_connected() ) {
			parent::render();
		}
	}


	/**
	 * Renders the notice about the US-only limitation for Instagram Checkout.
	 *
	 * @since 2.1.0-dev.1
	 */
	private function render_us_only_limitation_notice() {

		?>

		<div class="notice notice-info"><p><?php esc_html_e( 'Instagram Checkout is only available to merchants located in the United States.', 'facebook-for-woocommerce' ); ?></p></div>

		<?php
	}


	/**
	 * Renders the Google category field markup.
	 *
	 * @internal

	 * @since 2.1.0-dev.1
	 *
	 * @param array $field field data
	 */
	public function render_google_product_category_field( $field ) {

		$category_field = new Admin\Google_Product_Category_Field();

		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['title'] ); ?>
					<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $field['desc_tip'] ); ?>"></span>
				</label>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $field['type'] ) ); ?>">
				<?php $category_field->render( $field['id'] ); ?>
				<input id="<?php echo esc_attr( $field['id'] ); ?>" type="hidden" name="<?php echo esc_attr( $field['id'] ); ?>" value="<?php echo esc_attr( $field['value'] ); ?>" />
			</td>
		</tr>
		<?php
	}


	/**
	 * Builds the connect URL.
	 *
	 * The base URL is https://www.facebook.com/commerce_manager/onboarding with two query variables:
	 * - app_id - the developer app ID
	 * - redirect_url - the URL where the user will land after onboarding is complete
	 *
	 * The redirect URL must be an approved domain, so it must be the connect.woocommerce.com proxy app. In that URL, we
	 * include the final site URL, which is where the merchant will redirect to with the data that needs to be stored.
	 * So the final URL looks like this without encoding:
	 *
	 * https://www.facebook.com/commerce_manager/onboarding/?app_id={id}&redirect_url=https://connect.woocommerce.com/auth/facebook/?site_url=https://example.com/?wc-api=wc_facebook_connect_commerce&nonce=1234
	 *
	 * If testing only, &is_test_mode=true can be appended to the URL using the wc_facebook_commerce_connect_url filter
	 * to trigger the test account flow, where fake US-based business details can be used.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @return string
	 */
	public function get_connect_url() {

		// build the site URL to which the user will ultimately return
		$site_url = add_query_arg( [
			'wc-api' => Connection_Handler::ACTION_CONNECT_COMMERCE,
			'nonce'  => wp_create_nonce( Connection_Handler::ACTION_CONNECT_COMMERCE ),
		], home_url( '/' ) );

		// build the proxy app URL where the user will land after onboarding, to be redirected to the site URL
		$redirect_url = add_query_arg( 'site_url', urlencode( $site_url ), facebook_for_woocommerce()->get_connection_handler()->get_proxy_url() );

		// build the final connect URL, direct to Facebook
		$connect_url = add_query_arg( [
			'app_id'       => facebook_for_woocommerce()->get_connection_handler()->get_client_id(), // this endpoint calls the client ID "app ID"
			'redirect_url' => urlencode( $redirect_url ),
		], 'https://www.facebook.com/commerce_manager/onboarding/' );

		/**
		 * Filters the URL used to connect to Facebook Commerce.
		 *
		 * @since 2.1.0-dev.1
		 *
		 * @param string $connect_url connect URL
		 */
		return apply_filters( 'wc_facebook_commerce_connect_url', $connect_url );
	}


	/**
	 * Gets the screen settings.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @return array
	 */
	public function get_settings() {

		return [
			[
				'type' => 'title',
			],
			[
				'id'       => \SkyVerge\WooCommerce\Facebook\Commerce::OPTION_GOOGLE_PRODUCT_CATEGORY_ID,
				'type'     => 'commerce_google_product_categories',
				'title'    => __( 'Default Google product category', 'facebook-for-woocommerce' ),
				'desc_tip' => __( 'Choose a default Google product category for your products. Defaults can also be set for product categories. Products need at least two category levels defined to sell via Instagram.', 'facebook-for-woocommerce' ),
			],
			[
				'type' => 'sectionend',
			]
		];
	}


	/**
	 * Gets the "disconnected" message.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @return string
	 */
	public function get_disconnected_message() {

		return sprintf(
			/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
			__( 'Please %1$sconnect to Facebook%2$s to enable Instagram Checkout.', 'facebook-for-woocommerce' ),
			'<a href="' . esc_url( facebook_for_woocommerce()->get_connection_handler()->get_connect_url() ) . '">', '</a>'
		);
	}


}
