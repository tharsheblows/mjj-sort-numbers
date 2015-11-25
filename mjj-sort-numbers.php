<?php
/**
 * MJJ Sort Numbers
 *
 * A table and API to sort objects based on their numerical values.
 *
 * @package   MJJ_Sort_Numbers
 * @author    JJ Jay <jjjay@mac.com>
 * @license   GPL-2.0+
 * @copyright 2015 JJ Jay
 *
 * @wordpress-plugin
 * Plugin Name: MJJ Sort Numbers
 * Plugin URI:  http://github.com/tharsheblow/??
 * Description: A table and API to sort objects based on their numerical values.
 * Version:     0.0.1
 * Author:      JJ Jay
 * Text Domain: mjj-sort-numbers-locale
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /lang
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
} // end if

require_once( plugin_dir_path( __FILE__ ) . 'class-mjj-sort-numbers.php' );
require_once( plugin_dir_path( __FILE__ ) . 'class-mjj-sort-numbers-admin.php' );

MJJ_Sort_Numbers_Admin::get_instance();
MJJ_Sort_Numbers::get_instance();

