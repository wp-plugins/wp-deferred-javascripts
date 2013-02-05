<?php
/*
Plugin Name: WP deferred javaScript
Plugin URI: http://wabeo.fr/blog/wordpress-javascripts-asynchrones/
Description: This plugin defer the loading of all javascripts added by the way of wp_enqueue_scripts, using LABJS.
Version:1.4
Author: Willy Bahuaud, Daniel Roch
Author URI: http://wabeo.fr
*/
/**
INITIALIZATION OF OUR GLOBAL VARIABLE
* @global ARRAY $all_our_scripts 
*/
function initialize_script_global() {
	global $all_our_scripts;
	$all_our_scripts = array();
}
add_action( 'init', 'initialize_script_global' );
/**
PUSH SCRIPTS INTO OUR GLOBAL & EMPTYING $WP_SCRIPTS
* @since 1.4 {filter scripts are now hooked on wp_print_scripts}
* @since 1.2 {datas are queued into cross_the_steams()}
* @uses FILTER all_our_scripts_datas
*/
function you_shall_not_pass() {
	global $all_our_scripts, $wp_scripts;
	foreach( $wp_scripts->queue as $s ) {
		$all_our_scripts[ $wp_scripts->registered[ $s ]->handle ] = array(
			'src'   => $wp_scripts->registered[ $s ]->src, 
			'deps'  => $wp_scripts->registered[ $s ]->deps
			);
		apply_filters( 'all_our_scripts_datas', $all_our_scripts );
	}
	$wp_scripts->queue = array();
}
add_action( 'wp_print_scripts', 'you_shall_not_pass', 9 );

/**
FINAL REPORT OF SCRIPTS
* @var ARRAY $all_our_ordered_scripts
* @var ARRAY $undead
* @var INT $alldepscounter
* @var ARRAY $waited_scripts
* @uses render_our_scripts_now() {{to render the scripts}}
*/
function cross_the_steams() {
	global $all_our_scripts, $wp_scripts;

	$i                       = count( $all_our_scripts );
	
	$all_our_ordered_scripts = array();
	$undead                  = $all_our_scripts;
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
							$all_our_scripts[ $wp_scripts->registered[ $d ]->handle ] = array(
								'src'   => $wp_scripts->registered[ $d ]->src,
								'deps'  => $wp_scripts->registered[ $d ]->deps, 
								'extra' => $wp_scripts->registered[ $d ]->extra);
							$undead[ $wp_scripts->registered[ $d ]->handle ] = $all_our_scripts[ $wp_scripts->registered[ $d ]->handle ];
						}else{
							unset( $all_our_scripts[ $k ] );
							$i--;
							break; //go back into darkness demonic creature ...
						}
					}else{
						if( array_key_exists( $d, $all_our_ordered_scripts ) ) { // one more satisfied condition
							$alldepscounter++;
						}
					}
				}
				if( $alldepscounter == count( $s['deps'] ) ) { //YEAH, all conditions are satisfied now !!
					// I have to wait ?
					$diff = array_diff(  $s['deps'], $waited_scripts );
					if(  ! empty ( $diff ) ) {
						$s['wait'] = true;
						$waited_scripts =  array_merge( (array) $waited_scripts,  (array) $s['deps'] );
					}
					$s['extra'] = $wp_scripts->registered[ $k ]->extra; //joining datas
					$all_our_ordered_scripts[ $k ] = $s;
					unset( $all_our_scripts[ $k ] );
					$i--;
				}
			}
		}
	}
	render_our_scripts_now( $all_our_ordered_scripts ); //print scripts
}
add_action( 'wp_footer', 'cross_the_steams', 99 );

/**
FUNCTION USED FOR RENDERING SCRIPTS
* @since 1.3 {{when it's need, we wait while queuing scripts}}
* @since 1.3 {{datas are inclued before scripts}}
* @var VARCHAR $output
*/
function render_our_scripts_now( $all_our_ordered_scripts ) {
	if( !empty( $all_our_ordered_scripts ) ) {
		$output  = '<script src="'.plugin_dir_url( __FILE__ ).'j/lab.min.js"></script>'."\r\n";
		$output .= '<script>'."\r\n";
		//one loop for datas
		foreach($all_our_ordered_scripts as $s) {
			if ( isset( $s['extra']['data'] ) )
				$output .= $s['extra']['data']."\r\n";
		}
		//another to print scripts
		$output .= '$LAB';
		foreach($all_our_ordered_scripts as $s) {
			$src	= ( preg_match( '/^\/[^\/]/', $s['src'] ) ) ? get_bloginfo( 'wpurl' ).$s['src'] : $s['src'] ;
			if( isset ( $s['wait'] ) )
				$output .= '.wait()';
			$output .= '.script("'.$src.'")';
		}
		$output .= ';'."\r\n".'</script>';
		echo $output;
	}
}