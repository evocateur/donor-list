<?php
/*
Plugin Name: Donor List
Plugin URI: [insert the plugin uri here]
Description: A list of donors; [donor-list] shortcode, widget, admin editable
Author: Daniel Stockman
Version: 0.3
Author URI: http://evocateur.org/
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

if ( !class_exists( 'DonorList' ) ):
class DonorList {

	var $db_table_name = '';
	var $plugin_url = '';
	var $states = array();

	/**
	* Constructor
	*/
	function DonorList() { $this->__construct(); }

	function __construct() {
		global $wpdb;

		$base = plugin_basename( dirname( __FILE__ ) );
		$this->plugin_url = get_option('siteurl') . "/wp-content/plugins/$base/";
		$this->nonce_key  = 'donor_list_edit';

		$admin_print_script = "admin_print_scripts-toplevel_page_$base/$base";

		register_activation_hook(   __FILE__, array( &$this, "install"   ) );
		register_deactivation_hook( __FILE__, array( &$this, "uninstall" ) );

		add_action( "admin_menu",       array( &$this, "add_admin_page"  ) );
		add_action( "widgets_init",     array( &$this, "register_widget" ) );

		add_action( "wp_print_scripts",    array( &$this, "add_css" ) );
		add_action( "$admin_print_script", array( &$this, "add_js"  ) );

		add_action( "wp_ajax_$this->nonce_key", array( &$this, 'admin_ajax' ) );

		if ( function_exists( 'add_shortcode' ) ) {
			add_shortcode('donor-list', array( &$this , 'shortcode' ) );
		}

		$this->db_version_key = "donor_list_db_version";
		$this->db_version = array(
			'plugin'    => '0.2',
			'installed' => get_option( $this->db_version_key )
		);

		$this->db_table_name = $wpdb->prefix . "donor_list";
		$this->states_table  = $wpdb->prefix . "donor_states";

		$this->init_states();
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
			<h2>Donor List</h2>
			<?php echo $this->get_list( array('edit' => 1) ); ?>
			<?php $this->admin_form(); ?>
		</div>
		<?php
	}

	function admin_form() {
		$action = get_option('siteurl') . '/wp-admin/admin-ajax.php';
		$nonce  = wp_create_nonce( $this->nonce_key );
		?>
		<form id="donor-list-form" action="<?php echo $action; ?>" method="POST">
			<div id="donor-edit">
				<h3>
					Edit Donor
					<label for="donor-business"><input tabindex="1" type="checkbox" name="donor_business" id="donor-business" value="1" /> Business</label>
				</h3>
				<fieldset>
					<label for="donor-first-name">First Name
						<input tabindex="1" type="text" name="donor[first_name]" id="donor-first-name" value="" />
					</label>
					<label for="donor-last-name">Last Name
						<input tabindex="1" type="text" name="donor[last_name]"  id="donor-last-name"  value="" />
					</label>
					<label for="donor-city">City
						<input tabindex="1" type="text" name="donor[city]" id="donor-city" value="" />
					</label>
					<label for="donor-state" class="state">State
					<?php echo $this->state_select(); ?>
					</label>
					<p>
						<input tabindex="1" disabled="disabled" type="submit" name="submit" value="Submit" id="donor-submit" />
					</p>
				</fieldset>
				<input type="hidden" name="donor[id]" value="" />
				<input type="hidden" name="action" value="<?php echo $this->nonce_key; ?>" />
				<input type="hidden" name="_wpnonce" value="<?php echo $nonce; ?>" />
			</div>
		</form>
		<?php
	}

	function admin_ajax() {
		if ( check_ajax_referer( $this->nonce_key ) ) {
			$data = (array) $_POST['donor'];
			$id = (int) $data['id'];
			$method = preg_match( '/^donor_list_(edit|delete)$/', $_REQUEST['action'], $m );
			die( $this->{$m[1]}( $data, $id ) );
		}
	}

	/**
	* shortcode - produces and returns the content to replace the shortcode tag
	*
	* @param array 	$atts		An array of attributes passed from the shortcode
	* @param string	$content	If the shortcode wraps round some html, this will be passed.
	*/
	function shortcode( $atts, $content = null ) {
		return $this->get_list( $atts );
	}

	/**
	* Create or update database table, database table version number -> WP option
	*/
	function install() {
		global $wpdb;

		// only run installation if not installed or if previous version installed
		if ( $this->db_version['installed'] === false
		||   $this->db_version['installed'] != $this->db_version['plugin'] ) {

			// http://codex.wordpress.org/Creating_Tables_with_Plugins
			$sql = "CREATE TABLE {$this->db_table_name} (
				id SMALLINT NOT NULL AUTO_INCREMENT,
				first_name VARCHAR(100),
				last_name VARCHAR(100) NOT NULL,
				city VARCHAR(100),
				state TINYINT,
				email VARCHAR(100),
				PRIMARY KEY  (id),
				KEY lname (last_name)
			);";

			require_once( ABSPATH . "wp-admin/upgrade-functions.php" );
			dbDelta( $sql );
			//add a database version number for future upgrade purposes
			update_option( $this->db_version_key, $this->db_version['plugin'] );
		}

		if ( $wpdb->get_var("SHOW TABLES LIKE '$this->states_table'") != $this->states_table ) {

			// join table for states because i <3 normalization
			$sql = "CREATE TABLE {$this->states_table} (
				id TINYINT NOT NULL AUTO_INCREMENT,
				iso_code CHAR(2),
				name VARCHAR(50),
				PRIMARY KEY  (id),
				KEY iso (iso_code)
			);";
			// don't wanna check for dbDelta, don't need it anyway
			$wpdb->query( $sql );

			// insert data
			$states = $this->states['source'];
			$values = array();
			foreach ( $states as $iso => $name ) { $values[] = "('$iso','$name')"; }
			$values = implode( ",", $values );
			$insert = "INSERT INTO {$this->states_table} (`iso_code`,`name`) VALUES $values;";
			$wpdb->query( $insert );
		}
	}

	function uninstall() {
		global $wpdb;

		// delete states join table
		$wpdb->query( "DROP TABLE {$this->states_table};" );

		// only delete donors + db_version option if donor_list empty
		if ( ! $wpdb->get_var("SELECT COUNT(id) FROM {$this->db_table_name};") ) {
			$wpdb->query( "DROP TABLE {$this->db_table_name};" );
			delete_option( $this->db_version_key );
		}
	}

	/**
	* CRUD - { create / update, delete, get (list) } DB operations
	*/
	function create_set( $posted ) {
		global $wpdb;
		$fieldtypes = array(
			'first_name' => '%s'
			,'last_name' => '%s'
			,'city'      => '%s'
			,'state'     => '%u'
			,'email'     => '%s'
		);
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
			$values[] = trim( wp_specialchars( $posted[ $key ] ) );
		}

		// magic!
		return call_user_func_array( array( &$wpdb, 'prepare' ), $values );
	}

	function edit( $data, $id = 0 ) {
		global $wpdb;
		// update if id present, otherwise insert
		$id = (int) $id;
		$values = $this->create_set( $data );
		// TODO: catch empties
		// assemble query
		$sql  = ( $id ? "UPDATE" : "INSERT INTO" );
		$sql .= " {$this->db_table_name} SET\n\t";
		$sql .= "$values" . ( $id ? $wpdb->prepare( "\nWHERE id = %u;", $id ) : ';' );
		return $sql;
	}

	function delete( $id ) {
		$id = (int) $id;
		if ( $id ) {
			global $wpdb;
			$sql = $wpdb->prepare( "DELETE FROM {$this->db_table_name} WHERE id = %u", $id );
			return $sql; // temp
			// $wpdb->query( $sql );
			// return true;
		}
		return false;
	}

	function get_list( $attrs = false ) {
		global $wpdb;

		extract( shortcode_atts( array(
			'limit' => 0,
			'edit'  => 0
		), array_filter( (array) $attrs ) ) );

		$_edit  = false;
		$_limit = ( (int) $limit ) ? "\nLIMIT $limit" : '';

		$sql = "SELECT t.id, t.first_name, t.last_name,
			t.email, t.city, t.state, s.iso_code AS iso
		FROM {$this->db_table_name} AS t
		LEFT JOIN {$this->states_table} AS s ON ( s.id = t.state )
		ORDER BY t.last_name, t.first_name $_limit;";

		$donors = $wpdb->get_results( $sql );

		if ( is_admin() && true === (bool) $edit ) {
			$_edit = '<td class="edit"><a href="#REPLACE" title="Edit Donor">edit</a></td>';
		}

		$s = array(
		"\n\t<table id=\"donor-list\" cellspacing=\"0\">",
		"\t<caption>Alphabetized by Last Name or Business Name</caption>",
		"<thead>",
		"\t<tr><th>Name ( <span>Last, First</span> )</th><th class=\"cs\">City, State</th></tr>",
		"</thead>",
		"<tbody>"
		);
		$edit_link = '';
		
		foreach ( $donors as $i => $donor ) {
			$alt = !( $i % 2 ) ? ' class="alt"' : '';
			$edit_link = preg_replace( '/REPLACE/', "{$donor->id},{$donor->state}", $_edit );
			$citystate = ( trim( $donor->city ) )
				? "<td>{$donor->city}, {$donor->iso}</td>"
				: '<td><br /></td>';
			$firstlast = ( $first = preg_replace( '/ and /', ' &amp; ', $donor->first_name ) )
				? "<th>{$donor->last_name}, {$first}</th>"
				: "<th>{$donor->last_name}</th>";
			$s[] = "\t<tr$alt>{$firstlast}{$citystate}{$edit_link}</tr>";
		}
		$s[] = "</tbody>";
		$s[] = "</table>\n";

		return implode( "\n\t", $s );
	}

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
		echo '<link rel="stylesheet" type="text/css" href="'. $this->plugin_url .'style.css" />'."\n";
	}

	function add_js() {
		wp_enqueue_script( 'donor-list', "{$this->plugin_url}plugin.js", array('jquery-form') );
		echo '<link rel="stylesheet" type="text/css" href="'. $this->plugin_url .'admin.css" />'."\n";
	}

	function init_states() {
		$source = array(
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
		$this->states = array(
			'index'  => array_keys(   $source ),
			'value'  => array_values( $source ),
			'source' => $source
		);
	}

	function state_select() {
		$states = $this->states['source'];
		$id = 0;
		$s = array();
		$s[] = "\n\t\t<select tabindex=\"1\" id=\"donor-state\" name=\"donor[state]\">";
		$s[] = "\t<option value=\"\"></option>"; // empty option
		foreach ( $states as $code => $name ) {
			$s[] = "\t<option value=\"". ++$id ."\" title=\"$name\">$code</option>";
		}
		$s[] = "</select>\n";
		return implode( "\n\t\t", $s );
	}
}
endif;

//instantiate the class
if ( class_exists( 'DonorList' ) ) {
	$DonorList = new DonorList();
}

?>