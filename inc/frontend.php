<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/*-----------------------------------------------------------------------------------*/
/* !TOOLS. ========================================================================= */
/*-----------------------------------------------------------------------------------*/

// !Store/get some data.

if ( ! function_exists('sf_cache_data') ):
function sf_cache_data( $key, $data = 'trolilol' ) {
	static $datas = array();
	if ( $data !== 'trolilol' ) {
		$datas[ $key ] = $data;
	}
	return isset( $datas[ $key ] ) ? $datas[ $key ] : null;
}
endif;


// !Recursive function to find all dependencies, given an array of JS handles.

function sfdjs_get_all_deps( $scripts ) {
	global $wp_scripts;
	$out = array();

	if ( is_array( $scripts ) && ! empty( $scripts ) ) {
		foreach ( $scripts as $handle ) {
			if ( ! empty( $wp_scripts->registered[ $handle ]->deps ) ) {
				$deps = array_filter( (array) $wp_scripts->registered[ $handle ]->deps );
				if ( ! empty( $deps ) ) {
					$out = array_merge( $out, sfdjs_get_all_deps( $deps ) );
				}
			}

			if ( ! empty( $wp_scripts->registered[ $handle ]->src ) ) {
				$out[ $handle ] = $handle;
			}
		}
	}

	return $out;
}


// !Recursive function to tell if a JS handle is a dependency.

function sfdjs_script_is_dependency( $my_script, $scripts ) {
	global $wp_scripts;

	if ( is_array( $scripts ) && ! empty( $scripts ) ) {
		foreach ( $scripts as $handle ) {
			if ( ! empty( $wp_scripts->registered[ $handle ]->deps ) ) {
				$deps = array_filter( (array) $wp_scripts->registered[ $handle ]->deps );

				if ( ! empty( $deps ) && ( in_array( $my_script, $deps ) || sfdjs_script_is_dependency( $my_script, $deps ) ) ) {
					return true;
				}
			}
		}
	}

	return false;
}


/*-----------------------------------------------------------------------------------*/
/* !STORE SOME DATA. =============================================================== */
/*-----------------------------------------------------------------------------------*/

// !Store an array containing all scripts that should not be deferred, including their dependencies.
// !Store an array containing the data for the deferred scripts (those data are normally printed BEFORE the filter <code>'script_loader_src'</code>, this is why I grab them here).

add_filter( 'print_scripts_array', 'sfdjs_store_do_not_defer_deps' );

function sfdjs_store_do_not_defer_deps( $to_do ) {
	global $wp_scripts;
	$do_not_defer = (array) apply_filters( 'do_not_defer', array() );
	$do_not_defer = array_filter( (array) $do_not_defer );

	if ( ! empty( $do_not_defer ) ) {
		$do_not_defer = sfdjs_get_all_deps( $do_not_defer );
	}

	$datas    = sf_cache_data( 'sfdjs_deferred_datas' );
	$datas    = is_array( $datas ) ? $datas : array();
	$deferred = $wp_scripts->queue;
	if ( ! empty( $do_not_defer ) ) {
		$deferred = array_diff( $deferred, $do_not_defer );
	}
	if ( ! empty( $deferred ) ) {
		foreach ( $deferred as $handle ) {
			if ( ! isset( $datas[ $handle ] ) && ! empty( $wp_scripts->registered[ $handle ]->extra['data'] ) ) {
				$datas[ $handle ] = $wp_scripts->registered[ $handle ]->extra['data'];
				unset( $wp_scripts->registered[ $handle ]->extra['data'] );
			}
		}
	}

	sf_cache_data( 'sfdjs_do_not_defer', $do_not_defer );
	sf_cache_data( 'sfdjs_deferred_datas', $datas );

	return $to_do;
}


// !Store an array containing all scripts that should be deferred.

add_filter( 'script_loader_src', 'sfdjs_store_deferred_scripts', PHP_INT_MAX, 2 );

function sfdjs_store_deferred_scripts( $src, $handle ) {
	$do_not_defer = sf_cache_data( 'sfdjs_do_not_defer' );

	if ( ! isset( $do_not_defer[ $handle ] ) ) {
		$deferred = sf_cache_data( 'sfdjs_deferred' );
		$deferred = is_array( $deferred ) ? $deferred : array();
		$deferred[ $handle ] = $handle;
		sf_cache_data( 'sfdjs_deferred', $deferred );
		return false;
	}

	return $src;
}


/*-----------------------------------------------------------------------------------*/
/* !PRINT OUR SCRIPT. ============================================================== */
/*-----------------------------------------------------------------------------------*/

add_action( 'wp_footer', 'sfdjs_render_scripts', PHP_INT_MAX );

