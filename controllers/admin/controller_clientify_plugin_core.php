<?php
//include_once (__DIR__) . '/Api.php';
$fileToInclude = __DIR__ . '/AdminClientify.php';
if (file_exists($fileToInclude)) {

	include_once($fileToInclude);
}
class AdminCustomClientifyEndPoint
{
	public $data_config;

	public function __construct()
	{
		$this->data_config = $this->getIdShopConfigClientify(); // obtención del valor de la consulta SQL y asignación a la variable
	}

	/**
	 * Obtains the store name in a manner compatible with PrestaShop 1.7/8/9.
	 */
	private function getShopName($idShop)
	{
		if (empty($idShop)) {
			return 'prestashop';
		}

		$shop = new Shop((int) $idShop);

		return $shop->id ? $shop->name : 'prestashop';
	}

	private function getIdShopConfigClientify()
	{
		$results_global = Db::getInstance()->executeS("SELECT * FROM " . _DB_PREFIX_ . "configuration_clientify");
		return $results_global[0];
	}

	// function str_contains(string $haystack, string $needle): bool
	// {
	// 	return '' === $needle || false !== strpos($haystack, $needle);
	// }


	public function GetApiUrl($id_shop)
	{
		$get_data = "SELECT * FROM " . _DB_PREFIX_ . "configuration_clientify WHERE id_shop=" . $id_shop;
		$results = Db::getInstance()->executeS($get_data);
		if ($results) {

			$shop = new Shop((int) $results[0]['id_shop']);

			$api_url = $shop->getBaseURL();
			$baseUrl = str_replace("http://", "https://", $api_url);
			$api_url = $baseUrl . 'index.php?fc=module&module=clientify&controller=';

		} else {
			$shop = new Shop((int) $id_shop);

			$api_url = $shop->getBaseURL();
			$baseUrl = str_replace("http://", "https://", $api_url);
			$api_url = $baseUrl . 'index.php?fc=module&module=clientify&controller=';
		}

		return $api_url;
	}

	public function token_id()
	{
		$new_key = str_replace('-', '', md5(uniqid()));
		$data = array(
			'clientify_store_key' => $new_key
		);
		Db::getInstance()->update('configuration_clientify', $data);
		return $new_key;
	}
	/* change status pluging conneted or disconnect passes 0 / 1 */
	public function plugin_handling($params)
	{
		$db = Db::getInstance();
		$set_send = $params["action"];

		if ($set_send == "connect") {
			$key_uid = $this->token_id();
			$url_base = $this->GetApiUrl($this->data_config['id_shop']);

			$post_key = array(
				'ecommerce' => 'prestashop',
				'action' => 'connect',
				'store_key' => $key_uid,
				'name' => $this->getShopName($this->data_config['id_shop']),
				'store_url' => $url_base
			);

			$data_config = array(
				'clientify_module_status' => 1
			);
			$db->update('configuration_clientify', $data_config);
			$results = $db->executeS("SELECT clientify_module_status FROM " . _DB_PREFIX_ . "configuration_clientify");


			if ($results[0]['clientify_module_status'] == 1) {
				return array('message' => 'success', 'data' => $post_key);
			} else {
				return array('message' => 'error');
			}
		} elseif ($set_send == "disconnect") {
			$data_config = $db->executeS("SELECT clientify_store_key,id_shop FROM " . _DB_PREFIX_ . "configuration_clientify");

			$url_base = $this->GetApiUrl($data_config[0]['id_shop']);
			$post_key = array(
				'ecommerce' => 'prestashop',
				'action' => 'disconnect',
				'store_key' => $data_config[0]['clientify_store_key'],
				'name' => $this->getShopName($this->data_config['id_shop']),
				'store_url' => $url_base
			);

			$data_config = array(
				'clientify_module_status' => 0
			);
			$db->update('configuration_clientify', $data_config);
			$results = $db->executeS("SELECT clientify_module_status FROM " . _DB_PREFIX_ . "configuration_clientify");
			if ($results[0]['clientify_module_status'] == 0) {
				return array('message' => 'success', 'data' => $post_key);
			} else {
				return array('message' => 'error');
			}
		} else {
			return array('message' => 'error param');
		}
	}

	private function has_gdpr_consent($id_customer)
	{
		$result = Db::getInstance()->getValue(
			'SELECT id_gdpr_log FROM ' . _DB_PREFIX_ . 'psgdpr_log WHERE id_customer = ' . (int) $id_customer
		);

		return !empty($result);
	}

	public function get_contact($user_id)
	{
		$context = Context::getContext();
		$user = new Customer((int) $user_id);
		$site_name = $this->getShopName($this->data_config['id_shop']);
		$site_name = empty($site_name) ? 'prestashop' : $site_name;
		$lang = $context->language->iso_code;
		$address = new Address(Address::getFirstCustomerAddressId($user_id));
		$customer_phones = array();

		if ($user_id != 0) {
			$data = array(
				'id_customer' => $user_id,
				'email' => $user->email,
				'contact_source' => $site_name,
				'user_registered' => $user->date_add,
				'custom_fields' => [],
				'company' => empty($address->company) ? '' : $address->company,
				'identification' => empty($address->dni) ? $address->vat_number : $address->dni,
				'gdpr_accept' => $this->has_gdpr_consent($user_id),
				'tags' => array(
					'prestashop',
					$site_name,
				)

			);

			if (!empty($user->firstname)) {
				$data['first_name'] = $user->firstname;
			}
			if (!empty($user->lastname)) {
				$data['last_name'] = $user->lastname;
			}
			if (!empty($lang)) {
				$data['custom_field'] = array(
					'field' => 'ecommerce_language',
					'value' => $lang,
				);
			}
			if (!empty($address)) {
				$street = $address->address1 . (!empty($address->address2) ? ', ' . $address->address2 : '');
				$city = $address->city;
				$country = $address->country;
				$postal_code = $address->postcode;
				$customer_address = array('type' => 1);

				if ($street) {
					$customer_address['street'] = $street;
				}
				if ($city) {
					$customer_address['city'] = $city;
				}
				if ($country) {
					$customer_address['country'] = $country;
				}
				if ($postal_code) {
					$customer_address['postal_code'] = $postal_code;
				}
				if (!empty($address->id_state)) {
					$customer_address['state'] = State::getNameById($address->id_state);
					if (empty($customer_address['state'])) {
						unset($customer_address['state']);
					}
				}
				$data['addresses'][] = $customer_address;
			}

			if (!empty($user->company)) {
				$data['company'] = $user->company;
			}
			if (!empty($address->phone) && !in_array($address->phone, $customer_phones)) {
				$data['phones'][] = array('phone' => $address->phone);
				$customer_phones[] = $address->phone;
			}
		}

		return $data;
	}

