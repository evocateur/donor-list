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
	var $states_array;

	/**
	* Constructor
	*/
	function CharityDonorList() { $this->__construct(); }

	function __construct() {
		global $wpdb;

		register_activation_hook( __FILE__, array( &$this, "install" ) );

		add_action( "admin_menu",       array( &$this, "add_admin_page"  ) );
		add_action( "widgets_init",     array( &$this, "register_widget" ) );
		add_action( "wp_print_scripts", array( &$this, "add_css" ) );

		/*
		* Register the shortcode
		*/
		if ( function_exists( 'add_shortcode' ) ) {
			add_shortcode('donor_list', array( &$this , 'shortcode' ) );
		}

		$this->db_table_name = $wpdb->prefix . "donor_list";
		$this->states_table  = $wpdb->prefix . "donor_states";
		$this->init_states_array();

		$this->db_field_type = array(
			'first_name' => '%s'
			,'last_name' => '%s'
			,'city'      => '%s'
			,'state'     => '%u'
			,'email'     => '%s'
		);
		// test data
		$this->db_test_data = array(
			'person' => array(
				'first_name' => 'Joe'
				,'last_name' => 'Donor'
				// ,'city'      => 'Topeka'
				// ,'state'     => 30
				,'email'     => 'test@kansas.com'
			),
			'business' => array(
				'first_name' => ""
				,'last_name' => "Joe's Auto Parts"
				,'city'      => "Kansas City"
				,'state'     => 20
				,'email'     => "biz@missouri.com"
			),
		);
	}

	function init_states_array() {
		$this->states_array = array(
			'AK' => 'Alaska',
			'AL' => 'Alabama',
			'AR' => 'Arkansas',
			'AZ' => 'Arizona',
			'CA' => 'California',
			'CO' => 'Colorado',
			'CT' => 'Connecticut',
			'DC' => 'District of Columbia',
			'DE' => 'Delaware',
			'FL' => 'Florida',
			'GA' => 'Georgia',
			'HI' => 'Hawaii',
			'IA' => 'Iowa',
			'ID' => 'Idaho',
			'IL' => 'Illinois',
			'IN' => 'Indiana',
			'KS' => 'Kansas',
			'KY' => 'Kentucky',
			'LA' => 'Louisiana',
			'MA' => 'Massachusetts',
			'MD' => 'Maryland',
			'ME' => 'Maine',
			'MI' => 'Michigan',
			'MN' => 'Minnesota',
			'MO' => 'Missouri',
			'MS' => 'Mississippi',
			'MT' => 'Montana',
			'NC' => 'North Carolina',
			'ND' => 'North Dakota',
			'NE' => 'Nebraska',
			'NH' => 'New Hampshire',
			'NJ' => 'New Jersey',
			'NM' => 'New Mexico',
			'NV' => 'Nevada',
			'NY' => 'New York',
			'OH' => 'Ohio',
			'OK' => 'Oklahoma',
			'OR' => 'Oregon',
			'PA' => 'Pennsylvania',
			'PR' => 'Puerto Rico',
			'RI' => 'Rhode Island',
			'SC' => 'South Carolina',
			'SD' => 'South Dakota',
			'TN' => 'Tennessee',
			'TX' => 'Texas',
			'UT' => 'Utah',
			'VA' => 'Virginia',
			'VT' => 'Vermont',
			'WA' => 'Washington',
			'WI' => 'Wisconsin',
			'WV' => 'West Virginia',
			'WY' => 'Wyoming'
		);
		$this->states_array_indices = array_keys( $this->states_array );
	}

	/**
	* Top-level admin page for editing the list
	*/
	function add_admin_page() {
		add_menu_page( 'Donor List', 'Donors', 10, __FILE__, array( &$this, "admin_page" ) );
	}

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
	* Create or update database table, database table version number -> WP option
	*/
	function install() {
		global $wpdb;
		$plugin_db_version = "0.1";
		$installed_ver = get_option( "donor_list_db_version" );
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
			update_option( "donor_list_db_version", $plugin_db_version );

			if ( $wpdb->get_var("SHOW TABLES LIKE '$this->states_table'") != $this->states_table ) {
				// join table for states because i <3 normalization
				$ssql = "CREATE TABLE {$this->states_table} (
					id mediumint(9) NOT NULL AUTO_INCREMENT,
					iso_code CHAR(2),
					name VARCHAR(50),
					PRIMARY KEY  (id),
					KEY iso (iso_code)
				);";
				dbDelta( $ssql );
				// insert data
				$states = $this->states_array();
				$values = array();
				foreach ($states as $iso => $name) {
					$values[] = "('$iso','$name')";
				}
				$values = implode( ",\n", $values );
				$insert = "INSERT INTO {$this->states_table} (`iso_code`,`name`) VALUES \n$values;";
			}
		}
	}

	/**
	* CRUD - { create, list (retrieve), update, delete } DB operations
	*/
	function insert_or_update_values( $posted ) {
		global $wpdb;
		$fieldtypes = $this->db_field_type;
		$statement = array();

		foreach ( $fieldtypes as $field => $type ) {
			$exists = ( array_key_exists( $field, $posted ) && trim( $posted[$field] ) );
			$statement[] = "$field = " . (( $exists ) ? $type : 'NULL');
			if ( ! $exists ) unset( $fieldtypes[ $field ] );
		}

		// string to be formatted, first argument to wpdb->prepare
		$values = array( implode( ",\n\t", $statement ) );

		// values for replacements, 1+nth args to wpdb->prepare
		foreach ( $fieldtypes as $key => $value ) {
			$values[] = trim( $posted[ $key ] );
		}

		return call_user_func_array( array( &$wpdb, 'prepare' ), $values );
	}

	function create( $posted ) {
		$values = $this->insert_or_update_values( $posted );
		$sql = "INSERT INTO {$this->db_table_name} SET\n\t$values;";
		return $sql;
	}

/*	function list() {
		
	}

	function update( $id ) {
		
	}

	function delete( $id ) {
		
	}
*/
	/**
	* Widget
	*/
	function register_widget() {
		$opts = array( 'classname' => 'donor-list', 'description' => 'A list of donors.' );
		wp_register_sidebar_widget( 'donor-list', 'Donor List', array( &$this, "widget" ), $opts );
	}

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