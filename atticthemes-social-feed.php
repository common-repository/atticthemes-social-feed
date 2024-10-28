<?php
/*
Plugin Name: AtticThemes: Social Feed
Plugin URI: http://atticthemes.com
Description: This plugin allows you to display your recent post form Instagram and Dribbble. No lengthy setups nor there is a need for you to fiddle around with complicated settings. Connect with the social platform you want with just couple of clicks, and you're done.
Version: 1.0.1
Author: atticthemes
Author URI: http://themeforest.net/user/atticthemes
Requires: 4.0.0
Tested: 4.6.1
Updated: 2016-06-13
Added: 2016-06-13
*/



if( !class_exists('AtticThemes_SocialFeed') ) {

	class AtticThemes_SocialFeed {
		/**
		 * Plugin version
		 */
		const VERSION = '1.0.1';

		/**
		 * Defines if the plugin is in development stage or not
		 */
		const IS_DEV = false;

		/**
		 * suffix to append to the filename of resources
		 */
		const MIN_SUFFIX = '.min';


		/**
		 * Current file
		 */
		const FILE = __FILE__;


		const USER_AGENT = 'AT-SF';
		const TIMEOUT = 30;



		public static function init() {
			
		}


		public static function request( $args ) {
			$args = array_merge( array(
				'method' => 'GET',
				'url' => '',
				'body' => ''
			), $args);

			$response = wp_safe_remote_post( $args['url'], array(
					'method' => $args['method'],
					'timeout' => self::TIMEOUT,
					'httpversion' => '1.1',
					'headers' => array(
						'User-Agent' => self::USER_AGENT,
					),
					'body' => $args['body'],
				)
			);

			return $response;
		}


		public static function add_admin_resources() {
			wp_register_style(
				'atsf-admin',
				plugins_url( 'resources/css/admin'. self::min() .'.css', self::FILE ),
				array(),
				self::VERSION
			);
			/**
			 * add admin style
			 */
			wp_enqueue_style( 'atsf-admin' );
		}


		public static function add_resources() {
			wp_register_style(
				'atsf-style',
				plugins_url( 'resources/css/style'. self::min() .'.css', self::FILE ),
				array(),
				self::VERSION
			);
			/**
			 * add main style
			 */
			wp_enqueue_style( 'atsf-style' );
		}


		/**
		 * Returns the min suffix if not in development
		 */
		public static function min() {
			if( self::IS_DEV ) {
				return '';
			} else {
				return self::MIN_SUFFIX;
			}
		}
	} //end class



	/** 
	 * Init the plugin
	 */
	add_action( 'init', array('AtticThemes_SocialFeed', 'init') );

	/**
	 * Add admin style
	 */
	add_action( 'admin_enqueue_scripts', array('AtticThemes_SocialFeed', 'add_admin_resources') );

	/**
	 * Add front-end style
	 */
	add_action( 'wp_enqueue_scripts', array('AtticThemes_SocialFeed', 'add_resources') );


	/**
	 * Add required classes
	 */
	require_once( plugin_dir_path( __FILE__ ) . 'includes/instagram/class.php' );
	require_once( plugin_dir_path( __FILE__ ) . 'includes/dribbble/class.php' );
}