	public function get_orders($order_id)
	{

		$order = new Order((int) $order_id); //paid and ids of order
		$url_base = $this->GetApiUrl($this->data_config['id_shop']);
		$shop = new Shop((int) $this->data_config['id_shop']);
		$shop_url = $shop->getBaseURL();
		$items = array();
		$products = $order->getProducts();
		$currency = Currency::getCurrency($order->id_currency);

		foreach ($products as $order_product) {

			$product = new Product((int) $order_product['id_product']);
			$id_image = Product::getCover($product->id);
			$date_format_clientify = new DateTimeImmutable($order->date_add);

			$categories = array();
			$categories = array();
			$sub_categories = array();
			$new_subcategories = array();

			$terms = Db::getInstance()->executes(
				"SELECT distinct cp.id_category,cp.id_product , c.id_parent , c.is_root_category, cl.name
				FROM " . _DB_PREFIX_ . "category_product cp
				INNER JOIN " . _DB_PREFIX_ . "category c ON cp.id_category = c.id_category
				INNER JOIN " . _DB_PREFIX_ . "category_lang cl ON c.id_category = cl.id_category
				WHERE cp.id_product = " . $order_product['id_product'] . " AND cl.id_lang = 1"
			);

			if (is_array($terms)) {
				foreach ($terms as $term) {
					$term_search = new Category($term['id_parent'], Context::getContext()->language->id);

					if ($term['is_root_category'] == 1 || !$this->categoryExists($categories, $term['id_parent'])) {
						$categories[] = $term_search->id_category . ":" . $term_search->name;
					}

					$sub_categories[] = $term['id_category'] . ":" . $term['name'] . "|parent_id:" . $term['id_parent'];

					if (!$this->categoryExists($categories, $term['id_parent'])) {
						$term_search_subcat = new Category($term['id_parent'], Context::getContext()->language->id);
						if (!in_array($term_search_subcat->id_category . ":" . $term_search_subcat->name, $categories)) {
							$categories[] = $term_search_subcat->id_category . ":" . $term_search_subcat->name;
						}
					}
				}
			}

			$new_subcategories = array_unique($sub_categories);
			$join_cat = implode(",", $categories) . "/" . implode(",", $new_subcategories);

			if ($id_image) {
				$image = new Image($id_image['id_image']);
				$image_url = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
			} else {
				$image_url = '';
			}

			$link = new Link();
			$url = $link->getProductLink($product);
			$price = $order_product['unit_price_tax_incl'];

			// Cálculo del porcentaje de descuento
			$global_discounts = floatval(number_format($order->total_products_wt, 2)) - floatval($order->total_discounts_tax_incl);
			if ($global_discounts == 0) {
				$discount_percent = 100;
			} else {
				$discount_percent = ($order->total_discounts_tax_incl * 100) / $order->total_products_wt;
			}

			$link = new Link();
			$url = $link->getProductLink($product);

			// Verificar si se encontraron combinaciones
			if (isset($order_product['product_type']) && ($order_product['product_type'] == 'combinations' || $order_product['product_attribute_id'] != 0)) {

				$specific_price_output = array();
				$price = ProductCore::getPriceStatic(
					(int) $order_product['id_product'],
					true,
					$order_product['product_attribute_id'],
					2,
					null,
					false,
					true,
					1,
					false,
					null,
					'price',
					$specific_price_output
				);

				$url_attribute = $link->getProductLink(
					$product,
					null,
					null,
					null,
					null,
					null,
					$order_product['product_attribute_id']
				);
				$product_name = ProductCore::getProductName($order_product['id_product'], $order_product['product_attribute_id']);
				$combination_images = ProductCore::getCombinationImageById((int) $order_product['product_attribute_id'], 1);

				if (!empty($combination_images)) {
					$image = new Image($combination_images['id_image']);
					$image_url = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
				} else {
					if ($id_image) {
						$image = new Image($id_image['id_image']);
						$image_url = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
					} else {
						$image_url = '';
					}
				}
				$items[] = array(
					'name' => $product_name,
					'description' => isset($product->description[1]) ? trim(strip_tags($product->description[1])) : '',
					'category' => $join_cat,
					'sku' => $order_product['product_reference'],
					'image_url' => $image_url,
					'item_url' => $url_attribute,
					'price' => number_format($order_product['unit_price_tax_incl'], 2, '.', ''),
					'quantity' => $order_product['product_quantity'],
					'discount' => is_numeric($discount_percent) ? round($discount_percent) : 0,
				);

			} else {

				$product_name = ProductCore::getProductName($order_product['id_product']);

				if ($id_image) {
					$image = new Image($id_image['id_image']);
					$image_url = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
				} else {
					$image_url = '';
				}

				$items[] = array(
					'name' => $product_name,
					'description' => $product->description[1],
					'category' => $join_cat,
					'sku' => $product->reference,
					'image_url' => $image_url,
					'item_url' => $url,
					'price' => number_format($price, 2, '.', ''),
					'quantity' => $order_product['product_quantity'],
					'discount' => is_numeric($discount_percent) ? round($discount_percent) : 0,
				);

			}
		}

		$data = array(

			'contact' => $this->Get_contact($order->id_customer),
			'status' => 'ordered',
			'current_state' => $order->current_state,
			'order_date' => date_format($date_format_clientify, 'Y-m-d H:i:s'),
			'order_id' => $order->id,
			'ecommerce' => 'prestashop',
			'shop_name' => $this->getShopName($this->data_config['id_shop']),
			'order_url' => $shop_url . "/index.php?controller=pdf-invoice?id_order=$order_id",
			'store_url' => $url_base,
			'currency' => $currency['iso_code'],
			'products' => $items,
			'shipping' => $order->total_shipping_tax_incl == 0 ? '0' : number_format($order->total_shipping_tax_incl, 2, '.', ''),
			'price' => number_format($order->total_paid_tax_incl, 2, '.', ''),
			'coupon' => $order->gift,
		);
		if (!empty($lang)) {
			$data['custom_field'] = array(
				'field' => 'ecommerce_language',
				'value' => $lang,
			);
		}

		$api = new ClientifyApi;
		$clientify_order = $api->Post_Order_Clientify($data);

		return $clientify_order;
	}

	public function get_product($product_id)
	{

		$product = new Product((int) $product_id);
		$url_base = $this->GetApiUrl($this->data_config['id_shop']);
		$id_image = Product::getCover($product->id);

		$categories = array();
		$sub_categories = array();
		$new_subcategories = array();

		// Consulta SQL para obtener las combinaciones del producto con su nombre y precio de venta
		$sql = "SELECT 
		pac.id_product_attribute,
		(SELECT SUM(quantity) FROM " . _DB_PREFIX_ . "stock_available WHERE id_product_attribute = pac.id_product_attribute) AS quantity,
		GROUP_CONCAT(agl.name, '-', al.name ORDER BY agl.id_attribute_group SEPARATOR '-') AS attribute_designation,
		pas.price,
		pa.reference
		FROM 
			" . _DB_PREFIX_ . "product_attribute_combination pac
		LEFT JOIN 
			" . _DB_PREFIX_ . "attribute a ON a.id_attribute = pac.id_attribute
		LEFT JOIN 
			" . _DB_PREFIX_ . "attribute_group ag ON ag.id_attribute_group = a.id_attribute_group
		LEFT JOIN 
			" . _DB_PREFIX_ . "attribute_lang al ON (a.id_attribute = al.id_attribute AND al.id_lang = 1)
		LEFT JOIN 
			" . _DB_PREFIX_ . "attribute_group_lang agl ON (ag.id_attribute_group = agl.id_attribute_group AND agl.id_lang = 1)
		LEFT JOIN 
			" . _DB_PREFIX_ . "product_attribute_shop pas ON pas.id_product_attribute = pac.id_product_attribute
		LEFT JOIN 
			" . _DB_PREFIX_ . "product_attribute pa ON pa.id_product_attribute = pac.id_product_attribute
		WHERE 
			pac.id_product_attribute IN (SELECT pa.id_product_attribute FROM " . _DB_PREFIX_ . "product_attribute pa WHERE pa.id_product = " . (int) $product_id . " GROUP BY pa.id_product_attribute)
		GROUP BY 
			pac.id_product_attribute";

		// Ejecutar la consulta
		$combinations = Db::getInstance()->executeS($sql);
		$terms = Db::getInstance()->executes(
			"SELECT distinct cp.id_category,cp.id_product , c.id_parent , c.is_root_category, cl.name
					FROM " . _DB_PREFIX_ . "category_product cp
					INNER JOIN " . _DB_PREFIX_ . "category c ON cp.id_category = c.id_category
					INNER JOIN " . _DB_PREFIX_ . "category_lang cl ON c.id_category = cl.id_category
					WHERE cp.id_product = " . $product_id . " AND cl.id_lang = 1"
		);

		if (is_array($terms)) {
			foreach ($terms as $term) {
				$term_search = new Category($term['id_parent'], Context::getContext()->language->id);

				if ($term['is_root_category'] == 1 || !$this->categoryExists($categories, $term['id_parent'])) {
					$categories[] = $term_search->id_category . ":" . $term_search->name;
				}
				$sub_categories[] = $term['id_category'] . ":" . $term['name'] . "|parent_id:" . $term['id_parent'];

				if (!$this->categoryExists($categories, $term['id_parent'])) {
					$term_search_subcat = new Category($term['id_parent'], Context::getContext()->language->id);
					if (!in_array($term_search_subcat->id_category . ":" . $term_search_subcat->name, $categories)) {
						$categories[] = $term_search_subcat->id_category . ":" . $term_search_subcat->name;
					}
				}
			}
		}

		$new_subcategories = array_unique($sub_categories);
		$join_cat = implode(",", $categories) . "/" . implode(",", $new_subcategories);

		$link = new Link();
		$url = $link->getProductLink($product);
		$price = Product::getPriceStatic($product_id);
		$default_currency_id = (int) Configuration::get('PS_CURRENCY_DEFAULT', null, null, $this->data_config['id_shop']);
		$currency = new Currency($default_currency_id);

		// Verificar si se encontraron combinaciones
		if (!empty($combinations)) {
			// Iterar sobre las combinaciones y mostrar la información
			$api = new ClientifyApi;
			foreach ($combinations as $combination) {
				$specific_price_output = array();
				$price = ProductCore::getPriceStatic(
					(int) $product_id,
					true, // tax
					$combination['id_product_attribute'],
					2, // precision
					null, // divise
					false, // only_reduction
					true, // use_reduc
					1, // quantity
					false, // force_cashe
					null, //id_currency
					'price', // price_tax_exc
					$specific_price_output
				);
				$url_attribute = $link->getProductLink(
					$product,
					null,
					null,
					null,
					null,
					null,
					$combination['id_product_attribute']
				);
				$product_name = ProductCore::getProductName($product_id, $combination['id_product_attribute']);
				$combination_images = ProductCore::getCombinationImageById((int) $combination['id_product_attribute'], 1);

				if (!empty($combination_images)) {
					$image = new Image($combination_images['id_image']);
					$image_url = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
				} else {
					if ($id_image) {
						$image = new Image($id_image['id_image']);
						$image_url = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
					} else {
						$image_url = '';
					}
				}
				$data = array(
					'status' => 'product',
					'store_url' => $url_base,
					'id' => (int) $combination['id_product_attribute'],
					'name' => $product_name,
					'description' => isset($product->description[1]) ? trim(strip_tags($product->description[1])) : '',
					'category' => $join_cat,
					'sku' => $combination['reference'],
					'image_url' => $image_url,
					'item_url' => $url_attribute,
					'price' => number_format($price, 2, '.', ''),
					'currency' => $currency->iso_code,
					'discount' => 0,
					//$discount
				);
				$clientify_product = $api->Post_Order_Clientify($data);
			}
			return $clientify_product;
		} else {

			$product_name = ProductCore::getProductName($product_id);

			if ($id_image) {
				$image = new Image($id_image['id_image']);
				$image_url = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
			} else {
				$image_url = '';
			}

			$data = array(
				'status' => 'product',
				'store_url' => $url_base,
				'id' => $product->id,
				'name' => $product_name,
				'description' => isset($product->description[1]) ? trim(strip_tags($product->description[1])) : '',
				'category' => $join_cat,
				'sku' => $product->reference,
				'image_url' => $image_url,
				'item_url' => $url,
				'price' => number_format($price, 2, '.', ''),
				'currency' => $currency->iso_code,
				'discount' => 0,
			);
			$api = new ClientifyApi;
			$clientify_product = $api->Post_Order_Clientify($data);
			return $clientify_product;

		}

	}

