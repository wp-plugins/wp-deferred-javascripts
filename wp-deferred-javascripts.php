<?php
/*
Plugin Name: WP deferred javaScript
Plugin URI: http://www.wabeo.fr
Description: This plugin defer the loading of all javascripts added by the way of wp_enqueue_scripts, using LABJS.
Version:1.0
Author: Willy Bahuaud, confridin
Author URI: http://wabeo.fr
*/
/**
INITIALIZATION VARIABLE GLOBALE
* @var delayed_script ARRAY 
*/
add_action('init','initialize_script_global');
function initialize_script_global(){
	global $delayed_script;
	$delayed_script = array();
}

/**
POUSSAGE DES SCRIPTS DANS LA GLOBALE & VIDAGE DE $WP_SCRIPTS
*/
add_action('wp_head','stop',1);
add_action('wp_footer','stop',1);
function stop(){
	global $delayed_script,$wp_scripts;
	foreach($wp_scripts->queue as $s){
		$delayed_script[$wp_scripts->registered[$s]->handle] = array(
			'src'   => $wp_scripts->registered[$s]->src, 
			'deps'  => $wp_scripts->registered[$s]->deps, 
			'extra' =>$wp_scripts->registered[$s]->extra
			);
		apply_filters('delayed_script_datas', $delayed_script);
	}
	$wp_scripts->queue = array();
}

/**
RENDU FINAL DES SCRIPTS
*/
add_action('wp_footer','afficher_moi_tout_ca',99);
function afficher_moi_tout_ca(){
	global $delayed_script,$wp_scripts;

	//WHILE
	//i = nombr de script
	//a chaque fois qu'un script est ajoute, on desiterre i
	//les scripts sans dependance sont jetés
	//les scripts avec depandances sont remis au lendemain
	//lorsque i = 0 -> on sort
	$i = count($delayed_script);

	$starscriptethutch = array();
	$mortvivant = $delayed_script;

	while($i>0){
		foreach($delayed_script as $k => $s){
			if(empty($s['deps'])){
				$starscriptethutch[$k] = $s;
				unset($delayed_script[$k]);
				$i--;
			}else{
				$alldepscounter = 0; //nombre de conditions remplies
				foreach($s['deps'] as $d){
					if(!array_key_exists($d,$mortvivant)){ //je n'aurais jamais l'indépendance...
						if(isset($wp_scripts->registered[$d])){ //... à moins que quelqu'un puisse me sauver
							$delayed_script[$wp_scripts->registered[$d]->handle] = array(
								'src'   => $wp_scripts->registered[$d]->src,
								'deps'  => $wp_scripts->registered[$d]->deps, 
								'extra' =>$wp_scripts->registered[$d]->extra);
							$mortvivant[$wp_scripts->registered[$d]->handle] = $delayed_script[$wp_scripts->registered[$d]->handle];
						}else{
							unset($delayed_script[$k]);
							$i--;
							break; //retourne dans ton monde, créature démoniaque...
						}
					}else{
						if(array_key_exists($d,$starscriptethutch)){ // je rempli une condition en +
							$alldepscounter++;
						}
					}
				}
				if($alldepscounter == count($s['deps'])){ //bravo, toutes les conditions sont remplies
					$starscriptethutch[$k] = $s;
					unset($delayed_script[$k]);
					$i--;
				}
			}
		}
	}
	$delayed_script = $starscriptethutch; //le même, mais dans l'ordre ^^

	rendez_moi_mes_scripts($delayed_script); //on écrit les scripts
}

/**
FONCTION UTILISÉE POUR LE RENDU
*/
function rendez_moi_mes_scripts($delayed_script){
	if(!empty($delayed_script)){
		$output = '<script src="'.plugin_dir_url(__FILE__).'j/lab.min.js"></script>'."\n";
		$output.= '<script>';
		foreach($delayed_script as $s)
			$output.= $s['extra']['data'].'$LAB.script("'.$s['src'].'");'."\n";
		$output.= '</script>';
		echo $output;
	}
}