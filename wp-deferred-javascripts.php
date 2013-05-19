<?php
/*
Plugin Name: WP deferred javaScript
Plugin URI: http://wabeo.fr/blog/wordpress-javascripts-asynchrones/
Description: This plugin defer the loading of all javascripts added by the way of wp_enqueue_scripts, using LABJS.
Version:1.5.6
Author: Willy Bahuaud, Daniel Roch
Author URI: http://wabeo.fr
*/
/**
INITIALIZATION OF OUR GLOBAL VARIABLE
* @global ARRAY $all_our_scripts 
*/
function wpdjs_initialize_script_global() {
	global $all_our_scripts;
	$all_our_scripts = array();
}
add_action( 'init', 'wpdjs_initialize_script_global' );
/**
PUSH SCRIPTS INTO OUR GLOBAL & EMPTYING $WP_SCRIPTS
* @since 1.5.6 {new filter for exlude scripts from defering}
* @since 1.5.6 {now push javascript version to prevent cache bad reload}
* @since 1.5.1 {we now excluded the function from login and register pages}
* @since 1.4 {filter scripts are now hooked on wp_print_scripts}
* @since 1.2 {datas are queued into cross_the_steams()}
* @uses FILTER all_our_scripts_datas
* @uses FILTER do_not_defer
*/
function you_shall_not_pass() {
	if( ! is_admin() && ! in_array( $GLOBALS[ 'pagenow' ], array( 'wp-login.php', 'wp-register.php' ) ) ) {
		global $all_our_scripts, $wp_scripts;
		foreach( $wp_scripts->queue as $k => $s ) {
			if( ! in_array( $s, apply_filters( 'do_not_defer', array() ) ) ){
				$ver = ( ! empty( $wp_scripts->registered[ $s ]->ver ) ) ? $wp_scripts->registered[ $s ]->ver : get_bloginfo( 'version' );
				$all_our_scripts[ $wp_scripts->registered[ $s ]->handle ] = array(
					'src'   => add_query_arg( array( 'v' => $ver ), esc_url( $wp_scripts->registered[ $s ]->src ) ),
					'deps'  => $wp_scripts->registered[ $s ]->deps,
					);
				apply_filters( 'all_our_scripts_datas', $all_our_scripts );
				unset( $wp_scripts->queue[ $k ] );
			}
		}
	}
}
add_action( 'wp_print_scripts', 'you_shall_not_pass', 9 );
add_action( 'wp_print_footer_scripts', 'you_shall_not_pass', 9 );

/**
FINAL REPORT OF SCRIPTS
* @var ARRAY $all_our_ordered_scripts
* @var ARRAY $undead
* @var INT $alldepscounter
* @var ARRAY $waited_scripts
* @uses render_our_scripts_now() {{to render the scripts}}
*/
function wpdjs_cross_the_steams() {
	global $all_our_scripts, $wp_scripts;

	$i                       = count( $all_our_scripts );
	$all_our_ordered_scripts = array();
	$script_normaly_enqueued = array_fill_keys( $wp_scripts->done, array() );
	$undead                  = array_merge( $all_our_scripts, $script_normaly_enqueued  );
	$waited_scripts			 = array();

	while( $i > 0 ) {
		foreach( $all_our_scripts as $k => $s ) {
			if( empty( $s['deps'] ) ){
				$s['extra'] = $wp_scripts->registered[ $k ]->extra; //joining datas
				$all_our_ordered_scripts[ $k ] = $s;
				unset( $all_our_scripts[ $k ] );
				$i--;
			}
			else{			
				$alldepscounter = 0; //number of satisfied conditions
				foreach( $s['deps'] as $d ){
					if( !array_key_exists( $d, $undead ) ) { //can I load me ?
						if( isset( $wp_scripts->registered[$d] ) ) { // yes you can
							$ver = ( ! empty( $wp_scripts->registered[ $d ]->ver ) ) ? $wp_scripts->registered[ $d ]->ver : get_bloginfo( 'version' );
							$all_our_scripts[ $wp_scripts->registered[ $d ]->handle ] = array(
								'src'   => add_query_arg( array('v' => $ver ), esc_url( $wp_scripts->registered[ $d ]->src ) ),
								'deps'  => $wp_scripts->registered[ $d ]->deps, 
								'extra' => $wp_scripts->registered[ $d ]->extra);
							$undead[ $wp_scripts->registered[ $d ]->handle ] = $all_our_scripts[ $wp_scripts->registered[ $d ]->handle ];
							//Dont forget to add one script to counter
							$i++;
						}else{
							unset( $all_our_scripts[ $k ] );
							$i--;
							break; //go back into darkness demonic creature ...
						}
					}else{
						if( array_key_exists( $d, $all_our_ordered_scripts ) || array_key_exists( $d, $script_normaly_enqueued ) ) { // one more satisfied condition
							$alldepscounter++;
						}
					}
				}
				if( $alldepscounter == count( $s['deps'] ) ) { //YEAH, all conditions are satisfied now !!
					// I have to wait ?
					$diff = array_diff(  $s[ 'deps' ], $waited_scripts );
					if(  ! empty ( $diff ) ) {
						$s[ 'wait' ] = true;
						$waited_scripts =  array_merge( (array) $waited_scripts,  array_keys( $all_our_ordered_scripts ) );
					}
					$s[ 'extra' ] = $wp_scripts->registered[ $k ]->extra; //joining datas
					$all_our_ordered_scripts[ $k ] = $s;
					unset( $all_our_scripts[ $k ] );
					$i--;
				}
			}
		}
	}
	wpdjs_render_our_scripts_now( $all_our_ordered_scripts ); //print scripts
}
add_action( 'wp_footer', 'wpdjs_cross_the_steams', 99 );

/**
FUNCTION USED FOR RENDERING SCRIPTS
* @since 1.5.6 {{Add an usefull hook to call callback js functions}}
* @since 1.3 {{when it's need, we wait while queuing scripts}}
* @since 1.3 {{datas are inclued before scripts}}
* @var VARCHAR $output
* @uses wdjs_before_end_lab FILTER HOOK {to add some script to LABJS (like a function callback...)}
*/
function wpdjs_render_our_scripts_now( $all_our_ordered_scripts ) {
	if( ! empty( $all_our_ordered_scripts ) ) {
		$output  = '<script src="' . plugin_dir_url( __FILE__ ) . 'j/lab.min.js"></script>' . "\r\n";
		$output .= '<script>' . "\r\n";
		//one loop for datas
		foreach($all_our_ordered_scripts as $s) {
			if ( isset( $s[ 'extra' ][ 'data' ] ) )
				$output .= $s[ 'extra' ][ 'data' ] . "\r\n";
		}
		//another to print scripts
		$output .= '$LAB';
		foreach($all_our_ordered_scripts as $s) {
			$src	= ( preg_match( '/^\/[^\/]/', $s[ 'src' ] ) ) ? get_bloginfo( 'wpurl' ) . $s[ 'src' ] : $s[ 'src' ] ;
			if( isset ( $s['wait'] ) )
				$output .= '.wait()';
			$output .= '.script("' . $src . '")';
		}
		$output .= apply_filters( 'wdjs_before_end_lab', '' );
		$output .= ';' . "\r\n" . '</script>';
		echo $output;
	}
}