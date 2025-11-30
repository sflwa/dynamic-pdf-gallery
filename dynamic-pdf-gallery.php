<?php
/**
 * Plugin Name: Dynamic PDF Gallery
 * Plugin URI: https://github.com/sflwa/dynamic-pdf-gallery/
 * Description: An Elementor widget to display a dynamic, multi-column gallery of PDF links from the WordPress Media Library, supporting popular folder plugins like FileBird and WP Media Folder.
 * Version: 2.1.0
 * Author: South Florida Web Advisors
 * Author URI:  https://sflwa.net
 * License: GPLv2 or later
 * Text Domain: dynamic-pdf-gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define( 'DPDFG_PATH', plugin_dir_path( __FILE__ ) );

// Include Core Files
require_once( DPDFG_PATH . 'inc/abstract-pdf-source.php' );
require_once( DPDFG_PATH . 'inc/source-manual.php' );
require_once( DPDFG_PATH . 'inc/source-filebird.php' );
require_once( DPDFG_PATH . 'inc/source-wpmf.php' );
require_once( DPDFG_PATH . 'inc/pdf-meta-fields.php' );

/**
 * Initialize the Elementor widget.
 */
function dpdfg_register_elementor_widget( $widgets_manager ) {
    // FIX: The widget class file is now required here, ensuring Elementor's environment is loaded.
    require_once( DPDFG_PATH . 'inc/class-dpdfg-elementor-widget.php' ); 
    $widgets_manager->register( new \DPDFG_Elementor_Widget() );
}
add_action( 'elementor/widgets/register', 'dpdfg_register_elementor_widget' );
