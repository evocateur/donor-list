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

/*  Copyright 2008  Daniel Stockman  (email : PLUGIN AUTHOR EMAIL)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if (!class_exists('CharityDonorList')) {
    class CharityDonorList	{
		
		/**
		* @var string   The name of the database table used by the plugin
		*/	
		var $db_table_name = '';

		
		/**
		* PHP 4 Compatible Constructor
		*/
		function CharityDonorList(){$this->__construct();}
		
		/**
		* PHP 5 Constructor
		*/		
		function __construct(){
			global $wpdb;


		add_action("admin_menu", array(&$this,"add_admin_pages"));
		register_activation_hook(__FILE__,array(&$this,"install_on_activation"));
		add_action("plugins_loaded",array(&$this,"register_widget_charity_donor_list_Donor List"));
		add_action("wp_head", array(&$this,"add_css"));

		
		/*
		* Register the shortcode
		*/
		if ( function_exists( 'add_shortcode' ) ) {
			add_shortcode('donor_list', array( &$this , 'donor_list_shortcode_handler' ) );
		}
		
			$this->db_table_name = $wpdb->prefix . "charity_donor_list";

		}
		

		function add_admin_pages(){
				add_menu_page('Donors', 'Donors', 10, __FILE__, array(&$this,"output_main_admin_page"));
		add_submenu_page(__FILE__, "Donors_1", "Donors_1", 10, "Donors_1", array(&$this,"output_sub_admin_page_1"));
		}
		
		/**
		* Outputs the HTML for the admin page.
		*/
		function output_main_admin_page(){
			?>
			<div class="wrap">
				<h2>Admin Menu Placeholder for Donors</h2>
				<p>You can modify the content that is output to this page by modifying the method: <strong>output_main_admin_page</strong></p>
			</div>
			<?php
		}
		
		/**
		* Outputs the HTML for the admin sub page.
		*/
		function output_sub_admin_page_1(){
			?>
			<div class="wrap">
				<h2>Admin Menu Placeholder for Donors_1 a subpage of Donors</h2>
				<p>You can modify the content that is output to this page by modifying the method <strong>output_sub_admin_page_1</strong></p>
			</div>
			<?php
		} 
		
		/**
		* donor_list_shortcode_handler - produces and returns the content to replace the shortcode tag
		*
		* @param array $atts  An array of attributes passed from the shortcode
		* @param string $content   If the shortcode wraps round some html, this will be passed.
		*/
		function donor_list_shortcode_handler( $atts , $content = null) {
			//add the attributes you want to accept to the array below
			$attributes = shortcode_atts(array(
		      'attr_1' => 'attribute 1 default',
		      'attr_2' => 'attribute 2 default',
		      // ...etc
			), $atts);
		
			//create the content you want to replace the shortcode in the post, here.
		
			//return the content. DO NOT USE ECHO.
			return 'default return value from donor_list';
		}
		
		
		/**
		* Creates or updates the database table, and adds a database table version number to the WordPress options.
		*/
		function install_on_activation() {
			global $wpdb;
			$plugin_db_version = "0.1";
			$installed_ver = get_option( "charity_donor_list_db_version" );
			//only run installation if not installed or if previous version installed
			if ($installed_ver === false || $installed_ver != $plugin_db_version) {
		
				//*****************************************************************************************
				// Create the sql - You will need to edit this to include the columns you need
				// Using the dbdelta function to allow the table to be updated if this is an update.
				// Read the limitations of the dbdelta function here: http://codex.wordpress.org/Creating_Tables_with_Plugins
				// remember to update the version number every time you want to make a change.
				//*****************************************************************************************
				$sql = "CREATE TABLE " . $this->db_table_name . " (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				first_name VARCHAR(100),
				last_name VARCHAR(100),
				city VARCHAR(100),
				state CHAR(2),
				email VARCHAR(100),
				UNIQUE KEY id (id)
				);";
			
				require_once(ABSPATH . "wp-admin/upgrade-functions.php");
				dbDelta($sql);
				//add a database version number for future upgrade purposes
				update_option("charity_donor_list_db_version", $plugin_db_version);
			}
		}
		
		/**
		* Registers the widget for use
		*/
		function register_widget_charity_donor_list_Donor List($args) {
			register_sidebar_widget("Donor List",array(&$this,"widget_charity_donor_list_Donor List"));
		}
		
		
		/**
		* Contains the widget logic
		*/
		function widget_charity_donor_list_Donor List($args) {
			extract($args);
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
		function add_css(){
		echo '<link rel="stylesheet" href="'.get_bloginfo('wpurl').'/wp-content/plugins/charity_donor_list/style.css" type="text/css" media="screen"  />'; 
		}
		

    }
}

//instantiate the class
if (class_exists('CharityDonorList')) {
	$CharityDonorList = new CharityDonorList();
}




?>