function sfdjs_render_scripts() {
	global $wp_scripts, $wp_filter;
	$deferred = sf_cache_data( 'sfdjs_deferred' );
	$datas    = sf_cache_data( 'sfdjs_deferred_datas' );

	if ( ! empty( $deferred ) ) {
		$lab_ver   = '2.0.3';
		$lab_src   = SFDJS_PLUGIN_URL . 'assets/js/lab.min.js';
		// You also use my plugin SF Cache Busting, right? RIGHT?! ;)
		$lab_src   = function_exists( 'sfbc_build_src_for_cache_busting' ) ? sfbc_build_src_for_cache_busting( $lab_src, $lab_ver ) : $lab_src . '?ver=' . $lab_ver;
		$lab_src   = apply_filters( 'wdjs_labjs_src', $lab_src, $lab_ver );

		$start_tag = '<script' . ( apply_filters( 'wdjs_use_html5', false ) ? '' : ' type=\'text/javascript\'' ) . ">/* <![CDATA[ */\n";
		$end_tag   = "\n/* ]]> */</script>";
		$last_cond = null;

		$output    = '';

		// Data
		if ( ! empty( $datas ) ) {

			foreach ( $datas as $handle => $data ) {
				$condition = $wp_scripts->get_data( $handle, 'conditional' );

				// Not a conditionnal script.
				if ( ! $condition ) {
					if ( is_null( $last_cond ) ) {
						$output .= $start_tag;
					}
					elseif ( $last_cond ) {
						$output .= "$end_tag<![endif]-->\n$start_tag";
					}
					// if $last_cond === false, do nothing.
				}
				// Conditionnal script.
				else {
					$condition = trim( $condition );
					if ( is_null( $last_cond ) ) {
						$output .= "<!--[if $condition]>$start_tag";
					}
					elseif ( ! $last_cond ) {
						$output .= "$end_tag\n<!--[if $condition]>$start_tag";
					}
					elseif ( $last_cond !== $condition ) {
						$output .= "$end_tag<![endif]-->\n<!--[if $condition]>$start_tag";
					}
					// if $last_cond === $condition, do nothing.
				}

				$last_cond = $condition;
				$output .= $data;
			}

		}

		// Scripts
		if ( is_null( $last_cond ) ) {
			$output .= $start_tag;
		}
		elseif ( $last_cond ) {
			$output .= "$end_tag<![endif]-->\n$start_tag";
		}
		else {
			$output .= "\n";
		}

		$output .= '(function(g,b,d){var c=b.head||b.getElementsByTagName("head"),D="readyState",E="onreadystatechange",F="DOMContentLoaded",G="addEventListener",H=setTimeout;function f(){';
		$output .= '$LAB';

		foreach ( $deferred as $handle ) {
			$src = $wp_scripts->registered[ $handle ]->src;
			if ( ! preg_match( '|^(https?:)?//|', $src ) && ! ( $wp_scripts->content_url && 0 === strpos( $src, $wp_scripts->content_url ) ) ) {
				$src = $wp_scripts->base_url . $src;
			}
			$src = esc_url( $src );

			$output .= '.script(';

			// Handle scripts for IE.
			if ( $condition = $wp_scripts->get_data( $handle, 'conditional' ) ) {
				$src_string  = 'function(){';
					$src_string .= 'var div = document.createElement("div");';
					$src_string .= 'div.innerHTML = "<!--[if ' . $condition . ']><i></i><![endif]-->";';
					$src_string .= 'return div.getElementsByTagName("i").length ? "' . $src . '" : null;';
				$src_string .= '}';
			}
			else {
				$src_string = '"' . $src . '"';
			}

			$output .= apply_filters( 'wdjs_deferred_script_src', $src_string, $handle, $src );
			$output .= ')';

			$wait = apply_filters( 'wdjs_deferred_script_wait', '', $handle );
			if ( $wait || sfdjs_script_is_dependency( $handle, $deferred ) ) {
				$output .= '.wait(' . $wait . ')';
			}
		}

		$output .= apply_filters( 'wdjs_before_end_lab', '' );

		$output .= ';}H(function(){if("item"in c){if(!c[0]){H(arguments.callee,25);return}c=c[0]}var a=b.createElement("script"),e=false;a.onload=a[E]=function(){if((a[D]&&a[D]!=="complete"&&a[D]!=="loaded")||e){return false}a.onload=a[E]=null;e=true;f()};a.src="' . $lab_src . '";c.insertBefore(a,c.firstChild)},0);if(b[D]==null&&b[G]){b[D]="loading";b[G](F,d=function(){b.removeEventListener(F,d,false);b[D]="complete"},false)}})(this,document);';
		$output .= $end_tag;

		echo $output;
	}
}

/**/