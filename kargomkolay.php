<?php

/**
 * @package KargomKolay
 */
/*
Plugin Name: KargomKolay Express Shipping
Plugin URI: https://www.kargomkolay.com/
Description: KargomKolay Express Shipping, Türkiye'den 219+ ülkeye kapıdan kapıya DHL, TNT, UPS ve FEDEX servislerini kullanarak kargo yollamanızı sağlar.
Version: 1.0.4
Author: KargomKolay
Author URI: http://www.drnlojistik.com.tr/
License: GPLv2 or later
Text Domain: kargomkolay
*/

require('kkep.php');
require('shipping.php');

// Make sure we don't expose any info if called directly
if (!function_exists('add_action')) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

function kkep_shipping_init()
{
	if (!class_exists('WC_KKEP_Shipping_Method')) {

		class WC_KKEP_Shipping_Method extends WC_Shipping_Method
		{
			private $api_url = "https://api.kargomkolay.com";

			/**
			 * Constructor for your shipping class
			 *
			 * @access public
			 * @return void
			 */
			public function __construct($instance_id = 0)
			{
				$this->instance_id        = absint($instance_id);
				$this->supports = array('settings', 'shipping-zones',);
				$this->id                 = 'kkep_shipping_method';
				$this->title       = __('KargomKolay');
				$this->method_title = __('KargomKolay', 'woocommerce');
				$this->method_description = __('KargomKolay Yurt Dışı Gönderi'); // 

				$this->enabled            = "yes";


				$this->init();
			}

			function get_dimension($val)
			{
				return floatval(wc_get_dimension($val, "cm"));
			}

			public function is_available($package)
			{
				if ($package["destination"]["country"] == "TR") {
					return false;
				} else {

					$request_model = array(
						"countryCode" => $package["destination"]["country"]
					);

					$args = array(
						'body' => json_encode($request_model),
						'timeout' => '10',
						'redirection' => '5',
						'httpversion' => '1.1',
						'blocking' => true,
						'headers' => array(
							'Content-Type' => 'application/json; charset=utf-8',
							'X-KKCI-Key' => $this->settings['KKEP_customer_key']
						),
						'cookies' => array()
					);

					$response = wp_remote_post("{$this->api_url}/api/priceCalculator/isAvailableForCountry", $args);

					if (is_wp_error($response)) {
						return false;
					}

					$result =  json_decode(wp_remote_retrieve_body($response));

					return $result->data->available;
				}
			}

			/**
			 * Init your settings
			 *
			 * @access public
			 * @return void
			 */
			function init()
			{
				// Load the settings API
				$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
				$this->init_settings(); // This is part of the settings API. Loads settings you previously init.

				// Save settings in admin if you have any defined
				add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
			}

			function init_form_fields()
			{
				$this->form_fields = array(
					'KKEP_customer_key' => array(
						'title' => __('Müşteri Key', 'woocommerce'),
						'type' => 'text',
						'description' => __('Size verilen şifreyi girin. (Müşteri Numarası Edinmek İçin 0850 550 55 00 numaralı hattı arayın)', 'woocommerce'),
						'default' => __('00000000-0000-0000-0000-000000000000', 'woocommerce')
					),
					'KKEP_multiplier' => array(
						'title' => __('Paketleme Katsayısı (%)', 'woocommerce'),
						'type' => 'number',
						'description' => __('Gönderilerinizi paketledikten sonra oluşacak muhtemel hacim/kilo farkından korunmak için dilediğiniz paketleme katsayınızı yüzde oranında giriniz (Örnek 15). Müşterilerinize kargo maliyetleri, bu katsayı ile çarpılarak sunulacaktır. ', 'woocommerce'),
						'default' => __("5", 'woocommerce')
					)
				);
			}

			/**
			 * calculate_shipping function.
			 *
			 * @access private
			 * @param KKEP_Calculate_Price_Request
			 * @return mixed
			 */
			private function get_rate($request_model)
			{
				$args = array(
					'body' => json_encode($request_model),
					'timeout' => '10',
					'redirection' => '5',
					'httpversion' => '1.1',
					'blocking' => true,
					'headers' => array(
						'Content-Type' => 'application/json; charset=utf-8',
						'X-KKCI-Key' => $this->settings['KKEP_customer_key']
					),
					'cookies' => array()
				);

				$response = wp_remote_post("{$this->api_url}/api/priceCalculator/calculatePriceForProducts", $args);

				if (is_wp_error($response)) {
					return false;
				}

				$result =  json_decode(wp_remote_retrieve_body($response));

				return $result->data;
			}

			/**
			 * calculate_shipping function.
			 *
			 * @access private
			 * @param array $package Package array
			 * @return boolean
			 */
			private function checkProducts($package)
			{
				foreach ($package["contents"] as $p) {

					/**
					 * @var WC_Product_Simple $product_data
					 */
					$product_data = $p["data"];

					$weight = floatval($product_data->get_weight());

					$height = floatval($product_data->get_height());
					$width = floatval($product_data->get_width());
					$length = floatval($product_data->get_length());

					if (in_array(0, [$weight, $height, $width, $length])) {
						return false;
					}
				}
				return true;
			}

			/**
			 * calculate_shipping function.
			 *
			 * @access public
			 * @param array $package Package array
			 * @return void
			 */
			public function calculate_shipping($package = array())
			{
				if (!$this->checkProducts($package)) {
					$this->add_error("Shipping cannot be calculated, please contact shop owner to check product details.");
					return;
				}

				$multiplier = $this->settings['KKEP_multiplier'];

				$req = new KKEP\KKEP_Calculate_Price_Request;
				$req->countryCode = $package["destination"]["country"];
				$req->currency = get_woocommerce_currency();
				$req->volumeMultiplier = $multiplier;
				$req->packageProp = new KKEP\KKEP_Calculate_Price_Request;

				foreach ($package["contents"] as $p) {

					/**
					 * @var WC_Product_Simple $product_data
					 */
					$product_data = $p["data"];
					$quantity = $p["quantity"];

					$weight = wc_get_weight($product_data->get_weight(), 'kg');

					$height = $this->get_dimension($product_data->get_height());
					$width = $this->get_dimension($product_data->get_width());
					$length = $this->get_dimension($product_data->get_length());

					//product
					$product = new KKEP\KKEP_Product;

					$product->weight = $weight;
					$product->height = $height;
					$product->width = $width;
					$product->length = $length;

					$product->quantity = $quantity;


					array_push($req->products, $product);
				}

				$result = $this->get_rate($req);

				$rate = array(
					'id'       => $this->id,
					'label'    => "KargomKolay Service Providers: TNT, UPS, DHL, FedEx. Delivery time is " . $result->avgShipmentDay . " working days as an average after shipping. (Door to door express shipment excluding customs in arrival country)",
					'cost'     => strval($result->price),
					'calc_tax' => 'per_order'
				);

				// Register the rate
				$this->add_rate($rate);
			}
		}
	}
}

function add_kkep_shipping_method($methods)
{
	$methods['kkep_shipping_method'] = 'WC_KKEP_Shipping_Method';
	return $methods;
}

add_action('woocommerce_shipping_init', 'kkep_shipping_init');

add_filter('woocommerce_shipping_methods', 'add_kkep_shipping_method');