	public function get_abandoned_carts($params)
	{

		$page = (int) (!isset($params["page"])) ? 1 : $params["page"];
		$per_page = (int) $params["per_page"];
		$date_null = $params["date_init"] != 0 ? "between  '" . date("Y-m-d", strtotime($params["date_init"])) . "'  and '" . date("Y-m-d", strtotime($params["date_end"])) . "'" : '';
		$limit = $per_page != 0 ? 'LIMIT ' . (($page - 1) * $per_page) . ' , ' . $per_page . '' : '';

		$get_data = "SELECT * FROM " . _DB_PREFIX_ . "configuration_clientify";
		$results = Db::getInstance()->executeS($get_data);
		$shop = new Shop((int) $results[0]['id_shop']);
		$url_shop = $shop->getBaseURL();
		$all = array();

		$sq_data = 'SELECT DISTINCT t1.id_cart
		FROM ' . _DB_PREFIX_ . 'cart t1
		LEFT JOIN ' . _DB_PREFIX_ . 'orders t2 ON (t2.id_cart = t1.id_cart)
		LEFT JOIN ' . _DB_PREFIX_ . 'cart_product t3 ON (t3.id_cart = t1.id_cart)
		WHERE t2.id_cart IS null and t3.id_cart=t1.id_cart and DATE(t1.date_add)  ' . $date_null . ' and t1.id_shop = ' . $this->data_config['id_shop'] . ' ' . $limit;

		$cart_ids = Db::getInstance()->executes(
			$sq_data
		);

		if (isset($per_page)) {
			$to_per = $per_page != '' ? count($cart_ids) / $per_page : 0;
			$total_pages = is_float($to_per) ? intval($to_per + 1) : $to_per;
		}
		foreach ($cart_ids as $cart_id) {
			$sql = 'SELECT t1.*, t3.*
			FROM ' . _DB_PREFIX_ . 'cart t1
			LEFT JOIN ' . _DB_PREFIX_ . 'orders t2 ON (t2.id_cart = t1.id_cart)
			LEFT JOIN ' . _DB_PREFIX_ . 'cart_product t3 ON (t3.id_cart = t1.id_cart)
			LEFT JOIN ' . _DB_PREFIX_ . 'product_attribute pa ON (t3.id_product_attribute = pa.id_product_attribute)
			WHERE t3.id_cart = ' . (int) $cart_id['id_cart'] . ' AND t1.id_shop = ' . $this->data_config['id_shop'];

			$cart = Db::getInstance()->executes($sql);
			$id_customer = $cart[0]['id_customer'];
			$currency = Currency::getCurrency($cart[0]['id_currency']);
			$url_base = $this->GetApiUrl($this->data_config['id_shop']);
			$items = array();
			$cart_content = new Cart($cart[0]['id_cart']);
			$cartProducts = $cart_content->getProducts();
			$cart_details = $cart_content->getSummaryDetails();

			foreach ($cartProducts as $order_product) {

				$product = new Product((int) $order_product['id_product']);
				$link = new Link();
				$url = $link->getProductLink($product);
				$id_image = Product::getCover($product->id);
				if ($id_image) {
					$image = new Image($id_image['id_image']);
					$image_url = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
				} else {
					$image_url = '';
				}

				$categories = array();
				$sub_categories = array();
				$new_subcategories = array();

				$terms = Db::getInstance()->executes(
					"SELECT distinct cp.id_category,cp.id_product , c.id_parent , c.is_root_category, cl.name
					FROM " . _DB_PREFIX_ . "category_product cp
					INNER JOIN " . _DB_PREFIX_ . "category c ON cp.id_category = c.id_category
					INNER JOIN " . _DB_PREFIX_ . "category_lang cl ON c.id_category = cl.id_category
					WHERE cp.id_product = " . $order_product['id_product'] . " AND cl.id_lang = 1"
				);

				if (is_array($terms)) {
					foreach ($terms as $term) {
						$term_search = new Category($term['id_parent'], Context::getContext()->language->id);

						if ($term['is_root_category'] == 1 || !$this->categoryExists($categories, $term['id_parent'])) {
							$categories[] = $term_search->id_category . ":" . $term_search->name;
						}

						$sub_categories[] = $term['id_category'] . ":" . $term['name'] . "|parent_id:" . $term['id_parent'];

						if (!$this->categoryExists($categories, $term['id_parent'])) {
							$term_search_subcat = new Category($term['id_parent'], Context::getContext()->language->id);
							if (!in_array($term_search_subcat->id_category . ":" . $term_search_subcat->name, $categories)) {
								$categories[] = $term_search_subcat->id_category . ":" . $term_search_subcat->name;
							}
						}
					}
				}

				$new_subcategories = array_unique($sub_categories);
				$join_cat = implode(",", $categories) . "/" . implode(",", $new_subcategories);
				$price = $cart_details['total_price'];

				if ($price == 0) {
					$discount = 0;
				} else {
					// Calculamos el porcentaje de descuento
					$discount = ($cart_details['total_discounts'] * 100) / $price;
				}

				// Verificar si se encontraron combinaciones
				if (!empty($order_product['id_product_attribute'])) {

					$specific_price_output = array();
					$price = ProductCore::getPriceStatic(
						(int) $order_product['id_product'],
						true, // tax
						$order_product['id_product_attribute'],
						2, // precision
						null, // divise
						false, // only_reduction
						true, // use_reduc
						1, // quantity
						false, // force_cashe
						null, //id_currency
						'price', // price_tax_exc
						$specific_price_output
					);
					$url_attribute = $link->getProductLink(
						$product,
						null,
						null,
						null,
						null,
						null,
						$order_product['id_product_attribute']
					);
					$product_name = ProductCore::getProductName($order_product['id_product'], $order_product['id_product_attribute']);
					$combination_images = ProductCore::getCombinationImageById((int) $order_product['id_product_attribute'], 1);

					if (!empty($combination_images)) {
						$image = new Image($combination_images['id_image']);
						$image_url = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
					} else {
						if ($id_image) {
							$image = new Image($id_image['id_image']);
							$image_url = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
						} else {
							$image_url = '';
						}
					}
					$items[] = array(
						'name' => $product_name,
						'description' => isset($product->description[1]) ? trim(strip_tags($product->description[1])) : '',
						'category' => $join_cat,
						'sku' => $order_product['reference'],
						'image_url' => $image_url,
						'item_url' => $url_attribute,
						'price' => number_format($price, 2, '.', ''),
						'quantity' => $order_product['cart_quantity'],
						'discount' => is_numeric($discount) ? round($discount) : 0,
					);

				} else {
					$product_name = ProductCore::getProductName($order_product['id_product']);
					if ($id_image) {
						$image = new Image($id_image['id_image']);
						$image_url = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
					} else {
						$image_url = '';
					}

					$items[] = array(
						'name' => $product_name,
						'description' => $product->description[1],
						'category' => $join_cat,
						'sku' => $product->reference,
						'image_url' => $image_url,
						'item_url' => $url,
						'price' => number_format($product->getPrice(true, null, 2, null, false, false), 2, '.', ''),
						'quantity' => $order_product['cart_quantity'],
						'discount' => is_numeric($discount) ? round($discount) : 0,
					);


				}

			}
			$data = array(
				'status' => 'abandoned',
				'current_state' => 'abandoned',
				'contact' => $id_customer == 0 ? null : $this->Get_contact($id_customer),
				'abandoned_date' => date('Y-m-d', strtotime($cart[0]['date_add'])),
				'cart_id' => $cart[0]['id_cart'],
				'order_id' => $cart[0]['id_cart'],
				'ecommerce' => 'prestashop',
				'shop_name' => $this->getShopName($this->data_config['id_shop']),
				'order_url' => $url_shop . '/index.php?controller=order&recover_cart=' . $cart[0]['id_cart'],
				'store_url' => $url_base,
				'currency' => $currency['iso_code'],
				'products' => $items,
				'shipping' => $cart_details['total_shipping'] == 0 ? '0' : number_format($cart_details['total_shipping'], 2, '.', ''),
				'price' => number_format($cart_details['total_price'], 2, '.', ''),
				'coupon' => 0,

			);
			if ($data['contact'] != null) {
				$all[] = $data;
			}

		}
		$all_link = array(
			'data' => $all,
			'Link' => $total_pages != '' ? $total_pages : '',
		);
		return $all_link;
	}

