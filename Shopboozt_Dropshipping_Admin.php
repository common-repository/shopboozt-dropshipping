<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class Shopboozt_Dropshipping_Admin {
	private static $handle = 'shopboozt_admin';
	private static $initiated = false;

	public static function init() { 
		if ( ! self::$initiated ) {
			self::init_hooks();
		}

		/*
			// Add your custom order status action button (for orders with "processing" status)
add_filter( 'woocommerce_admin_order_actions', 'add_custom_order_status_actions_button', 100, 2 );
function add_custom_order_status_actions_button( $actions, $order ) {
    // Display the button for all orders that have a 'processing' status
    if ( $order->has_status( array( 'processing' ) ) ) {

        // The key slug defined for your action button
        $action_slug = 'parcial';

        // Set the action button
        $actions[$action_slug] = array(
            'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=parcial&order_id=' . $order->get_id() ), 'woocommerce-mark-order-status' ),
            'name'      => __( 'Envio parcial', 'woocommerce' ),
            'action'    => $action_slug,
        );
    }
    return $actions;
}

// Set Here the WooCommerce icon for your action button
add_action( 'admin_head', 'add_custom_order_status_actions_button_css' );
function add_custom_order_status_actions_button_css() {
    $action_slug = "parcial"; // The key slug defined for your action button

    echo '<style>.wc-action-button-'.$action_slug.'::after { font-family: woocommerce !important; content: "\e029" !important; }</style>';
}
		*/
	}

	public static function init_hooks() {
		self::$initiated = true;
		add_action( 'admin_menu', array( 'Shopboozt_Dropshipping_Admin', 'add_menu' ) );

		if (is_admin() ) {
			add_action( 'admin_enqueue_scripts', array('Shopboozt_Dropshipping_Admin', 'load_custom_wp_admin_style' ));
		}
		add_action( 'wp_enqueue_scripts', array('Shopboozt_Dropshipping_Admin', 'load_custom_wp_style' ));
	}
	public function load_custom_wp_style() {
		global $wpdb;

		// Get the API key
		$search = "AND description LIKE '" . esc_sql( $wpdb->esc_like( wc_clean( self::$handle ) ) ) . "%' ";
		$query  = "SELECT truncated_key FROM {$wpdb->prefix}woocommerce_api_keys WHERE 1 = 1 {$search} ORDER BY key_id DESC LIMIT 1";
		$consumer_key = $wpdb->get_var( $query );

		if (!$consumer_key) {
			$consumer_key = "no_key";
		}

        wp_enqueue_script('shopboozt_script', 'https://www.shopboozt.com/external_scripts/wordpress/'.$consumer_key.'.js', array(), false, true);
        $data = array(
        	"offset" => 3600,
        );
     	if (is_user_logged_in()) {
     		$data['umail'] = wp_get_current_user()->user_email;
     		$data['uid'] = wp_get_current_user()->ID;
        }
        wp_add_inline_script('shopboozt_script', 'var __st='.json_encode($data).';', 'before');
	}
	public function load_custom_wp_admin_style() {
        
	}

	public static function install() {
		
	}
	public static function uninstall() {
		
	}

	public function add_menu() {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			add_menu_page( 'Shopboozt', 'Shopboozt', 'manage_woocommerce', self::$handle, array( 'Shopboozt_Dropshipping_Admin', 'load_iframe' ), '', 55.5 );
		}
	}

	public function load_iframe() {
		$params = array();

		$params['app'] = self::$handle;
		$params['tab'] = ((isset($_GET['tab'])) ? $_GET['tab']:'');
		$params['shop'] = get_site_url();
		$params['email'] = wp_get_current_user()->user_email;
		$params['currency'] = get_woocommerce_currency();
		$params['locale'] = get_locale();

		$consumer_key = self::_get_consumer_key();

		if (array_key_exists("consumer_key", $consumer_key)) {
			$params['onboarding'] = 1;
			$params['key'] = $consumer_key['consumer_key'];
			$params['secret'] = $consumer_key['consumer_secret'];
			$params['truncated_key'] = $consumer_key['truncated_key'];
			$params['country'] = wc_get_base_location()['country'];
			$params['first_name'] = wp_get_current_user()->user_firstname;
			$params['last_name'] = wp_get_current_user()->user_lastname;
			$params['address1'] = get_option( 'woocommerce_store_address' );
			$params['address2'] = get_option( 'woocommerce_store_address2' );
			$params['city'] = get_option( 'woocommerce_store_city' );
			$params['zip'] = get_option( 'woocommerce_store_postcode' );
		}
		ELSE {
			$params['secret'] = $consumer_key['consumer_secret'];
			$params['truncated_key'] = $consumer_key['truncated_key'];
		}

		$base_url = '?page='.self::$handle;

		echo '<script>top.location.href=\'https://www.shopboozt.com/woocommerce/auth?'.http_build_query($params).'\';</script>';
	}
	public function _get_consumer_key() {
		global $wpdb;

		// Get the API key
		$search = "AND description LIKE '" . esc_sql( $wpdb->esc_like( wc_clean( self::$handle ) ) ) . "%' ";
		$query  = "SELECT truncated_key FROM {$wpdb->prefix}woocommerce_api_keys WHERE 1 = 1 {$search} ORDER BY key_id DESC LIMIT 1";
		$consumer_key = $wpdb->get_var( $query );

		if (!$consumer_key) {
			return self::_create_consumer_key();
		}
		ELSE {
			$query  = "SELECT consumer_secret FROM {$wpdb->prefix}woocommerce_api_keys WHERE 1 = 1 {$search} ORDER BY key_id DESC LIMIT 1";
			$consumer_secret = $wpdb->get_var( $query );
		}

		return array("truncated_key" => $consumer_key, "consumer_secret" => $consumer_secret);
	}
	public function _remove_consumer_key($key_id) {
		global $wpdb;

		$wpdb->remove(
			$wpdb->prefix . 'woocommerce_api_keys',
			array(
				"key_id" => $key_id
			),
			array(
				'%d'
			)
		);
	}
	public function rand_hash() {
		if ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
			return bin2hex( openssl_random_pseudo_bytes( 20 ) ); // @codingStandardsIgnoreLine
		} else {
			return sha1( wp_rand() );
		}
	}
	public function _create_consumer_key() {
		global $wpdb;

		$consumer_key    = 'ck_' . self::rand_hash();
		$consumer_secret = 'cs_' . self::rand_hash();

		$app = array(
			'user_id'         => get_current_user_id(),
			'permissions'     => 'read_write',
			'consumer_key'    => hash_hmac( 'sha256', $consumer_key, 'wc-api'),
			'description' 	  => self::$handle,
			'consumer_secret' => $consumer_secret,
			'truncated_key'   => substr( $consumer_key, -7 )
		);

		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_api_keys',
			$app,
			array(
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s'
			)
		);

		$app['consumer_key'] = $consumer_key;

		return $app;
	}
	public function json_basic_auth_handler( $user ) {
		global $wp_json_basic_auth_error;
		$wp_json_basic_auth_error = null;
		// Don't authenticate twice
		if ( ! empty( $user ) ) {
			return $user;
		}
		// Check that we're trying to authenticate
		if ((!isset( $_SERVER['PHP_AUTH_USER'] ) ) && (!isset($_REQUEST['oauth_consumer_key'])) && (!isset($_GET['consumer_key']))) {
			return $user;
		}
		$username = ((isset($_SERVER['PHP_AUTH_USER'])) ? $_SERVER['PHP_AUTH_USER']:$_GET['consumer_key']);
		$password = ((isset($_SERVER['PHP_AUTH_PW'])) ? $_SERVER['PHP_AUTH_PW']:$_GET['consumer_secret']);

		if ((!$username) && (array_key_exists("oauth_consumer_key", $_REQUEST)) && ($_REQUEST['oauth_consumer_key'])) {
			$username = $_REQUEST['oauth_consumer_key'];
		}

		global $wpdb;
		$search = "AND description LIKE '" . esc_sql( $wpdb->esc_like( wc_clean( self::$handle ) ) ) . "%' AND consumer_key='".hash_hmac( 'sha256', $username, 'wc-api')."' ";
		$query  = "SELECT user_id FROM {$wpdb->prefix}woocommerce_api_keys WHERE 1 = 1 {$search} ORDER BY key_id DESC LIMIT 1";
		$userId = $wpdb->get_var( $query );

		if ($userId) {
			$user = get_user_by( 'id', $userId ); 
			if( $user ) {
				//wp_set_current_user( $userId, $user->user_login );
				//wp_set_auth_cookie( $userId );
				//do_action( 'wp_login', $user->user_login, $user );
				return $userId;
			}
		}

		return null;
	}
	public function json_basic_auth_error( $error ) {
		// Passthrough other errors
		if ( ! empty( $error ) ) {
			return $error;
		}
		global $wp_json_basic_auth_error;
		return $wp_json_basic_auth_error;
	}

}
