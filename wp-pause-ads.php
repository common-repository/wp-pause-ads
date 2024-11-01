<?php
/**
 * @package WP Pause Ads
 * @version 0.3
 */
/*
Plugin Name: wp_pause_ads
Plugin URI: http://dsampaolo.wordpress.com/plugins/wp-pause-ads/
Description: met à disposition un shortcode permettant d'inclure des publicités "ciblées"
Author: Didier Sampaolo
Version: 0.3
Author URI: http://dsampaolo.wordpress.com/
*/

class wp_pause_ads {
    function __construct() {
        add_action( 'init', array(&$this, 'register_types'));
        add_shortcode('wp_pause_ads', array(&$this, 'ad_display'));
    }
    
    function register_types() {
        register_taxonomy(
		'label',
		array('wp_pause_ad'),
		array(
			'label' => __( 'Libellé' ),
			'rewrite' => array( 'slug' => 'label' ),
                )
	);
        register_taxonomy(
		'format',
		array('wp_pause_ad'),
		array(
			'label' => __( 'Format' ),
			'rewrite' => array( 'slug' => 'format' ),
                )
	);
        
        register_post_type( 'wp_pause_ad',
            array(
                'labels' => array(
                        'name' => __( 'Wp Pause Ads' ),
                        'singular_name' => __( 'Wp Pause Ad' )
                ),
                'public' => true,
                'has_archive' => false,
                'rewrite' => array('slug' => 'ad'),
            )
        );
    }
    
    function ad_display($params) {
        if (!isset($this->ads)) {
            $this->register_ads();
        }
        
        if (isset($params['keyword']) && in_array($params['keyword'],$this->keywords)) {
            $keyword = $params['keyword'];
	} else {
            shuffle($this->keywords);
            $keyword = $this->keywords[0];
	}

	if (isset($params['format']) && in_array($params['format'],$this->formats)) {
            $format = $params['format'];
	} else {
            shuffle($this->formats);
            $format = $this->formats[0];
	}
	
	if (isset($this->ads[$keyword][$format])) {
            if ($format == "texte") {
                $ancre = isset($params['ancre']) ? $params['ancre'] : 'voir le site';
                $this->ads[$keyword][$format] = sprintf($this->ads[$keyword][$format],$ancre);
            }
            
            return $this->spin($this->ads[$keyword][$format]);
	}

	return '';
    }   

    function register_ads() {
        $this->ads = array();
        $this->formats = array();
        $this->keywords = array();
        
        global $wpdb;
        $querydetails = "SELECT wposts.*
                            FROM $wpdb->posts wposts
                            WHERE wposts.post_status = 'publish'
                            AND wposts.post_type = 'wp_pause_ad'
                            ORDER BY wposts.post_date DESC";

      $wp_pause_ads = $wpdb->get_results($querydetails, OBJECT);
      
      
      foreach($wp_pause_ads as $post) {
          setup_postdata($post);
          
          $wp_formats = wp_get_post_terms( $post->ID, 'format'); 
          foreach($wp_formats as $format) {
              $ad_format = $format->slug; 
              
              if (!in_array($ad_format, $this->formats)) {
                  $this->formats[] = $ad_format;
              }
          }

          $wp_keywords = wp_get_post_terms( $post->ID, 'label'); 
          foreach($wp_keywords as $kw) {
              $ad_keyword = $kw->slug;
              
              if (!in_array($ad_keyword, $this->keywords)) {
                  $this->keywords[] = $ad_keyword;
              }
          }
          
          $this->ads[$ad_keyword][$ad_format] = get_the_content();
      }
    }
	
    function spin($text) {
        if (!preg_match("/{/si", $text)) {
            return $this->corrige($text);
        } else {
            preg_match_all("/\{([^{}]*)\}/si", $text, $matches);
            $occur = count($matches[1]);
            for ($i = 0; $i < $occur; $i++) {
                $word_spinning = explode("|", $matches[1][$i]);
                shuffle($word_spinning);
                $text = str_replace($matches[0][$i], $word_spinning[0], $text);
            }
            return $this->spin($text);
        }
    }
}

$wpa = new wp_pause_ads();
