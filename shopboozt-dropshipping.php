<?PHP
/**
 * Plugin Name: Shopboozt Dropshipping
 * Plugin URI: 
 * Description: Dropship products
 * Author: Shopboozt
 * Author URI: https://www.shopboozt.com/
 * Version: 1.0.0
 * WC requires at least: 2.6.0
 * WC tested up to: 3.5.7
 *
 * Copyright: (c) 2018 Shopboozt. (info@shopboozt.com)
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package     shopboozt-dropshipping
 * @author      Shopboozt
 * @Category    Plugin
 * @copyright   Copyright (c) 2018 Shopboozt
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */
	if ( !function_exists( 'add_action' ) ) {
		echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
		exit;
	}

	define( 'SHOPBOOZT_DROPSHIPPING_VERSION', '1.0.0' );
	define( 'SHOPBOOZT_DROPSHIPPING__MINIMUM_WP_VERSION', '4.0' );
	define( 'SHOPBOOZT_DROPSHIPPING__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
	define( 'SHOPBOOZT_DROPSHIPPING__PLUGIN_URL', plugin_dir_url( __FILE__ ) );

	require_once( SHOPBOOZT_DROPSHIPPING__PLUGIN_DIR . 'Shopboozt_Dropshipping_Admin.php' );
	add_action( 'init', array( 'Shopboozt_Dropshipping_Admin', 'init' ) );
	register_activation_hook( __FILE__, array('Shopboozt_Dropshipping_Admin','install') );
	register_deactivation_hook( __FILE__, array('Shopboozt_Dropshipping_Admin','uninstall') );

	add_filter( 'determine_current_user', array( 'Shopboozt_Dropshipping_Admin', 'json_basic_auth_handler' ), 10, 1 );
	add_filter( 'rest_authentication_errors', array( 'Shopboozt_Dropshipping_Admin', 'json_basic_auth_error' ) );
?>