	public function get_all_orders($params)
	{

		$all = array();
		$page = (int) (!isset($params["page"])) ? 1 : $params["page"];
		$per_page = (int) $params["per_page"];
		$date_null = $params["date_init"] != 0 ? "between  '" . date("Y-m-d", strtotime($params["date_init"])) . "'  and '" . date("Y-m-d", strtotime($params["date_end"])) . "'" : '';
		$limit = $per_page != 0 ? 'LIMIT ' . (($page - 1) * $per_page) . ' , ' . $per_page . '' : '';

		// Consulta SQL unificada para obtener los IDs de orden
		$query = "SELECT DISTINCT o.id_order 
				  FROM " . _DB_PREFIX_ . "orders o
				  LEFT JOIN " . _DB_PREFIX_ . "customer g ON (o.id_customer = g.id_customer) 
				  WHERE DATE(invoice_date) " . $date_null . " AND o.id_shop = " . $this->data_config['id_shop'] . "
				  ORDER BY o.id_order DESC " . $limit;

		// Ejecutar la consulta SQL
		$order_ids = Db::getInstance()->executes($query);

		// Obtener el número total de resultados sin usar count()
		$total = count($order_ids);

		// Calcular el número total de páginas
		if (isset($per_page) && $per_page != '') {
			$total_pages = ceil($total / $per_page);
		} else {
			$total_pages = 0;
		}

		foreach ($order_ids as $order_id) {
			$order = new Order($order_id['id_order']);
			$url_base = $this->GetApiUrl($this->data_config['id_shop']);
			$products = $order->getProducts();
			$currency = Currency::getCurrency($order->id_currency);
			$items = array();

			foreach ($products as $order_product) {

				$categories = array();
				$sub_categories = array();
				$new_subcategories = array();

				$product = new Product((int) $order_product['id_product']);
				$id_image = Product::getCover($product->id);
				$date_format_clientify = new DateTimeImmutable($order->date_add);
				$terms = Db::getInstance()->executes(
					"SELECT distinct cp.id_category,cp.id_product , c.id_parent , c.is_root_category, cl.name
					FROM " . _DB_PREFIX_ . "category_product cp
					INNER JOIN " . _DB_PREFIX_ . "category c ON cp.id_category = c.id_category
					INNER JOIN " . _DB_PREFIX_ . "category_lang cl ON c.id_category = cl.id_category
					WHERE cp.id_product = " . $order_product['id_product'] . " AND cl.id_lang = 1"
				);

				if (is_array($terms)) {
					foreach ($terms as $term) {
						$term_search = new Category($term['id_parent'], Context::getContext()->language->id);

						if ($term['is_root_category'] == 1 || !$this->categoryExists($categories, $term['id_parent'])) {
							$categories[] = $term_search->id_category . ":" . $term_search->name;
						}

						$sub_categories[] = $term['id_category'] . ":" . $term['name'] . "|parent_id:" . $term['id_parent'];

						if (!$this->categoryExists($categories, $term['id_parent'])) {
							$term_search_subcat = new Category($term['id_parent'], Context::getContext()->language->id);
							if (!in_array($term_search_subcat->id_category . ":" . $term_search_subcat->name, $categories)) {
								$categories[] = $term_search_subcat->id_category . ":" . $term_search_subcat->name;
							}
						}
					}
				}

				$new_subcategories = array_unique($sub_categories);
				$join_cat = implode(",", $categories) . "/" . implode(",", $new_subcategories);


				if ($id_image) {
					$image = new Image($id_image['id_image']);
					$image_url = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
				} else {
					$image_url = '';
				}
				$link = new Link();
				$url = $link->getProductLink($product);
				$url_2 = Tools::getHttpHost(true) . __PS_BASE_URI__;
				$price = $order_product['unit_price_tax_incl'];

				if ($order->total_products_wt == 0) {
					$discount = 0;
				} else {
					$discount = ($order->total_discounts * 100) / ($order->total_products_wt);
				}

				// Verificar si se encontraron combinaciones
				if (isset($order_product['product_type']) && ($order_product['product_type'] == 'combinations' || $order_product['product_attribute_id'] != 0)) {

					$specific_price_output = array();
					$price = ProductCore::getPriceStatic(
						(int) $order_product['id_product'],
						true, // tax
						$order_product['product_attribute_id'],
						2, // precision
						null, // divise
						false, // only_reduction
						true, // use_reduc
						1, // quantity
						false, // force_cashe
						null, //id_currency
						'price', // price_tax_exc
						$specific_price_output
					);
					$url_attribute = $link->getProductLink(
						$product,
						null,
						null,
						null,
						null,
						null,
						$order_product['product_attribute_id']
					);
					$product_name = ProductCore::getProductName($order_product['id_product'], $order_product['product_attribute_id']);
					$combination_images = ProductCore::getCombinationImageById((int) $order_product['product_attribute_id'], 1);

					if (!empty($combination_images)) {
						$image = new Image($combination_images['id_image']);
						$image_url = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
					} else {
						if ($id_image) {
							$image = new Image($id_image['id_image']);
							$image_url = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
						} else {
							$image_url = '';
						}
					}
					$items[] = array(
						'name' => $product_name,
						'description' => isset($product->description[1]) ? trim(strip_tags($product->description[1])) : '',
						'category' => $join_cat,
						'sku' => $order_product['product_reference'],
						'image_url' => $image_url,
						'item_url' => $url_attribute,
						'price' => number_format($order_product['unit_price_tax_incl'], 2, '.', ''),
						'quantity' => $order_product['product_quantity'],
						'discount' => is_numeric($discount) ? round($discount) : 0,
					);

				} else {

					$product_name = ProductCore::getProductName($order_product['id_product']);

					if ($id_image) {
						$image = new Image($id_image['id_image']);
						$image_url = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
					} else {
						$image_url = '';
					}

					$items[] = array(
						'name' => $product_name,
						'description' => isset($product->description[1]) ? trim(strip_tags($product->description[1])) : '',
						'category' => $join_cat,
						'sku' => $product->reference,
						'image_url' => $image_url,
						'item_url' => $url,
						'price' => number_format($order_product['unit_price_tax_incl'], 2, '.', ''),
						'quantity' => $order_product['product_quantity'],
						'discount' => is_numeric($discount) ? round($discount) : 0,
					);


				}
			}
			$data = array(

				'contact' => $this->Get_contact($order->id_customer),
				'status' => 'ordered',
				'current_state' => $order->current_state,
				'order_date' => date_format($date_format_clientify, 'Y-m-d H:i:s'),
				'order_id' => $order->id,
				'ecommerce' => 'prestashop',
				'shop_name' => $this->getShopName($this->data_config['id_shop']),
				'order_url' => $url_2 . "index.php?controller=pdf-invoice?id_order=" . $order_id['id_order'],
				'store_url' => $url_base,
				'currency' => $currency['iso_code'],
				'products' => $items,
				'shipping' => $order->total_shipping_tax_incl == 0 ? '0' : number_format($order->total_shipping_tax_incl, 2, '.', ''),
				'price' => number_format($order->total_paid_tax_incl, 2, '.', ''),
				// 'visitor_key' => (string)$this->getVisitorKeyByCartId($_COOKIE['vk']),
				'coupon' => $order->gift,
			);
			if (!empty($lang)) {
				$data['custom_field'] = array(
					'field' => 'ecommerce_language',
					'value' => $lang,
				);
			}


			$all[] = $data;
		}
		$all_link = array(
			'data' => $all,
			'Link' => $total_pages != '' ? $total_pages : '',
		);
		return $all_link;
	}

