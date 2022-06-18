<?php
/**
 * Plugin Name:       Bidii Pay 
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
 */

 
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

include('bidii-pay-custom-gateway.php');

