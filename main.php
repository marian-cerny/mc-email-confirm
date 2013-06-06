<?php

/*
Plugin name: Download Email Confirmation
Plugin version: 1.0
Author name: Marian Cerny
Author URL: http://mariancerny.com
Description: This plug-in enables you to require the user to confirm their email address before downloading a file. The user's details will be emailed to your specified email address after confirmation.
*/

class mc_email_confirmation
{


// *******************************************************************
// ------------------------------------------------------------------
//					CONSTRUCTOR AND INITIALIZATION
// ------------------------------------------------------------------
// *******************************************************************


	var $settings = array(
		'general' => array(
			'title' => 'General settings',
			'output_function' => 'output_settings_section_general',
			'fields' => array(
				'company_email' => array(
					'title' => 'Company email address',
					'type' => 'text',
					'value' => '',
					'description' => 'This is the address where the student\'s details will be sent to. Separate by comma if more emails are needed.'
				),
				'file_link' => array(
					'title' => 'Link to file',
					'type' => 'text',
					'value' => '',
					'description' => 'This is the link that will be sent to the user after confirming their email address.'
				),
			),
		)
	);
	

	var $plugin_name;
	var $plugin_slug;
	var $plugin_url;
	var $plugin_version;
	var $plugin_namespace;
	
	
	function __construct()
	{	
		/* SET UP PLUGIN VARIABLES */
		$this->plugin_name = 'Download Email Confirmation';
		$this->plugin_slug = 'email-confirmation';
		$this->plugin_url = plugins_url( '', __FILE__ );
		$this->plugin_version = '1.0';
		$this->plugin_namespace = 'mc_ec_';
		
		/* GET SETTINGS FROM DATABASE */
		$this->get_settings_from_db();			
		
		/* ADD ACTIONS, SHORTCODES AND FILTERS */		
		add_filter('query_vars', array( $this, 'register_url_parameter' ) );
  		add_action( 'admin_menu', array( $this, 'register_settings') );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_and_scripts' ) );
		add_action( 'template_redirect', array( $this, 'email_confirmation' ) );
		
		add_action( 'wp_ajax_insert_form', array( $this, 'output_form' ) );
		add_action( 'wp_ajax_nopriv_insert_form', array( $this, 'output_form' ) );
		
		add_action( 'wp_ajax_save_data', array( $this, 'save_user_data' ) );
		add_action( 'wp_ajax_nopriv_save_data', array( $this, 'save_user_data' ) );
		
		// INITIALIZE VARIABLES
		global $wpdb;
		$this->s_table_name = $wpdb->prefix . "mc_email_confirmation";
		
		// CREATE WISHLIST QUERY
		$s_wishlist_sql = "CREATE TABLE $this->s_table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			email varchar(255) NOT NULL,
			phone varchar(255) NOT NULL,
			code varchar(255) NOT NULL,
			enq_type varchar(255) NOT NULL,			
			activated int(1) NOT NULL,			
			UNIQUE KEY id (id)
		);";

		// CREATE DATABASE TABLES IF DON'T EXIST 
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($s_wishlist_sql);
	}