	public function get_all_contacts($params)
	{
		$all = array();
		$page = (int) (!isset($params["page"])) ? 1 : $params["page"];
		$per_page = (int) $params["per_page"];
		$date_null = $params["date_init"] != 0 ? "between  '" . date("Y-m-d", strtotime($params["date_init"])) . "'  and '" . date("Y-m-d", strtotime($params["date_end"])) . "'" : '';
		$limit = $per_page != 0 ? 'LIMIT ' . (($page - 1) * $per_page) . ' , ' . $per_page . '' : '';


		$contacts = Db::getInstance()->executes(
			"SELECT DISTINCT o.id_customer FROM " . _DB_PREFIX_ . "customer o LEFT JOIN " . _DB_PREFIX_ . "address g ON ( o.id_customer = g.id_customer ) WHERE DATE(o.date_add)  " . $date_null . " and o.id_shop =" . $this->data_config['id_shop'] . "
			ORDER BY o.id_customer desc " . $limit
		);

		if (isset($per_page)) {
			$to_per = $per_page != '' ? count($contacts) / $per_page : 0;
			$total_pages = is_float($to_per) ? intval($to_per + 1) : $to_per;
		}

		foreach ($contacts as $contact) {
			$user_id = $contact['id_customer'];
			$context = Context::getContext();
			$user = new Customer((int) $user_id);
			$site_name = $this->getShopName($this->data_config['id_shop']);
			$site_name = empty($site_name) ? 'prestashop' : $site_name;
			$lang = $context->language->iso_code;
			$address = new Address(Address::getFirstCustomerAddressId($user_id));
			$customer_phones = array();

			if ($user_id != 0) {
				$data = array(
					'id_customer' => $user_id,
					'email' => $user->email,
					'contact_source' => $site_name,
					'user_registered' => $user->date_add,
					'company' => empty($address->company) ? null : $address->company,
					'identification' => empty($address->dni) ? $address->vat_number : $address->dni,
					'custom_fields' => [],
					'gdpr_accept' => $this->has_gdpr_consent($user_id),
					'tags' => array(
						'prestashop',
						$site_name,
					)
				);

				if (!empty($user->firstname)) {
					$data['first_name'] = $user->firstname;
				}
				if (!empty($user->lastname)) {
					$data['last_name'] = $user->lastname;
				}
				if (!empty($lang)) {
					$data['custom_field'] = array(
						'field' => 'ecommerce_language',
						'value' => $lang,
					);
				}
				if (!empty($address)) {
					$street = $address->address1 . (!empty($address->address2) ? ', ' . $address->address2 : '');
					$city = $address->city;
					$country = $address->country;
					$postal_code = $address->postcode;
					$customer_address = array('type' => 1);

					if ($street) {
						$customer_address['street'] = $street;
					}
					if ($city) {
						$customer_address['city'] = $city;
					}
					if ($country) {
						$customer_address['country'] = $country;
					}
					if ($postal_code) {
						$customer_address['postal_code'] = $postal_code;
					}
					if (!empty($address->id_state)) {
						$customer_address['state'] = State::getNameById($address->id_state);
						if (empty($customer_address['state'])) {
							unset($customer_address['state']);
						}
					}
					$data['addresses'][] = $customer_address;
				}

				if (!empty($user->company)) {
					$data['company'] = $user->company;
				}
				if (!empty($address->phone) && !in_array($address->phone, $customer_phones)) {
					$data['phones'][] = array('phone' => $address->phone);
					$customer_phones[] = $address->phone;
				}
			}
			$all[] = $data;
		}
		$all_link = array(
			'data' => $all,
			'Link' => $total_pages != '' ? $total_pages : '',
		);
		return $all_link;
	}

	public function get_all_products($params)
	{
		$all = array();
		$url_base = $this->GetApiUrl($this->data_config['id_shop']);
		$new_categories = [];
		$page = (int) (!isset($params["page"])) ? 1 : $params["page"];
		$per_page = (int) $params["per_page"];
		$date_null = $params["date_init"] != 0 ? "between  '" . date("Y-m-d", strtotime($params["date_init"])) . "'  and '" . date("Y-m-d", strtotime($params["date_end"])) . "'" : '';
		$limit = $per_page != 0 ? 'LIMIT ' . (($page - 1) * $per_page) . ' , ' . $per_page . '' : '';

		$products = Db::getInstance()->executes(
			"SELECT o.id_product FROM " . _DB_PREFIX_ . "product o WHERE DATE(o.date_add)  " . $date_null . " and o.id_shop_default = " . $this->data_config['id_shop'] . "
			ORDER BY o.id_product desc " . $limit
		);

		if (isset($per_page)) {
			$to_per = $per_page != '' ? count($products) / $per_page : 0;
			$total_pages = is_float($to_per) ? intval($to_per + 1) : $to_per;
		}

		foreach ($products as $id_product) {

			$categories = array();
			$sub_categories = array();
			$new_subcategories = array();
			$context = Context::getContext();
			$product = new Product((int) $id_product['id_product']);

			// Consulta SQL para obtener las combinaciones del producto con su nombre y precio de venta
			$sql = "SELECT 
			pac.id_product_attribute,
			(SELECT SUM(quantity) FROM " . _DB_PREFIX_ . "stock_available WHERE id_product_attribute = pac.id_product_attribute) AS quantity,
			GROUP_CONCAT(agl.name, '-', al.name ORDER BY agl.id_attribute_group SEPARATOR '-') AS attribute_designation,
			pas.price,
			pa.reference
			FROM 
				" . _DB_PREFIX_ . "product_attribute_combination pac
			LEFT JOIN 
				" . _DB_PREFIX_ . "attribute a ON a.id_attribute = pac.id_attribute
			LEFT JOIN 
				" . _DB_PREFIX_ . "attribute_group ag ON ag.id_attribute_group = a.id_attribute_group
			LEFT JOIN 
				" . _DB_PREFIX_ . "attribute_lang al ON (a.id_attribute = al.id_attribute AND al.id_lang = 1)
			LEFT JOIN 
				" . _DB_PREFIX_ . "attribute_group_lang agl ON (ag.id_attribute_group = agl.id_attribute_group AND agl.id_lang = 1)
			LEFT JOIN 
				" . _DB_PREFIX_ . "product_attribute_shop pas ON pas.id_product_attribute = pac.id_product_attribute
			LEFT JOIN 
				" . _DB_PREFIX_ . "product_attribute pa ON pa.id_product_attribute = pac.id_product_attribute
			WHERE 
				pac.id_product_attribute IN (SELECT pa.id_product_attribute FROM " . _DB_PREFIX_ . "product_attribute pa WHERE pa.id_product = " . (int) $id_product['id_product'] . " GROUP BY pa.id_product_attribute)
			GROUP BY 
				pac.id_product_attribute";

			// Ejecutar la consulta
			$combinations = Db::getInstance()->executeS($sql);
			$id_image = Product::getCover($product->id);
			// $date_format_clientify = new DateTimeImmutable($order->date_add);
			// $category = Category::getCategoryInformation($product->id_category_default);				
			$terms = Db::getInstance()->executes(
				"SELECT distinct cp.id_category,cp.id_product , c.id_parent , c.is_root_category, cl.name
					FROM " . _DB_PREFIX_ . "category_product cp
					INNER JOIN " . _DB_PREFIX_ . "category c ON cp.id_category = c.id_category
					INNER JOIN " . _DB_PREFIX_ . "category_lang cl ON c.id_category = cl.id_category
					WHERE cp.id_product = " . $id_product['id_product'] . " AND cl.id_lang = 1"
			);

			if (is_array($terms)) {
				foreach ($terms as $term) {
					$cat = 0;
					$term_search = new Category($term['id_parent'], Context::getContext()->language->id);
					if ($term['is_root_category'] == 1) {
						$categories[] = $term['id_category'] . ":" . $term['name'];
					} else {
						foreach ($categories as $cat) {
							$cat_data = explode(":", $cat);
							(int) $cat_data[0] == $term['id_parent'] ? $cat = true : $cat = false;
						}
						if (!$cat) {
							if ($term_search->is_root_category == 1) {
								$categories[] = $term_search->id_category . ":" . $term_search->name;
							} else {
								$sub_categories[] = $term['id_category'] . ":" . $term['name'] . "|parent_id:" . $term['id_parent'];
								$term_search_subcat = new Category($term['id_parent'], Context::getContext()->language->id);
								if (!in_array($term_search_subcat->id_category . ":" . $term_search_subcat->name, $categories)) {
									$categories[] = $term_search_subcat->id_category . ":" . $term_search_subcat->name;
								}
							}
						}
						if (!in_array($term['id_category'] . ":" . $term['name'] . "|parent_id:" . $term_search->id_parent, $sub_categories)) {
							$sub_categories[] = $term['id_category'] . ":" . $term['name'] . "|parent_id:" . $term['id_parent'];
						}
					}
				}
			}

			foreach ($sub_categories as $cat_clean) {
				if (!in_array($cat_clean, $new_subcategories))
					$new_subcategories[] = $cat_clean;
			}

			$link = new Link();
			$url = $link->getProductLink($product);
			$price = Product::getPriceStatic($id_product['id_product']);
			$default_currency_id = (int) Configuration::get('PS_CURRENCY_DEFAULT', null, null, $this->data_config['id_shop']);
			$currency = new Currency($default_currency_id);
			$join_cat = implode(",", $categories) . "/" . implode(",", $new_subcategories);

			// Verificar si se encontraron combinaciones
			if (!empty($combinations)) {
				// Iterar sobre las combinaciones y mostrar la información
				foreach ($combinations as $combination) {
					$specific_price_output = array();
					$price = ProductCore::getPriceStatic(
						$id_product['id_product'],
						true, // tax
						$combination['id_product_attribute'],
						2, // precision
						null, // divise
						false, // only_reduction
						true, // use_reduc
						1, // quantity
						false, // force_cashe
						null, //id_currency
						'price', // price_tax_exc
						$specific_price_output
					);
					$url_attribute = $link->getProductLink(
						$product,
						null,
						null,
						null,
						null,
						null,
						$combination['id_product_attribute']
					);
					$product_name = ProductCore::getProductName($id_product['id_product'], $combination['id_product_attribute']);
					$combination_images = ProductCore::getCombinationImageById((int) $combination['id_product_attribute'], 1);

					if (!empty($combination_images)) {
						$image = new Image($combination_images['id_image']);
						$image_url = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
					} else {
						if ($id_image) {
							$image = new Image($id_image['id_image']);
							$image_url = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
						} else {
							$image_url = '';
						}
					}

					$data = array(
						'status' => 'product',
						'store_url' => $url_base,
						'id' => (int) $combination['id_product_attribute'],
						'name' => $product_name,
						'description' => isset($product->description[1]) ? trim(strip_tags($product->description[1])) : '',
						'category' => $join_cat,
						'sku' => $combination['reference'],
						'image_url' => $image_url,
						'item_url' => $url_attribute,
						'price' => number_format($price, 2, '.', ''),
						'currency' => $currency->iso_code,
						'discount' => 0,
						//$discount
					);
					$all[] = $data;
				}
			} else {

				$product_name = ProductCore::getProductName($id_product['id_product']);

				if ($id_image) {
					$image = new Image($id_image['id_image']);
					$image_url = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
				} else {
					$image_url = '';
				}

				$data = array(
					'status' => 'product',
					'store_url' => $url_base,
					'id' => $product->id,
					'name' => $product_name,
					'description' => isset($product->description[1]) ? trim(strip_tags($product->description[1])) : '',
					'category' => $join_cat,
					'sku' => $product->reference,
					'image_url' => $image_url,
					'item_url' => $url,
					'price' => number_format($price, 2, '.', ''),
					'currency' => $currency->iso_code,
					'discount' => 0,
					//$discount
				);
				$all[] = $data;

			}
		}

		$all_link = array(
			'data' => $all,
			'Link' => $total_pages != '' ? $total_pages : '',
		);
		return $all_link;
	}

