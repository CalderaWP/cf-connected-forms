<?php
/**
 * @package   Caldera_Forms_Connector
 * @author    Josh Pollock <Josh@CalderaWP.com>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock for CalderaWP
 *
 * @wordpress-plugin
 * Plugin Name: Caldera Forms Connector
 * Plugin URI:  https://calderawp.com/downloads/caldera-forms-connector
 * Description: Connect multiple Caldera Forms into a sequence of forms
 * Version: 0.1.0
 * Author:      Josh Pollock <Josh@CalderaWP.com>
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
define( 'CF_FORM_CON_VER', '0.1.0' );

// add filter to register addon with Caldera Forms
add_filter('caldera_forms_get_form_processors', 'cf_form_connector_register');

//add filter to change form when needed
add_filter( 'caldera_forms_render_get_form', 'cf_form_connector_change_form' );

// pull in the functions file
include CF_FORM_CON_PATH . 'includes/functions.php';