// *******************************************************************
// ------------------------------------------------------------------
//							PUBLIC FUNCTIONS
// ------------------------------------------------------------------
// *******************************************************************

	
	function output_form()
	{
		include( 'form.php' );
	}
	
	
	function save_user_data()
	{
		global $wpdb;
		// CREATE AN ARRAY OF VALUES TO BE INSERTED
		$a_values = array(
			'phone' => $_POST['phone'],
			'email' => $_POST['email'],
			'name' => $_POST['name'],
			'enq_type' => 'student',
			'activated' => 0,
			'code' => ''
		);
		// INSERT VALUES	
		$wpdb->insert( $this->s_table_name, $a_values );
		
		// GET LATEST RECORD ID
		$code = md5( $wpdb->insert_id );
		$a_values = array( 'code' => $code );
		
		// INSERT MD5 HASH OF ID AS ACTIVATION CODE
		$a_where = array( 'id' => $wpdb->insert_id );
		$wpdb->update( $this->s_table_name, $a_values, $a_where );
		
		// SEND EMAIL TO USER
		$confirm_link = get_home_url( NULL ) . '/confirmation/?code=' . $code;
		$to = $_POST['email'];
		$subject = 'Your Pinnacle Student Report - confirmation required';
		$headers = 'From: "Pinnacle MC Global" <contact@pinnaclemcglobal.com>';
		$message = "Thank you for your interest in Pinnacle's 2013 Student Accommodation Report.

Please click on the link below to confirm your email address so that we can email you a copy.

{$confirm_link}

- The Pinnacle Team

This email has been automatically generated, please do not reply directly to this email.";

		wp_mail( $to, $subject, $message, $headers );

	}
	
	
	function email_confirmation() 
	{
		global $wp_query, $wp, $wpdb;
		
		// REGISTER PARAMETER
		
		if ( $wp->request == 'confirmation' )
		{
			// get activation code
			$code = $wp_query->query_vars['code'];
			
			// update field where activation code matches
			$a_where = array( 'code' => $code );
			$a_data = array( 'activated' => 1 );
			$success = $wpdb->update( $this->s_table_name, $a_data, $a_where );
			
			if ( $success )
			{
				// set redirection and show message
				header('Refresh: 5; url=' . get_home_url( NULL ) );
				echo 'Your student report has been sent to your email address. Thank you.';
				
				$this->sendEmailsOnSuccess( $code );
			}
			else 
			{
				header('Refresh: 5; url=' . get_home_url( NULL ) );
				echo 'The confirmation code is not valid.';
			}
			
			exit();
		}
	
	}
	
	function register_url_parameter( $qvars )
	{
		 $qvars[] = 'code';
		 return $qvars;
	}
	
	function sendEmailsOnSuccess( $code )
	{
		global $wpdb;
		// GET USER DATA
		$s_query = 'SELECT * FROM ' . $this->s_table_name . ' WHERE code="' . $code . '"';
		$user_data = $wpdb->get_row( $s_query );
	
		// SEND EMAIL TO USER
		$report_link = $this->get_setting( 'file_link' );
		$to = $user_data->email;
		$subject = 'Your Pinnacle Student Report';
		$headers = 'From: "Pinnacle MC Global" <contact@pinnaclemcglobal.com>';
		$message = "Thank you, your email address has been confirmed.

Please download the 2013 report here:

{$report_link}

- The Pinnacle Team";

		wp_mail( $to, $subject, $message, $headers );
		
		
		// SEND EMAIL TO COMPANY
		$to = $this->get_setting( 'company_email' );
		$subject = 'New Student Enquiry';
		$headers = 'From: "Pinnacle MC Global" <contact@pinnaclemcglobal.com>';
		$message = "Name: {$user_data->name}
Email: {$user_data->email}
Phone number: {$user_data->phone}
		";
		wp_mail( $to, $subject, $message, $headers );
		
	}

// *******************************************************************
// ------------------------------------------------------------------
//							PRIVATE FUNCTIONS
// ------------------------------------------------------------------
// *******************************************************************
	
	/* ENQUEUE STYLES AND SCRIPTS */
	function enqueue_styles_and_scripts()
	{
		// ENQUEUE JQUERY
		wp_enqueue_script( 'jquery' );
		
		wp_enqueue_script( 
			$this->plugin_slug, 
			$this->plugin_url . '/script.js',
			array( 'jquery' ),
			$this->plugin_version
		);
		
		wp_enqueue_script( 'fancybox-script', $this->plugin_url . '/fancybox/jquery.fancybox.pack.js' );
		wp_enqueue_style( 'fancybox-style', $this->plugin_url . '/fancybox/jquery.fancybox.css' );
		
		// PASS PLUGIN SETTINGS TO THE SCRIPT
		$a_ajax_vars = array(
			'settings' => $this->get_settings_array(),
			'ajax_url' => admin_url( 'admin-ajax.php' )
		);		
		wp_localize_script(
			$this->plugin_slug, 
			$this->plugin_namespace . 'ajax_vars', 
			$a_ajax_vars
		);
	}
	
	
	/* ASSIGN SETTINGS FROM PLUGIN OPTIONS TO THE SETTINGS ARRAY */
	private function get_settings_from_db()
	{
		foreach ( $this->settings as $s_setting_key => $a_setting )
		{
			foreach( $a_setting['fields'] as $s_field_key => $m_field ) 
			{		
				// get options if they are set, or get defaults if not set
				$this->settings[$s_setting_key]['fields'][$s_field_key]['value'] 
					= get_option( $this->plugin_namespace . $s_field_key, $m_field['value'] );
					
				// write all options in DB in case they were not set
				update_option( $this->plugin_namespace . $s_field_key, $this->get_setting( $s_field_key ) );
			}
		}
	}
	
	
	/* GET ALL SETTINGS IN A SIMPLE KEY=>VALUE TYPE ARRAY  */ 
	private function get_settings_array()
	{
		$a_result = array();
	
		foreach ( $this->settings as $s_setting_key => $a_setting )
		{
			foreach( $a_setting['fields'] as $s_field_key => $m_field ) 
			{		
				$a_result[ $s_field_key ] = $this->get_setting( $s_field_key ) ;
			}
		}
		
		return $a_result;
	}
	
	
	/* RETURN THE VALUE OF A GIVEN SETTING FROM THE SETTINGS ARRAY */
	private function get_setting( $s_field_name )
	{
		foreach ( $this->settings as $a_setting )
		{
			if ( array_key_exists( $s_field_name, $a_setting['fields'] ) )
				return $a_setting['fields'][$s_field_name]['value'];
		}
		return false;
	}