	public function Data_response($data, $httpStatus)
	{
		http_response_code($httpStatus);
		header('Content-Type: application/json; charset=utf-8');

		if (isset($data['Link'])) {
			if ($data['Link'] != '') {
				header('Link:' . $data['Link']);
			}
			return json_encode($data['data']);
		} else {
			return json_encode($data);
		}
	}
	public function categoryExists($categories, $id_parent)
	{
		foreach ($categories as $cat) {
			$cat_data = explode(":", $cat);
			if ((int) $cat_data[0] == $id_parent) {
				return true;
			}
		}
		return false;
	}

	public function sync_abandoned_carts($params)
	{

		$page = (int) (!isset($params["page"])) ? 1 : $params["page"];
		$per_page = (int) $params["per_page"];
		$date_null = $params["date_init"] != 0 ? "between  '" . date("Y-m-d", strtotime($params["date_init"])) . "'  and '" . date("Y-m-d", strtotime($params["date_end"])) . "'" : '';
		$limit = $per_page != 0 ? 'LIMIT ' . (($page - 1) * $per_page) . ' , ' . $per_page . '' : '';

		$get_data = "SELECT * FROM " . _DB_PREFIX_ . "configuration_clientify";
		$results = Db::getInstance()->executeS($get_data);
		$shop = new Shop((int) $results[0]['id_shop']);
		$url_shop = $shop->getBaseURL();
		$all = array();

		$sq_data = 'SELECT DISTINCT t1.id_cart
		FROM ' . _DB_PREFIX_ . 'cart t1
		LEFT JOIN ' . _DB_PREFIX_ . 'orders t2 ON (t2.id_cart = t1.id_cart)
		LEFT JOIN ' . _DB_PREFIX_ . 'cart_product t3 ON (t3.id_cart = t1.id_cart)
		WHERE t2.id_cart IS null and t3.id_cart=t1.id_cart and DATE(t1.date_add)  ' . $date_null . ' and t1.id_shop = ' . $this->data_config['id_shop'] . ' ' . $limit;

		$cart_ids = Db::getInstance()->executes(
			$sq_data
		);

		if (isset($per_page)) {
			$to_per = $per_page != '' ? count($cart_ids) / $per_page : 0;
			$total_pages = is_float($to_per) ? intval($to_per + 1) : $to_per;
		}
		foreach ($cart_ids as $cart_id) {
			$sql = 'SELECT t1.*, t3.*
			FROM ' . _DB_PREFIX_ . 'cart t1
			LEFT JOIN ' . _DB_PREFIX_ . 'orders t2 ON (t2.id_cart = t1.id_cart)
			LEFT JOIN ' . _DB_PREFIX_ . 'cart_product t3 ON (t3.id_cart = t1.id_cart)
			LEFT JOIN ' . _DB_PREFIX_ . 'product_attribute pa ON (t3.id_product_attribute = pa.id_product_attribute)
			WHERE t3.id_cart = ' . (int) $cart_id['id_cart'] . ' AND t1.id_shop = ' . $this->data_config['id_shop'];

			$cart = Db::getInstance()->executes($sql);
			$id_customer = $cart[0]['id_customer'];
			$currency = Currency::getCurrency($cart[0]['id_currency']);
			$url_base = $this->GetApiUrl($this->data_config['id_shop']);
			$items = array();
			$cart_content = new Cart($cart[0]['id_cart']);
			$cartProducts = $cart_content->getProducts();
			$cart_details = $cart_content->getSummaryDetails();

			foreach ($cartProducts as $order_product) {

				$product = new Product((int) $order_product['id_product']);
				$link = new Link();
				$url = $link->getProductLink($product);
				$id_image = Product::getCover($product->id);
				if ($id_image) {
					$image = new Image($id_image['id_image']);
					$image_url = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
				} else {
					$image_url = '';
				}

				$categories = array();
				$sub_categories = array();
				$new_subcategories = array();

				$terms = Db::getInstance()->executes(
					"SELECT distinct cp.id_category,cp.id_product , c.id_parent , c.is_root_category, cl.name
					FROM " . _DB_PREFIX_ . "category_product cp
					INNER JOIN " . _DB_PREFIX_ . "category c ON cp.id_category = c.id_category
					INNER JOIN " . _DB_PREFIX_ . "category_lang cl ON c.id_category = cl.id_category
					WHERE cp.id_product = " . $order_product['id_product'] . " AND cl.id_lang = 1"
				);

				if (is_array($terms)) {
					foreach ($terms as $term) {
						$term_search = new Category($term['id_parent'], Context::getContext()->language->id);

						if ($term['is_root_category'] == 1 || !$this->categoryExists($categories, $term['id_parent'])) {
							$categories[] = $term_search->id_category . ":" . $term_search->name;
						}

						$sub_categories[] = $term['id_category'] . ":" . $term['name'] . "|parent_id:" . $term['id_parent'];

						if (!$this->categoryExists($categories, $term['id_parent'])) {
							$term_search_subcat = new Category($term['id_parent'], Context::getContext()->language->id);
							if (!in_array($term_search_subcat->id_category . ":" . $term_search_subcat->name, $categories)) {
								$categories[] = $term_search_subcat->id_category . ":" . $term_search_subcat->name;
							}
						}
					}
				}

				$new_subcategories = array_unique($sub_categories);
				$join_cat = implode(",", $categories) . "/" . implode(",", $new_subcategories);
				$price = $cart_details['total_price'];

				if ($price == 0) {
					$discount = 0;
				} else {
					// Calculamos el porcentaje de descuento
					$discount = ($cart_details['total_discounts'] * 100) / $cart_details['total_products_wt'];

				}

				// Verificar si se encontraron combinaciones
				if (!empty($order_product['id_product_attribute'])) {

					$specific_price_output = array();
					$price = ProductCore::getPriceStatic(
						(int) $order_product['id_product'],
						true, // tax
						$order_product['id_product_attribute'],
						2, // precision
						null, // divise
						false, // only_reduction
						true, // use_reduc
						1, // quantity
						false, // force_cashe
						null, //id_currency
						'price', // price_tax_exc
						$specific_price_output
					);
					$url_attribute = $link->getProductLink(
						$product,
						null,
						null,
						null,
						null,
						null,
						$order_product['id_product_attribute']
					);
					$product_name = ProductCore::getProductName($order_product['id_product'], $order_product['id_product_attribute']);
					$combination_images = ProductCore::getCombinationImageById((int) $order_product['id_product_attribute'], 1);

					if (!empty($combination_images)) {
						$image = new Image($combination_images['id_image']);
						$image_url = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
					} else {
						if ($id_image) {
							$image = new Image($id_image['id_image']);
							$image_url = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
						} else {
							$image_url = '';
						}
					}
					$items[] = array(
						'name' => $product_name,
						'description' => isset($product->description[1]) ? trim(strip_tags($product->description[1])) : '',
						'category' => $join_cat,
						'sku' => $order_product['reference'],
						'image_url' => $image_url,
						'item_url' => $url_attribute,
						'price' => number_format($price, 2, '.', ''),
						'quantity' => $order_product['cart_quantity'],
						'discount' => is_numeric($discount) ? round($discount) : 0,
					);

				} else {

					$product_name = ProductCore::getProductName($order_product['id_product']);

					if ($id_image) {
						$image = new Image($id_image['id_image']);
						$image_url = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
					} else {
						$image_url = '';
					}

					$items[] = array(
						'name' => $product_name,
						'description' => $product->description[1],
						'category' => $join_cat,
						'sku' => $product->reference,
						'image_url' => $image_url,
						'item_url' => $url,
						'price' => number_format($product->getPrice(true, null, 2, null, false, false), 2, '.', ''),
						'quantity' => $order_product['cart_quantity'],
						'discount' => is_numeric($discount) ? round($discount) : 0,
					);


				}

				$data = array(
					'status' => 'abandoned',
					'current_state' => 'abandoned',
					'contact' => $id_customer == 0 ? null : $this->Get_contact($id_customer),
					'abandoned_date' => date('Y-m-d', strtotime($cart[0]['date_add'])),
					'cart_id' => $cart[0]['id_cart'],
					'order_id' => $cart[0]['id_cart'],
					'ecommerce' => 'prestashop',
					'shop_name' => $this->getShopName($this->data_config['id_shop']),
					'order_url' => $url_shop . '/index.php?controller=order&recover_cart=' . $cart[0]['id_cart'],
					'store_url' => $url_base,
					'currency' => $currency['iso_code'],
					'products' => $items,
					'shipping' => $cart_details['total_shipping'] == 0 ? '0' : number_format($cart_details['total_shipping'], 2, '.', ''),
					'price' => number_format($cart_details['total_price'], 2, '.', ''),
					'coupon' => 0,

				);
				//Send data to Clientify
				if ($data['contact'] != null) {

					$api = new ClientifyApi;
					$abandoned = $api->Post_Order_Clientify($data);
					$result_sync[] = $abandoned;

					$all[] = $result_sync;
				}
			}


		}
		return $all;
	}

