<?php
/*
Plugin Name: Charity Donor List
Plugin URI: [insert the plugin uri here]
Description: A list of donors
Author: Daniel Stockman
Version: 0.1
Author URI: http://evocateur.org/
Generated At: www.wp-fun.co.uk;
*/ 

/*  Copyright 2008  Daniel Stockman <daniel.stockman@gmail.com>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
*/

if ( !class_exists( 'CharityDonorList' ) ):
class CharityDonorList {

	var $db_table_name = '';

	/**
	* Constructor
	*/
	function CharityDonorList() { $this->__construct(); }

	function __construct() {
		global $wpdb;

		register_activation_hook( __FILE__, array( &$this, "install" ) );

		add_action( "admin_menu", array( &$this, "add_admin_page" ) );
		add_action( "plugins_loaded", array( &$this, "register_widget" ) );
		add_action( "wp_head", array( &$this, "add_css" ) );

		/*
		* Register the shortcode
		*/
		if ( function_exists( 'add_shortcode' ) ) {
			add_shortcode('donor_list', array( &$this , 'shortcode' ) );
		}

		$this->db_table_name = $wpdb->prefix . "charity_donor_list";

		$this->db_field_prep = array(
			'first_name' => 's'
			,'last_name' => 's'
			,'city'  => 's'
			,'state' => 's'
			,'email' => 's'
		);
	}

	function add_admin_page() {
		add_menu_page( 'Donor List', 'Donors', 10, __FILE__, array( &$this, "admin_page" ) );
	}

	/**
	* Outputs the HTML for the admin page.
	*/
	function admin_page() {
		?>
		<div class="wrap">
			<h2>Admin Menu Placeholder for Donors</h2>
			<p>You can modify the content that is output to this page by modifying the method: <strong>admin_page</strong></p>
		</div>
		<?php
	}

	/**
	* shortcode - produces and returns the content to replace the shortcode tag
	*
	* @param array 	$atts		An array of attributes passed from the shortcode
	* @param string	$content	If the shortcode wraps round some html, this will be passed.
	*/
	function shortcode( $atts , $content = null ) {
		//add the attributes you want to accept to the array below
		$attributes = shortcode_atts( array(
	      'attr_1' => 'attribute 1 default',
	      'attr_2' => 'attribute 2 default',
	      // ...etc
		), $atts );

		//create the content you want to replace the shortcode in the post, here.

		return 'default return value from donor_list';
	}

	/**
	* Creates or updates the database table, and adds a database table version number to the WordPress options.
	*/
	function install() {
		global $wpdb;
		$plugin_db_version = "0.1";
		$installed_ver = get_option( "charity_donor_list_db_version" );
		// only run installation if not installed or if previous version installed
		if ( $installed_ver === false || $installed_ver != $plugin_db_version ) {

			//*************************************************************************************
			// Create the sql - You will need to edit this to include the columns you need
			// Using the dbdelta function to allow the table to be updated if this is an update.
			// Read the limitations of the dbdelta function here:
			//		http://codex.wordpress.org/Creating_Tables_with_Plugins
			// remember to update the version number every time you want to make a change.
			//*************************************************************************************
			$sql = "CREATE TABLE {$this->db_table_name} (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				first_name VARCHAR(100),
				last_name VARCHAR(100) NOT NULL,
				city VARCHAR(100),
				state CHAR(2),
				email VARCHAR(100),
				PRIMARY KEY  (id),
				KEY lname (last_name)
			);";

			require_once( ABSPATH . "wp-admin/upgrade-functions.php" );
			dbDelta( $sql );
			//add a database version number for future upgrade purposes
			update_option( "charity_donor_list_db_version", $plugin_db_version );
		}
	}

	/**
	* Registers the widget for use
	*/
	function register_widget( $args ) {
		register_sidebar_widget( "Donor List", array( &$this, "widget" ) );
	}

	/**
	* Contains the widget logic
	*/
	function widget( $args ) {
		extract( $args );
		?>
		<?php echo $before_widget; ?>
		<?php echo $before_title . "Donor List" . $after_title; ?>
		Hello, World!
		<?php echo $after_widget; ?>
		<?php
	}

	/**
	* Adds a link to the stylesheet to the header
	*/
	function add_css() {
		echo '<link rel="stylesheet" href="'.get_bloginfo('wpurl').'/wp-content/plugins/charity_donor_list/style.css" type="text/css" media="screen"  />'; 
	}


}
endif;

//instantiate the class
if ( class_exists( 'CharityDonorList' ) ) {
	$CharityDonorList = new CharityDonorList();
}

?>