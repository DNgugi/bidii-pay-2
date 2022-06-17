<?php

/**
 * Plugin Name: Bidii Pay WooCommerce Payment Gateway
 * Plugin URI:        https://teambidii.co.ke/bidii-pay
 * Description:       Handle M-Pesa payments on WordPress with this plugin.
 * Version:           0.1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Team Bidii Consulting
 * Author URI:        https://teambidii.co.ke
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bidii-pay
 * Domain Path:       /languages

 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
 
 
defined( 'ABSPATH' ) || exit;
 
/**
 * Activation and deactivation hooks for WordPress
 */
function bidii_pay_extension_activate() {
    // Your activation logic goes here.
}
register_activation_hook( __FILE__, 'bidii_pay_extension_activate' );
 
function bidii_pay_extension_deactivate() {
    // Your deactivation logic goes here.
 
    // Don't forget to:
    // Remove Scheduled Actions
    // Remove Notes in the Admin Inbox
    // Remove Admin Tasks
}
register_deactivation_hook( __FILE__, 'bidii_pay_extension_deactivate' );
 
 
if ( ! class_exists( 'Bidii_Pay_Custom_Gateway' ) ) :
    /**
     * My Extension core class
     */
    class Bidii_Pay_Custom_Gateway {
 
        /**
         * The single instance of the class.
         */
        protected static $_instance = null;
 
        /**
         * Constructor.
         */
        protected function __construct() {
            $this->init();
        }
 
        /**
         * Main Extension Instance.
         */
        public static function instance() {
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }
 
        /**
         * Cloning is forbidden.
         */
        public function __clone() {
            // Override this PHP function to prevent unwanted copies of your instance.
            //   Implement your own error or use `wc_doing_it_wrong()`
        }
 
        /**
         * Unserializing instances of this class is forbidden.
         */
        public function __wakeup() {
            // Override this PHP function to prevent unwanted copies of your instance.
            //   Implement your own error or use `wc_doing_it_wrong()`
        }
 
        /**
        * Function for loading dependencies.
        */
        // private function includes() {
        //     $loader = include_once dirname( __FILE__ ) . '/' . 'vendor/autoload.php';
 
        //     if ( ! $loader ) {
        //         throw new Exception( 'vendor/autoload.php missing please run `composer install`' );
        //     }
 
        //     require_once dirname( __FILE__ ) . '/' . 'includes/my-extension-functions.php';
        // }
 
        /**
         * Function for getting everything set up and ready to run.
         */
        private function init() {
 
            // Examples include: 
 
            // Set up cache management.
            // new Bidii_Pay_Custom_Gateway_Cache();
 
            // Initialize REST API.
            // new Bidii_Pay_Custom_Gateway_REST_API();
 
            // Set up email management.
            // new Bidii_Pay_Custom_Gateway_Email_Manager();
 
            // Register with some-action hook
            // add_action('some-action', 'my-extension-function');
        }
    }
endif;
 
/**
 * Function for delaying initialization of the extension until after WooComerce is loaded.
 */
function bidii_pay_custom_gateway_initialize() {
 
    // This is also a great place to check for the existence of the WooCommerce class
    if ( ! class_exists( 'WooCommerce' ) ) {
    // You can handle this situation in a variety of ways,
    //   but adding a WordPress admin notice is often a good tactic.
        return;
    }
 
    $GLOBALS['bidii_pay_custom_gateway'] = Bidii_Pay_Custom_Gateway::instance();
}
add_action( 'plugins_loaded', 'bidii_pay_custom_gateway_initialize');