	public function sync_all_orders($params)
	{

		$all = array();
		$result_sync = array();
		$page = (int) (!isset($params["page"])) ? 1 : $params["page"];
		$per_page = (int) $params["per_page"];
		$date_null = $params["date_init"] != 0 ? "between  '" . date("Y-m-d", strtotime($params["date_init"])) . "'  and '" . date("Y-m-d", strtotime($params["date_end"])) . "'" : '';
		$limit = $per_page != 0 ? 'LIMIT ' . (($page - 1) * $per_page) . ' , ' . $per_page . '' : '';

		// Consulta SQL unificada para obtener los IDs de orden
		$query = "SELECT DISTINCT o.id_order 
				  FROM " . _DB_PREFIX_ . "orders o
				  LEFT JOIN " . _DB_PREFIX_ . "customer g ON (o.id_customer = g.id_customer) 
				  WHERE DATE(invoice_date) " . $date_null . " AND o.id_shop = " . $this->data_config['id_shop'] . "
				  ORDER BY o.id_order DESC " . $limit;
		// Ejecutar la consulta SQL
		$order_ids = Db::getInstance()->executes($query);

		// Obtener el número total de resultados sin usar count()
		$total = count($order_ids);

		// Calcular el número total de páginas
		if (isset($per_page) && $per_page != '') {
			$total_pages = ceil($total / $per_page);
		} else {
			$total_pages = 0;
		}

		foreach ($order_ids as $order_id) {
			$order = new Order($order_id['id_order']);
			$url_base = $this->GetApiUrl($this->data_config['id_shop']);
			$products = $order->getProducts();
			$currency = Currency::getCurrency($order->id_currency);
			$items = array();

			foreach ($products as $order_product) {

				$categories = array();
				$sub_categories = array();
				$new_subcategories = array();

				$product = new Product((int) $order_product['id_product']);
				$id_image = Product::getCover($product->id);
				$date_format_clientify = new DateTimeImmutable($order->date_add);
				$terms = Db::getInstance()->executes(
					"SELECT distinct cp.id_category,cp.id_product , c.id_parent , c.is_root_category, cl.name
					FROM " . _DB_PREFIX_ . "category_product cp
					INNER JOIN " . _DB_PREFIX_ . "category c ON cp.id_category = c.id_category
					INNER JOIN " . _DB_PREFIX_ . "category_lang cl ON c.id_category = cl.id_category
					WHERE cp.id_product = " . $order_product['id_product'] . " AND cl.id_lang = 1"
				);
				if (is_array($terms)) {
					foreach ($terms as $term) {
						$term_search = new Category($term['id_parent'], Context::getContext()->language->id);

						if ($term['is_root_category'] == 1 || !$this->categoryExists($categories, $term['id_parent'])) {
							$categories[] = $term_search->id_category . ":" . $term_search->name;
						}

						$sub_categories[] = $term['id_category'] . ":" . $term['name'] . "|parent_id:" . $term['id_parent'];

						if (!$this->categoryExists($categories, $term['id_parent'])) {
							$term_search_subcat = new Category($term['id_parent'], Context::getContext()->language->id);
							if (!in_array($term_search_subcat->id_category . ":" . $term_search_subcat->name, $categories)) {
								$categories[] = $term_search_subcat->id_category . ":" . $term_search_subcat->name;
							}
						}
					}
				}

				$new_subcategories = array_unique($sub_categories);
				$join_cat = implode(",", $categories) . "/" . implode(",", $new_subcategories);


				if ($id_image) {
					$image = new Image($id_image['id_image']);
					$image_url = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
				} else {
					$image_url = '';
				}
				$link = new Link();
				$url = $link->getProductLink($product);
				$url_2 = Tools::getHttpHost(true) . __PS_BASE_URI__;
				$price = $order_product['unit_price_tax_incl'];

				if ($order->total_products_wt == 0) {
					$discount = 0;
				} else {
					$discount = ($order->total_discounts * 100) / ($order->total_products_wt);
				}

				// Verificar si se encontraron combinaciones
				if (isset($order_product['product_type']) && ($order_product['product_type'] == 'combinations' || $order_product['product_attribute_id'] != 0)) {

					$specific_price_output = array();
					$price = ProductCore::getPriceStatic(
						(int) $order_product['id_product'],
						true, // tax
						$order_product['product_attribute_id'],
						2, // precision
						null, // divise
						false, // only_reduction
						true, // use_reduc
						1, // quantity
						false, // force_cashe
						null, //id_currency
						'price', // price_tax_exc
						$specific_price_output
					);
					$url_attribute = $link->getProductLink(
						$product,
						null,
						null,
						null,
						null,
						null,
						$order_product['product_attribute_id']
					);
					$product_name = ProductCore::getProductName($order_product['id_product'], $order_product['product_attribute_id']);
					$combination_images = ProductCore::getCombinationImageById((int) $order_product['product_attribute_id'], 1);

					if (!empty($combination_images)) {
						$image = new Image($combination_images['id_image']);
						$image_url = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
					} else {
						if ($id_image) {
							$image = new Image($id_image['id_image']);
							$image_url = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
						} else {
							$image_url = '';
						}
					}
					$items[] = array(
						'name' => $product_name,
						'description' => isset($product->description[1]) ? trim(strip_tags($product->description[1])) : '',
						'category' => $join_cat,
						'sku' => $order_product['product_reference'],
						'image_url' => $image_url,
						'item_url' => $url_attribute,
						'price' => number_format($order_product['unit_price_tax_incl'], 2, '.', ''),
						'quantity' => $order_product['product_quantity'],
						'discount' => is_numeric($discount) ? round($discount) : 0,
					);

				} else {

					$product_name = ProductCore::getProductName($order_product['id_product']);

					if ($id_image) {
						$image = new Image($id_image['id_image']);
						$image_url = _PS_BASE_URL_ . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
					} else {
						$image_url = '';
					}

					$items[] = array(
						'name' => $product_name,
						'description' => $product->description[1],
						'category' => $join_cat,
						'sku' => $product->reference,
						'image_url' => $image_url,
						'item_url' => $url,
						'price' => number_format($order_product['unit_price_tax_incl'], 2, '.', ''),
						'quantity' => $order_product['product_quantity'],
						'discount' => is_numeric($discount) ? round($discount) : 0,
					);


				}

			}
			$data = array(

				'contact' => $this->Get_contact($order->id_customer),
				'status' => 'ordered',
				'current_state' => $order->current_state,
				'order_date' => date_format($date_format_clientify, 'Y-m-d H:i:s'),
				'order_id' => $order->id,
				'ecommerce' => 'prestashop',
				'shop_name' => $this->getShopName($this->data_config['id_shop']),
				'order_url' => $url_2 . "index.php?controller=pdf-invoice?id_order=" . $order_id['id_order'],
				'store_url' => $url_base,
				'currency' => $currency['iso_code'],
				'products' => $items,
				'shipping' => $order->total_shipping_tax_incl == 0 ? '0' : number_format($order->total_shipping_tax_incl, 2, '.', ''),
				'price' => number_format($order->total_paid_tax_incl, 2, '.', ''),
				// 'visitor_key' => (string)$this->getVisitorKeyByCartId($_COOKIE['vk']),
				'coupon' => $order->gift,
			);
			if (!empty($lang)) {
				$data['custom_field'] = array(
					'field' => 'ecommerce_language',
					'value' => $lang,
				);
			}

			//Send data to Clientify
			$api = new ClientifyApi;
			$orders = $api->Post_Order_Clientify($data);
			$result_sync[] = $orders;

			$all[] = $data;
		}

		return $result_sync;
	}

