<?php
/**
 * @package   CF_Connected_Forms
 * @author    Josh Pollock <Josh@CalderaWP.com>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 David Cramer & Josh Pollock for CalderaWP
 *
 * @wordpress-plugin
 * Plugin Name: Connected Caldera Forms
 * Plugin URI:  https://calderawp.com/downloads/caldera-forms-connector
 * Description: Connect multiple Caldera Forms into a sequence of forms
 * Version: 1.0.5
 * Author:      Josh Pollock & David Cramer <Calderasaurus@CalderaWP.com>
 * Author URI:  http://calderawp.com
 * Text Domain: cf-form-connector
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// define constants
define( 'CF_FORM_CON_PATH',  plugin_dir_path( __FILE__ ) );
define( 'CF_FORM_CON_URL',  plugin_dir_url( __FILE__ ) );
define( 'CF_FORM_CON_SLUG', '_users_connected_forms_dev' );
define( 'CF_FORM_CON_VER', '1.0.5' );
define( 'CF_FORM_CON_CORE', dirname( __FILE__ )  );
define( 'CF_FORM_CON_BASENAME', plugin_basename( __FILE__ ) );


// Load instance
add_action( 'plugins_loaded', 'cf_form_connector_init', 1 );
function cf_form_connector_init(){
	if (  ! version_compare( PHP_VERSION, '5.3.0', '>=' ) ) {
		if ( is_admin() || defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			include_once CF_FORM_CON_PATH . 'vendor/calderawp/dismissible-notice/src/functions.php';
		}

		if ( is_admin() ) {
			//BIG nope nope nope!

			$message = __( sprintf( 'Connected Forms for Caldera Forms requires PHP version %1s or later. We strongly recommend PHP 5.5 or later for security and performance reasons. Current version is %2s.', '5.3.0', PHP_VERSION ), 'cf-form-connector' );
			echo caldera_warnings_dismissible_notice( $message, true, 'activate_plugins' );
		}

	}else{
		// load dependencies
		include_once trailingslashit( CF_FORM_CON_PATH ) . 'vendor/autoload.php';
		include trailingslashit( CF_FORM_CON_PATH ) . 'includes/functions.php';
		add_filter('caldera_forms_get_form_processors', 'cf_form_connector_register');

	}

}
//add activation hook
add_action( 'activate_' . CF_FORM_CON_BASENAME,  'cf_form_connector_activate' );

/**
 * Plugin activation callback.
 */
function cf_form_connector_activate(){
	global $wp_version;
	wp_remote_get( add_query_arg( array( 'wp' => urlencode( $wp_version ), 'php' => urlencode( PHP_VERSION ), 'url' => urlencode( site_url() ) ),  'http://apicaldera.wpengine.com/wp-json/calderawp_api/v2/notices'  ) );
};




