<?php

/*
Plugin Name: ACF OpenStreetMap Field
Plugin URI: http://wordpress.org/
Description: Enter description here.
Author: Jörn Lund
Version: 0.0.2
Author URI:
License: GPL3

Text Domain: acf-open-street-map
Domain Path: /languages/
*/

/*  Copyright 2017  Jörn Lund

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

define('ACF_OPEN_STREET_MAP_DIR', dirname( __FILE__ ));

// check if class already exists
if( !class_exists('acf_plugin_open_street_map') ) :

class acf_plugin_open_street_map {

	private static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/*
	*  __construct
	*
	*  This function will setup the class functionality
	*
	*  @type	function
	*  @date	17/02/2016
	*  @since	1.0.0
	*
	*  @param	n/a
	*  @return	n/a
	*/

	private function __construct() {

		// vars
		$this->settings = array(
			'version'	=> '0.0.1',
			'url'		=> plugin_dir_url( __FILE__ ),
			'path'		=> plugin_dir_path( __FILE__ )
		);


		// set text domain
		// https://codex.wordpress.org/Function_Reference/load_plugin_textdomain
		load_plugin_textdomain( 'acf-open-street-map', false, plugin_basename( ACF_OPEN_STREET_MAP_DIR ) . '/languages' );


		// include field
		add_action('acf/include_field_types', 	array($this, 'include_field_types')); // v5
		add_action('acf/register_fields', 		array($this, 'include_field_types')); // v4

		if ( is_admin() ) {
			require_once ACF_OPEN_STREET_MAP_DIR . '/include/admin/settings.php';
		}
	}

	function get_osm_layers( ) {
		/*
		mapnik
		cyclemap C 	Cycle
		transportmap T	Transport
		hot H	Humantarian
		*/
		return array(
			'mapnik'		=> __('Standard','osm-layer','acf-open-street-map'),
			'cyclemap'		=> __('Cycle Map','osm-layer','acf-open-street-map'),
			'transportmap'	=> __('Transport map','osm-layer','acf-open-street-map'),
			'hot'			=> __('Humanitarian','osm-layer','acf-open-street-map'),
		);
	}

	/**
	 *	get providers and variants
	 *	omits proviers with unconfigured api credentials
	 *	as well as local-only map data
	 *
	 *	@return array(
	 *		'ProviderName' => array(
	 *			'ProviderName.VariantName'	=> 'VariantName'
	 *		)
	 *	)
	 */
	public function get_layer_providers( ) {

		$providers 			= array();
		$leaflet_providers	= $this->get_leaflet_providers( );//json_decode( file_get_contents( ACF_OPEN_STREET_MAP_DIR . '/etc/leaflet-providers.json'), true );
		//$access_tokens		= get_option( 'acf_osm_provider_tokens', array() );

		foreach ( $leaflet_providers as $provider => $provider_data ) {

			// drop boundless maps
			if ( isset($provider_data['options']['bounds'])) {
				continue;
			}
			// skip api credentials required
			foreach ( $provider_data['options'] as $option => $option_value ) {
				if ( is_string($option_value) && preg_match( '/^<([^>]+)>$/', $option_value ) ) {
					continue 2;
				}
			}

			$providers[$provider] = array();

			if ( isset( $provider_data[ 'variants' ] ) ) {
				foreach ( $provider_data[ 'variants' ] as $variant => $variant_data ) {
					if ( ! is_string( $variant_data ) && isset( $variant_data['options']['bounds'] ) ) {
						// no variants with bounds!
						continue;
					} else if ( is_string( $variant_data ) || isset( $variant_data['options']['variant'] ) ) {
						$providers[$provider][$provider . '.' . $variant] = $variant;
					} else {
						$providers[$provider][$provider] = $provider;
					}
				}
			} else {
				$providers[$provider][$provider] = $provider;
			}
		}

		return $providers;
	}

	/**
	 *	returns leaflet providers with configured access tokens
	 *	@return array
	 */
	public function get_leaflet_providers( ) {
		$leaflet_providers	= json_decode( file_get_contents( ACF_OPEN_STREET_MAP_DIR . '/etc/leaflet-providers.json'), true );
		$access_tokens		= get_option( 'acf_osm_provider_tokens', array() );
		return array_replace_recursive( $leaflet_providers, $access_tokens );
	}


	// private function deep_replace( $repl, $str ) {
	// 	if ( is_array( $repl ) ) {
	// 		foreach ( $repl as $key => $value ) {
	// 			if ( is_string( $value ) ) {
	// 				$str = str_replace('{'.$key.'}', $value, $str );
	// 			} else {
	// 				$str = $this->deep_replace( $value, $str );
	//
	// 			}
	// 		}
	// 	}
	// 	return $str;
	// }

	/**
	 *	Get places in provider config, where an access token should be entered.
	 */
	public function get_provider_token_options( ) {

		$providers		= json_decode( file_get_contents( ACF_OPEN_STREET_MAP_DIR . '/etc/leaflet-providers.json'), true );

		$token_options	= array();

		foreach ( $providers as $provider => $data ) {
			foreach( $data['options'] as $option => $value ) {
				if ( is_string($value) && ( 1 === preg_match( '/^<([^>]*)>$/imsU', $value, $matches ) ) ) { // '<insert your [some token] here>'

					if ( ! isset($token_options[ $provider ]['options'] ) ) {
						$token_options[ $provider ] = array( 'options' => array() );
					}
					$token_options[ $provider ]['options'][ $option ] = '';
				}
			}
		}
		return $token_options;
	}


	/*
	*  include_field_types
	*
	*  This function will include the field type class
	*
	*  @type	function
	*  @date	17/02/2016
	*  @since	1.0.0
	*
	*  @param	$version (int) major ACF version. Defaults to false
	*  @return	n/a
	*/

	function include_field_types( $version = false ) {

		// support empty $version
		if( !$version ) $version = 4;


		// include
		include_once( ACF_OPEN_STREET_MAP_DIR. '/include/fields/acf-open_street_map-v' . $version . '.php');

	}

}


// initialize
acf_plugin_open_street_map::instance();


// class_exists check
endif;