	public function sync_all_contacts($params)
	{
		$all = array();
		$result_sync = array();
		$page = (int) (!isset($params["page"])) ? 1 : $params["page"];
		$per_page = (int) $params["per_page"];
		$date_null = $params["date_init"] != 0 ? "between  '" . date("Y-m-d", strtotime($params["date_init"])) . "'  and '" . date("Y-m-d", strtotime($params["date_end"])) . "'" : '';
		$limit = $per_page != 0 ? 'LIMIT ' . (($page - 1) * $per_page) . ' , ' . $per_page . '' : '';
		$store_url = $this->GetApiUrl($this->data_config['id_shop']);

		$contacts = Db::getInstance()->executes(
			"SELECT DISTINCT o.id_customer FROM " . _DB_PREFIX_ . "customer o LEFT JOIN " . _DB_PREFIX_ . "address g ON ( o.id_customer = g.id_customer ) WHERE DATE(o.date_add)  " . $date_null . " and o.id_shop =" . $this->data_config['id_shop'] . "
			ORDER BY o.id_customer desc " . $limit
		);

		if (isset($per_page)) {
			$to_per = $per_page != '' ? count($contacts) / $per_page : 0;
			$total_pages = is_float($to_per) ? intval($to_per + 1) : $to_per;
		}

		foreach ($contacts as $contact) {
			$user_id = $contact['id_customer'];
			$context = Context::getContext();
			$user = new Customer((int) $user_id);
			$site_name = $this->getShopName($this->data_config['id_shop']);
			$site_name = empty($site_name) ? 'prestashop' : $site_name;
			$lang = $context->language->iso_code;
			$address = new Address(Address::getFirstCustomerAddressId($user_id));
			$customer_phones = array();

			if ($user_id != 0) {
				$data = array(
					'status' => 'customer',
					'id_customer' => $user_id,
					'email' => $user->email,
					'contact_source' => $site_name,
					'user_registered' => $user->date_add,
					'company' => empty($address->company) ? null : $address->company,
					'identification' => empty($address->dni) ? $address->vat_number : $address->dni,
					'custom_fields' => [],
					'gdpr_accept' => $this->has_gdpr_consent($user_id),
					'store_url' => $store_url,
					'tags' => array(
						'prestashop',
						$site_name,
					)
				);

				if (!empty($user->firstname)) {
					$data['first_name'] = $user->firstname;
				}
				if (!empty($user->lastname)) {
					$data['last_name'] = $user->lastname;
				}
				if (!empty($lang)) {
					$data['custom_field'] = array(
						'field' => 'ecommerce_language',
						'value' => $lang,
					);
				}
				if (!empty($address)) {
					$street = $address->address1 . (!empty($address->address2) ? ', ' . $address->address2 : '');
					$city = $address->city;
					$country = $address->country;
					$postal_code = $address->postcode;
					$customer_address = array('type' => 1);

					if ($street) {
						$customer_address['street'] = $street;
					}
					if ($city) {
						$customer_address['city'] = $city;
					}
					if ($country) {
						$customer_address['country'] = $country;
					}
					if ($postal_code) {
						$customer_address['postal_code'] = $postal_code;
					}
					if (!empty($address->id_state)) {
						$customer_address['state'] = State::getNameById($address->id_state);
						if (empty($customer_address['state'])) {
							unset($customer_address['state']);
						}
					}
					$data['addresses'][] = $customer_address;
				}

				if (!empty($user->company)) {
					$data['company'] = $user->company;
				}
				if (!empty($address->phone) && !in_array($address->phone, $customer_phones)) {
					$data['phones'][] = array('phone' => $address->phone);
					$customer_phones[] = $address->phone;
				}
			}
			//Send data to Clientify
			$api = new ClientifyApi;
			$contacts_sync = $api->Post_Contacts_Clientify($data);
			$result_sync[] = $contacts_sync;

			$all[] = $data;
		}

		return $result_sync;
	}

	function get_data_pluginserver()
	{
		// Obtener datos del servidor
		$datosServidor = array(
			'os' => php_uname('s'),
			'php_version' => phpversion(),
			//'ip_server' => $_SERVER['SERVER_ADDR'],
			'server_name' => $_SERVER['SERVER_NAME']
		);

		// Obtener versión de PrestaShop
		$archivoConfiguracion = _PS_ROOT_DIR_ . '/config/config.inc.php';
		if (file_exists($archivoConfiguracion)) {
			require_once($archivoConfiguracion);

			// Verificar si la constante _PS_VERSION_ ya está definida
			if (!defined('_PS_VERSION_')) {
				$datosPrestashop = array(
					'error' => 'La versión de PrestaShop no está definida'
				);
			} else {
				$datosPrestashop = array(
					'prestashop_version' => _PS_VERSION_,
					'prestashop_multishop' => shop::isFeatureActive(),
					'shop_name' => Configuration::get('PS_SHOP_NAME'),
					'url_tienda' => Tools::getShopDomain(true, true) . __PS_BASE_URI__
				);
			}
		} else {
			$datosPrestashop = array(
				'error' => 'No se pudo encontrar el archivo de configuración de PrestaShop'
			);
		}

		// Obtener versión del módulo
		if (class_exists('clientify')) {
			$configuiration_clientify = $this->getIdShopConfigClientify();
			$moduloClientify = new clientify();
			$datosModulo = array(
				'module_name' => $moduloClientify->name,
				'module_author' => $moduloClientify->author,
				'module_store_key' => $configuiration_clientify['clientify_store_key'],
				'module_version' => $moduloClientify->version,
				'module_status' => $configuiration_clientify['clientify_module_status'],
				'id_shop' => $configuiration_clientify['id_shop'],
				'clientify_script' => $configuiration_clientify['clientify_script'],
			);
		} else {
			$datosModulo = array(
				'error' => 'No se encontró la clase del módulo clientify'
			);
		}

		// Combinar datos y devolverlos
		$datosCombinados = array_merge($datosServidor, $datosPrestashop, $datosModulo);
		return $datosCombinados;
	}

}