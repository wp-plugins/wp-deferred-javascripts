<?php
/*
 * Plugin Name: WP deferred javaScript
 * Plugin URI: http://www.screenfeed.fr
 * Description: This plugin defer the loading of all javascripts added by the way of wp_enqueue_scripts, using LABJS.
 * Version: 2.0.0
 * Author: Willy Bahuaud, Daniel Roch, Grégory Viguier
 * Author URI: http://wabeo.fr/wp-deferred-js-authors.html
 * License: GPLv3
 * License URI: http://www.screenfeed.fr/gpl-v3.txt
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}


define( 'SFDJS_VERSION',    '2.0' );
define( 'SFDJS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );


if ( ! defined( 'XMLRPC_REQUEST' ) && ! defined( 'DOING_CRON' ) && ! is_admin() ) {
    include( plugin_dir_path( __FILE__ ) . 'inc/frontend.php' );
}

add_filter( 'plugin_row_meta', 'sfdjs_plugin_row_meta', 10, 2 );
function sfdjs_plugin_row_meta( $plugin_meta, $plugin_file ) {
    if( plugin_basename( __FILE__ ) == $plugin_file ){
        $last = end( $plugin_meta );
        $plugin_meta = array_slice( $plugin_meta, 0, -2 );
        $a = array();
        $authors = array(
            array( 'name'=>'Willy Bahuaud', 'url'=>'http://wabeo.fr' ),
            array( 'name'=>'Grégory Viguier', 'url'=>'http://www.screenfeed.fr' ),
            array( 'name'=>'Daniel Roch', 'url'=>'http://www.seomix.fr' ),
        );
        foreach( $authors as $author )
            $a[] = '<a href="' . $author['url'] . '" title="' . esc_attr__( 'Visit author homepage' ) . '">' . $author['name'] . '</a>';
        $a = sprintf( __( 'By %s' ), wp_sprintf( '%l', $a ) );
        $plugin_meta[] = $a;
        $plugin_meta[] = $last;
    }
    return $plugin_meta;
}

/**/