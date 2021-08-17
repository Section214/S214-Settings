<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.linkedin.com/in/robcruiz/
 * @since      1.0.0
 *
 * @package    Amfence_Locations
 * @subpackage Amfence_Locations/admin
 */

/**
 * The admin-specific settings functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Amfence_Locations
 * @subpackage Amfence_Locations/admin
 * @author     Rob Ruiz <r.ruiz@americafence.com>
 */
class Amfence_Locations_Settings {

	function __construct(){}

	function amfence_locations_add_menu( $menu ) {
		$menu['type']       = 'submenu';                                    // Can be set to 'submenu' or 'menu'. Defaults to 'menu'.
		//$menu['parent']     = 'options-general.php';                        // If 'type' is set to 'submenu', defines the parent menu to place our menu under. Defaults to 'options-general.php'
		$menu['page_title'] = __( 'Your Plugin Settings', 'amfence-locations' );  // The page title. Defaults to 'Section214 Settings'.
		$menu['show_title'] = false;                                        // Whether or not to display the title at the top of the page.
		$menu['menu_title'] = __( 'AMFence Locations', 'amfence-locations' );           // The menu title. Defaults to 'Section214 Settings'.
		$menu['capability'] = 'manage_options';                             // The minimum capability required to access the settings panel. Defaults to 'manage_options'.
		$menu['icon']       = '';                                           // An (optional) icon for your menu item. Follows the same standards as the add_menu_item() function in WordPress.
		$menu['position']   = null;                                         // Where in the menu to display our new menu. Defaults to 'null' (bottom of the menu).

		return $menu;
	}

	function amfence_locations_settings_tabs( $tabs ) {
		$tabs['general'] = __( 'General', 'amfence-locations' );

		return $tabs;
	}

	function amfence_locations_settings_sections( $sections ) {
		$sections = array(
			'general' => array(
				'main' => __( 'General Settings', 'amfence-locations' )
			)
		);

		return $sections;
	}

	function amfence_locations_settings( $settings ) {
		$plugin_settings = array(
			'general' => array(
				'main' => array(
					array(
						'id'   => 'amfence_locations_mode',
						'name' => __( 'Locations Data Role', 'amfence-locations' ),
						'desc' => __( '<br />Master - if this WP site is the source of truth for all location information<br />Slave - if this site should refer to master for location info', 'amfence-locations' ),
						'type' => 'select',
						'options' => array(
							'master' => 'Master',
							'slave' => 'Slave'
						),
						'std' => 'slave'
					),
					array(
						'id'   => 'amfence_locations_master_url',
						'name' => __( 'Master Site URL', 'amfence-locations' ),
						'desc' => __( '<br /><strong><em>Only Required if Slave is selected above ^</em></strong><br />The website URL of the master site', 'amfence-locations' ),
						'type' => 'text',
					)
				)
			)
		);

		return array_merge( $settings, $plugin_settings );
	}

}
