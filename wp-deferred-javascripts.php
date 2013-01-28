<?php
/*
Plugin Name: WP deferred javaScript
Plugin URI: http://www.wabeo.fr
Description: This plugin defer the loading of all javascripts added by the way of wp_enqueue_scripts, using LABJS.
Version:1.1
Author: Willy Bahuaud, Daniel Roch
Author URI: http://wabeo.fr
*/
/**
INITIALIZATION OF OUR GLOBAL VARIABLE
* @var all_our_scripts ARRAY 
*/
function initialize_script_global() {
	global $all_our_scripts;
	$all_our_scripts = array();
}
add_action( 'init', 'initialize_script_global' );
/**
PUSH SCRIPTS INTO OUR GLOBAL & EMPTYING $WP_SCRIPTS
* @uses FILTER all_our_scripts_datas
*/
function you_shall_not_pass() {
	global $all_our_scripts, $wp_scripts;
	foreach( $wp_scripts->queue as $s ) {
		$all_our_scripts[$wp_scripts->registered[$s]->handle] = array(
			'src'   => $wp_scripts->registered[$s]->src, 
			'deps'  => $wp_scripts->registered[$s]->deps, 
			'extra' =>$wp_scripts->registered[$s]->extra
			);
		apply_filters( 'all_our_scripts_datas', $all_our_scripts );
	}
	$wp_scripts->queue = array();
}
add_action( 'wp_head', 'you_shall_not_pass', 9 );
add_action( 'wp_footer', 'you_shall_not_pass', 9 );

/**
FINAL REPORT OF SCRIPTS
*/
function cross_the_steams() {
	global $all_our_scripts, $wp_scripts;

	$i = count( $all_our_scripts );

	$all_our_ordered_scripts = array();
	$undead = $all_our_scripts;

	while( $i > 0 ) {
		foreach( $all_our_scripts as $k => $s ) {
			if( empty( $s['deps'] ) ){
				$all_our_ordered_scripts[$k] = $s;
				unset( $all_our_scripts[$k] );
				$i--;
			}else{
				$alldepscounter = 0; //number of satisfied conditions
				foreach( $s['deps'] as $d ){
					if( !array_key_exists( $d, $undead ) ) { //can I load me ?
						if( isset( $wp_scripts->registered[$d] ) ) { // yes you can
							$all_our_scripts[$wp_scripts->registered[$d]->handle] = array(
								'src'   => $wp_scripts->registered[$d]->src,
								'deps'  => $wp_scripts->registered[$d]->deps, 
								'extra' =>$wp_scripts->registered[$d]->extra);
							$undead[$wp_scripts->registered[$d]->handle] = $all_our_scripts[$wp_scripts->registered[$d]->handle];
						}else{
							unset( $all_our_scripts[$k] );
							$i--;
							break; //go back into darkness demonic creature ...
						}
					}else{
						if( array_key_exists( $d, $all_our_ordered_scripts ) ) // one more satisfied condition
							$alldepscounter++;
					}
				}
				if( $alldepscounter == count( $s['deps'] ) ) { //YEAH, all conditions are satisfied now !!
					$all_our_ordered_scripts[$k] = $s;
					unset( $all_our_scripts[$k] );
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
*/
function render_our_scripts_now( $all_our_scripts ) {
	if( !empty( $all_our_scripts ) ) {
		$output  = '<script src="'.plugin_dir_url( __FILE__ ).'j/lab.min.js"></script>'."\n";
		$output .= '<script>';
		foreach($all_our_scripts as $s)
			$output .= $s['extra']['data'].'$LAB.script("'.$s['src'].'");'."\n";
		$output .= '</script>';
		echo $output;
	}
}