// *******************************************************************
// ------------------------------------------------------------------
//							OPTIONS MENU
// ------------------------------------------------------------------
// *******************************************************************
	
	
	/* CREATE AN ENTRY IN THE SETTINGS MENU AND REGISTER/OUTPUT ALL SETTINGS */
	function register_settings() 
	{
		add_options_page(
			$this->plugin_name, 
			$this->plugin_name, 
			'manage_options', 
			$this->plugin_slug, 
			array( $this, 'output_options_page' )
		);
		
		// CREATE OPTIONS SECTIONS		
		foreach ( $this->settings as $s_section_name => $a_settings_section )
		{
				
			add_settings_section( 
				$this->plugin_namespace . $s_section_name, 
				$a_settings_section['title'], 
				array( $this, 'output_settings_section_general' ), 
				$this->plugin_slug
			);
			
			// CREATE OPTIONS FIELDS AND REGISTER SETTINGS
			foreach( $a_settings_section['fields'] as $s_field_name => $a_settings_field )
			{				
				add_settings_field(
					$this->plugin_namespace . $s_field_name, 
					$a_settings_field['title'],
					array($this, 'output_option'), 
					$this->plugin_slug, 
					$this->plugin_namespace . $s_section_name,
					array(
						'type' => $a_settings_field['type'],
						'name' => $s_field_name,
						'section' => $s_section_name,
						'description' => $a_settings_field['description'],
					)
				);
			
				register_setting( $this->plugin_namespace . 'settings', $this->plugin_namespace . $s_field_name );
			}
			
		}
		
	}
	
	/* OUTPUT OPTIONS PAGE */
	function output_options_page()
	{
		?>
		<div class="wrap">
		<h2><?php echo $this->plugin_name; ?> Settings</h2>
		
		<form method="post" action="options.php">
		
			<?php
			
			foreach ( $this->settings as $s_section_name => $a_settings_section )
				settings_fields( $this->plugin_namespace . 'settings' );
			
			do_settings_sections( $this->plugin_slug  );     
			submit_button(); 
			
			?>
		
		</form>
		</div>
		<?php
	}
	
	/* OUTPUT GENERAL SETTINGS SECTION */
	function output_settings_section_general()
	{
		echo '';
	}
	
	/* OUTPUT OPTION */
	function output_option( $args )
	{
		if ( $args['type'] == 'radio' )
		{
			$orig_value = get_option( $this->plugin_namespace . $args['name'] );
			
			// echo "<pre>"; print_r( $this->settings[$args['section']]['fields'][$args['name']]['options'] ); echo "</pre>";
			
			// echo $args['name'];
			
			foreach ( $this->settings[$args['section']]['fields'][$args['name']]['options'] as $key => $value )
			{
				$s_output = "<label for='" . $this->plugin_namespace . $key . "'>";
				$s_output .= "<input 
				type='radio' 
				name='". $this->plugin_namespace . $args['name'] ."' 
				value='" . $key . "' 
				id='" . $this->plugin_namespace . $key . "'";
						
				$s_output .= checked( $orig_value, $key, false );
				$s_output .= "'/>" . $value . " </label> <br/>";
			
				echo $s_output;
			}
		}
		else 
		{
			$s_output = "<input 
				name='" . $this->plugin_namespace . $args['name'] ."'
				id='" . $this->plugin_namespace . $args['name'] ."'
				type='" . $args['type']."'";
			
			if ( $args['type'] == 'checkbox' )
				$s_output .= checked( 'on', get_option( $this->plugin_namespace . $args['name'], false ), false );
			else
				$s_output .= "value='".get_option( $this->plugin_namespace . $args['name'] )."'";
				
			if ( !empty( $args['description'] ) )
			$s_output .= "/> (" . $args['description'] . ")";
			
			echo $s_output;	
		}
		
	}


}


// *******************************************************************
// ------------------------------------------------------------------
//						FUNCTION SHORTCUTS
// ------------------------------------------------------------------
// *******************************************************************

$mc_gp_plugin = new mc_email_confirmation();


?>