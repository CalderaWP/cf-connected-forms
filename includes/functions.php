<?php
/**
 * CF Form Connector functions
 *
 * @package   Caldera_Forms_Connector
 * @author    Josh Pollock <Josh@CalderaWP.com>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 Josh Pollock for CalderaWP
 */

add_action( 'init', 'cf_form_connector_init_current_position' );

		// add form type
		add_action( 'caldera_forms_get_form_templates', function( $templates ){
			$templates['cf_connected_form'] = array(
				'name'		=>	'Connected Form',
				'template'	=>	array(
					'is_connected_form' => true
				)
			);
			
			return $templates;
		}, 12);

		// set new connected form type
		add_filter( 'caldera_forms_create_form', function( $form ){
			parse_str( $_POST['data'], $newform );
			if( !empty( $newform['connected_form_primary'] ) ){
				$form['is_connected_form'] = true;
			}
			return $form;
		} );

		// setup form tabs for connected form
		add_filter( 'caldera_forms_get_panel_extensions', function( $panels ){
			if( !empty( $_GET['edit'] ) ){
				$form = \Caldera_Forms::get_form( $_GET['edit'] );
				if( !empty( $form['is_connected_form'] ) ){

					//  setup new panels for this type.
					//var_dump( $panels );
					//die;
					//uneeded panels
					unset( $panels['form_layout']['tabs']['pages'] );
					unset( $panels['form_layout']['tabs']['conditions'] );
					unset( $panels['form_layout']['tabs']['processors'] );
					unset( $panels['form_layout']['tabs']['variables'] );
					unset( $panels['form_layout']['tabs']['responsive'] );

					$panels['form_layout']['tabs']['layout']['name'] = __( 'Connections', 'connected-forms' );
					$panels['form_layout']['tabs']['layout']['label'] = __( 'Connected Forms Builder', 'connected-forms' );
					$panels['form_layout']['tabs']['layout']['actions'] = array();
					$panels['form_layout']['tabs']['layout']['side_panel'] = null;
					$panels['form_layout']['tabs']['layout']['canvas'] = CF_FORM_CON_PATH . 'includes/templates/connection-builder.php';

					// add script
					wp_enqueue_script( 'jsplumb', CF_FORM_CON_URL . 'assets/js/jsPlumb-1.7.10-min.js', array(), CF_FORM_CON_VER );
					wp_enqueue_script( 'connector-ui', CF_FORM_CON_URL . 'assets/js/connector-ui.js', array('jsplumb'), CF_FORM_CON_VER );
					wp_enqueue_style( 'connector-ui', CF_FORM_CON_URL . 'assets/css/connector-ui.css', array(), CF_FORM_CON_VER );
					
				}
			}
			return $panels;
		});


function cf_form_connector_get_base_form( $form ){
	if( !empty( $form['is_connected_form'] ) ){
		foreach( $form['node'] as $node_id => $form_node ){
			if( !empty( $form_node['base'] ) ){
				// setup the form processor bases
				return $form_node['form'];
			}
		}
	}
	return false;
}
function cf_form_connector_verify_id( $form, $id ){
	if( !empty( $form['is_connected_form'] ) ){
		foreach( $form['node'] as $node_id => $form_node ){
			if( !empty( $form_node['form'] ) && $form_node['form'] == $id ){
				return true; // yup its there
			}
		}
	}
	return false;
}

function cf_form_connector_init_current_position(){
	if( is_user_logged_in() ){
		if( isset( $_COOKIE['cfcfrm_usr'] ) ){
			// kill it
			$process_record = get_option( $_COOKIE['cfcfrm_usr'], array() );
			cf_form_connector_set_current_position( $process_record );
			setcookie('cfcfrm_usr', null, time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
		}
	}else{
		if( !isset( $_COOKIE['cfcfrm_usr'] ) ){
			$usertag = uniqid('cfcfrm');
			$expire = time() + ( 60 * DAY_IN_SECONDS );
			setcookie('cfcfrm_usr', $usertag, $expire, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
		}
	}
}
function cf_form_connector_get_current_position(){
		if(is_user_logged_in()){
			$user_id = get_current_user_id();
			//delete_user_meta( $user_id, CF_FORM_CON_SLUG );
			$data = get_user_meta( $user_id , CF_FORM_CON_SLUG, true );

		}else{
			// alternate method
			if( !empty( $_COOKIE['cfcfrm_usr'] ) ){
				$user_id = $_COOKIE['cfcfrm_usr'];
				$data = get_option( $user_id, array() );
			}else{
				$data = array();
			}
		}
	return $data;
}
function cf_form_connector_set_current_position( $data ){
		if(is_user_logged_in()){
			$user_id = get_current_user_id();
			update_user_meta( $user_id, CF_FORM_CON_SLUG, $data );
		}else{
			// alternate method
			if( !empty( $_COOKIE['cfcfrm_usr'] ) ){
				$user_id = $_COOKIE['cfcfrm_usr'];
				update_option( $user_id, $data );
			}
		}
}

function cf_form_connector_get_stage_state( $entry_id, $form ){
	
	$process_record = cf_form_connector_get_current_position();
	if( !empty( $process_record ) && !empty( $process_record[ $form['ID'] ] ) && !empty( $form['current_form'] ) ){

		if( !empty( $process_record[ $form['ID'] ][ $form['current_form'] ] ) && !empty( $process_record[ $form['ID'] ][ $form['current_form'] ]['id'] ) ){
			if( is_user_logged_in() ){
				return $process_record[ $form['ID'] ][ $form['current_form'] ]['id'];
			}else{
				// no permission, simulate with prepopulate			
				$entry = Caldera_Forms::get_entry( $process_record[ $form['ID'] ][ $form['current_form'] ]['id'], $form['current_form'] );
				if( !empty( $entry['data'] ) ){
					foreach( $entry['data'] as $field_id => $values) {
						$process_record[ $form['ID'] ][ $form['current_form'] ]['pre_data'][ $field_id ] = $values['value'];
					}
					cf_form_connector_set_current_position( $process_record );
				}
				add_filter( 'caldera_forms_render_pre_get_entry', 'cf_form_connector_partial_populate_form', 10, 2 );
			}
		}
	}

	return $entry_id;
}

add_filter( 'caldera_forms_render_entry_id', 'cf_form_connector_get_stage_state', 10, 2 );

function cf_form_connector_setup_processors( $form ){

	if( empty( $form['is_connected_form'] ) ){
		return $form;
	}
	// ftch the connected stage form
	$base_form = Caldera_Forms::get_form( cf_form_connector_get_base_form( $form ) );
	if( !empty( $_POST['cffld_stage'] ) ){
		$current_form = Caldera_Forms_Sanitize::sanitize( $_POST['cffld_stage'] );
		// check this is part of the flow
		if( false === cf_form_connector_verify_id( $form, $current_form ) ){
			// erp.. hmm set the base
			$current_form = $base_form['ID'];
		}
	}else{
		$current_form = $base_form['ID'];
	}
	$stage = $form; // set the primary ( staging form )

	// get the process record
	$process_record = cf_form_connector_get_current_position();

	// get this form to be run
	$form = Caldera_Forms::get_form( $current_form );
	// check to see if its a back track
	if( !empty( $_POST['cffld_backnav'] ) && !empty( $process_record[ $stage['ID'] ] ) ){

		if( $current_form === $process_record[ $stage['ID'] ]['current_form'] ){			
			// prepare state jump
			$previous_form = $process_record[ $stage['ID'] ]['previous_form'];
			// create backtrack record
			if( !empty( $process_record[ $stage['ID'] ][ $previous_form ]['back'] ) ){
				$previous_back_form = $process_record[ $stage['ID'] ][ $previous_form ]['back'];
				$process_record[ $stage['ID'] ][ 'previous_form'] = $previous_back_form;
			}else{
				unset( $process_record[ $stage['ID'] ][ 'previous_form'] );
			}
			$process_record[ $stage['ID'] ][ 'current_form'] = $previous_form;
			// capture this without saving on a back as not to do processing
			if(!empty($form['fields'])){
				if( empty( $process_record[ $stage['ID'] ][ $current_form ] ) ){				
					$process_record[ $stage['ID'] ][ $current_form ] = array();
				}
				foreach($form['fields'] as $field_id=>$field){
					if( !empty( $_POST[ $field_id ] ) ){
						$process_record[ $stage['ID'] ][ $current_form ]['pre_data'][ $field_id ] = $_POST[ $field_id ];
					}
				}
			}

			cf_form_connector_set_current_position( $process_record );

			// check for ajax
			if( !empty( $stage['form_ajax'] ) ){
				wp_send_json( array( 'target' => $stage['ID'] . '_' . (int) $_POST['_cf_frm_ct'], 'form' => Caldera_Forms::render_form( $stage ) ) );
			}
			var_dump( 'ALT LOAD THING' );
			die;
			
		}
		var_dump( $_POST );
		die;
	}	
	$form['stage_form'] = $stage['ID'];


	if( empty( $process_record[ $stage['ID'] ] ) ){

		// first form - make the ID if not an edit;
		if( empty( $_POST['_cf_frm_edt'] ) ){
			global $wpdb;
			// CREATE ENTRY
			$new_entry = array(
				'form_id'	=>	$stage['ID'],
				'user_id'	=>	0,
				'datestamp' =>	date_i18n( 'Y-m-d H:i:s', time(), 0),
				'status'	=>	'pending'
			);
			// if user logged in
			if(is_user_logged_in()){
				$new_entry['user_id'] = get_current_user_id();
			}

			$wpdb->insert($wpdb->prefix . 'cf_form_entries', $new_entry);
			$entryid = $wpdb->insert_id;
			$process_record = array();
			$process_record[ $stage['ID'] ] = array(
				'entry_id' 		=>	$entryid,
				'completed'		=>	false,
				'current_form'	=>	$form['ID']
			);
			cf_form_connector_set_current_position( $process_record );
		}
	}

	// disable mailer
	$form['mailer']['enable_mailer'] = false;
	// setup each connection condition
	foreach( $stage['condition_points']['conditions'] as $condition_point => $condition ){

		// ye, lets only use the conditions for this form
		if( $condition['form'] != $form['ID'] ){
			continue;
		}
		// check its there
		if( empty( $stage['node'][ $condition['connect'] ] ) ){
			continue;
		}
		// create a processor IF
		$processor_id = 'cfp_' . $stage['ID'] . '_' . $condition_point;
		$form['processors'][ $processor_id ] = array(
			'ID' 			=> $processor_id,
			'type' 			=> 'form-connector',
			'config' 		=> array(
				'next_form_button'		=> '',//$condition['next'],
				'next_form_id'			=> $stage['node'][ $condition['connect'] ]['form'],
				'next_message' 			=> '',
				'previous_form_button'	=> '',//$condition['back'],
				'previous_message' 		=> ''
			),
			'conditions'	=>	array(
			)
		);
		if( !empty( $condition['group'] ) ){
			$form['processors'][ $processor_id ]['conditions'] = array(
				'type' 		=> 'use',
				'group'		=>	$condition['group']
			);
		}				

	}
	return $form;
}

add_filter( 'caldera_forms_submit_get_form' ,'cf_form_connector_setup_processors');
add_filter( 'caldera_forms_redirect_url', 'cf_form_connector_control_form_load', 10, 2 );

function cf_form_connector_control_form_load( $url, $form ){

	if( !empty( $form['stage_form'] ) ){
		$stage_form = Caldera_Forms::get_form( $form['stage_form'] );
		$process_record = cf_form_connector_get_current_position();
		
		if( !empty( $form['form_connection'] ) ){

			$process_record[ $form['stage_form'] ][ $form['ID'] ] = array(			
				'id'	=> $form['form_connection']['entry_id']
			);
			if( !empty( $process_record[ $form['stage_form'] ][ 'previous_form'] ) ){
				$process_record[ $form['stage_form'] ][ $form['ID'] ]['back'] = $process_record[ $form['stage_form'] ][ 'previous_form'];
			}
			$process_record[ $form['stage_form'] ][ 'previous_form'] = $form['ID'];
			$process_record[ $form['stage_form'] ][ 'current_form'] = $form['form_connection']['next_form_id'];
			cf_form_connector_set_current_position( $process_record );

			wp_send_json( array( 'target' => $form['stage_form'] . '_' . (int) $_POST['_cf_frm_ct'], 'form' => Caldera_Forms::render_form( $stage_form ) ) );
		}else{
			// final form in chain - swap to main
			var_dump( $stage_form );
			die;
		}
	}
	
	return $url;
}

function cf_form_connector_partial_populate_form( $data, $form ){

	if( !empty( $form['current_form'] ) ){
		$process_record = cf_form_connector_get_current_position();

		if( !empty( $process_record[ $form['ID'] ][ $form['current_form'] ]['pre_data'] ) ){
			return $process_record[ $form['ID'] ][ $form['current_form'] ]['pre_data'];
		}
	}

	return $data;
}

add_filter( 'caldera_forms_render_get_form', function( $form ){
	if( !empty( $form['is_connected_form'] ) ){

		$base_form = cf_form_connector_get_base_form( $form );
		// Some checks to see if this user is working on this for / or whatever to load the new entry
		// if nothing i.e a new start, then we're on the base form!
		// get the process record
		$process_record = cf_form_connector_get_current_position();

		if(!empty( $process_record[ $form['ID'] ] ) && false === $process_record[ $form['ID'] ]['completed'] ){
			if( !empty( $process_record[ $form['ID'] ]['previous_form'] ) ){
				$previous_form = $process_record[ $form['ID'] ]['previous_form'];
			}
			$new_form = Caldera_Forms::get_form( $process_record[ $form['ID'] ]['current_form'] );
			if( !empty( $process_record[ $form['ID'] ][ $new_form['ID'] ]['pre_data'] ) && empty( $process_record[ $form['ID'] ][ $new_form['ID'] ]['id'] ) ){
				add_filter( 'caldera_forms_render_pre_get_entry', 'cf_form_connector_partial_populate_form', 10, 2 );
			}
		}

		if( empty( $new_form ) ){
			// not form replacement, load up base
			$new_form = Caldera_Forms::get_form( $base_form );
		}
		
		$current_form = $new_form['ID'];
		$new_form['ID'] = $form['ID'];
		$new_form['current_form'] = $current_form;

		foreach( $new_form['fields'] as $field_id => $field ){
			// remove any submit buttons
			if( $field['type'] == 'button' && $field['config']['type'] =='submit' ){
				//unset( $new_form['fields'][ $field_id ] );
				unset( $new_form['layout_grid']['fields'][ $field_id ] );
			}
		}

		$pages = explode( '#', $new_form['layout_grid']['structure'] );
		if( count( $pages ) <= 1 ){
			// single page- add to this one			
			$rows = explode('|', $new_form['layout_grid']['structure'] );
			$last_row = count( $rows ) + 1;
			// add last row for navigation
			// perhaps a template thing.... later.
			$new_form['layout_grid']['structure'] .= '|6:6';			
			$new_form['layout_grid']['fields']['cffld_nextnav'] = $last_row . ':2';
			$new_form['layout_grid']['fields']['cffld_stage'] = $last_row . ':2';
			
			if( !empty( $previous_form ) && $previous_form != $new_form['ID'] ){

				$new_form['layout_grid']['fields']['cffld_backnav'] = $last_row . ':1';
				$new_form['fields']['cffld_backnav'] = array(
					'ID' => 'cffld_backnav',
					'type' => 'cfcf_back_nav',
					'label' => __('Back', 'cf-form-connector' ),
					'slug' => 'cfcf_back_nav',
					'conditions' => array(
						'type' => ''
					),
					'caption' => '',
					'config' => array(
						'custom_class' => '',
						'visibility' => 'all',
						'type' => 'back',
						'class' => 'btn btn-default',
						'default' => ''
					)
				);
			}
			$new_form['fields']['cffld_nextnav'] = array(
				'ID' => 'cffld_nextnav',
				'type' => 'cfcf_next_nav',
				'label' => __('Next', 'cf-form-connector' ),
				'slug' => 'cfcf_next_nav',
				'conditions' => array(
					'type' => ''
				),
				'caption' => '',
				'config' => array(
					'custom_class' => '',
					'visibility' => 'all',
					'type' => 'next',
					'class' => 'btn btn-default pull-right',
				)
			);
			$new_form['fields']['cffld_stage'] = array(
				'ID' => 'cffld_stage',
				'type' => 'hidden',
				'label' => '',
				'slug' => 'cffld_stage',
				'conditions' => array(
					'type' => ''
				),
				'caption' => '',
				'config' => array(
					'custom_class' => '',
					'visibility' => 'all',
					'default' => $current_form
				)
			);
			$new_form['connected_stage'] = true;
			// add filter to register the nav buttoms ( this is so that they dont show in form builder )
			add_filter('caldera_forms_get_field_types', 'cf_form_connector_register_fields');
			
		}else{

		}
		// setup the js handler if ajax
		if( $form['form_ajax'] ){
			wp_enqueue_script( 'cf-form-connector-ajax', CF_FORM_CON_URL . 'assets/js/cf-connected-ajax.js', array( 'jquery' ), CF_FORM_CON_VER , true );
			$new_form['custom_callback'] = 'cf_connected_ajax_handler';
		}
		return $new_form;
	}

	return $form;
} );

add_filter( 'caldera_forms_render_form_attributes', 'cf_form_connector_add_form_flag', 11, 2 );
function cf_form_connector_add_form_flag( $atts, $form ){
	if( !empty( $form['connected_stage'] ) ){
		// additionals like target :)
		//$atts['data-stage-target'] = '#' . $atts['id'];
		//$atts['data-target-insert'] = 'replace';
	};

	return $atts;
}

/**
 * Registers the Form Connector processor
 *
 * @since 0.1.0
 * @param array		$processors		Array of current registered processors
 *
 * @return array	array of regestered processors
 */
function cf_form_connector_register($processors){
	
	$processors['form-connector'] = array(
		"name"				=>	__('Connected Forms', 'cf-form-connector'),
		"description"		=>	__( 'Connect multiple forms.', 'cf-form-connector'),
		//"icon"				=>	CF_FORM_CON_URL . "icon.png",
		"author"			=>	"Josh Pollock for CalderaWP LLC",
		"author_url"		=>	"https://CalderaWP.com",
		"post_processor"	=>	'cf_form_connector_process',
		"template"			=>	CF_FORM_CON_PATH . "includes/config.php",

	);

	return $processors;

}

/**
 * Proccess submission
 *
 * @since 0.1.0
 *
 * @param array $config Processor config
 * @param array $form Form config
 *
 * @return array
 */
function cf_form_connector_process( $config, $form ) {
	global $form;
	$form['form_connection'] = $config;
	$form['form_connection']['entry_id'] = Caldera_Forms::get_field_data( '_entry_id', $form );
}

/**
 * Change form to be rendered
 *
 * @since 0.1.0
 *
 * @uses "caldera_forms_render_get_form" filter
 *
 * @param array $form The form config
 *
 * @return array
 */
function cf_form_connector_change_form( $form ) {
	if (
		isset( $_GET[ 'cf_con' ] )
		&& isset( $_GET[ 'cf_con_form_id' ] )
		&& isset( $_GET[ 'cf_con_nonce' ] )
		&& $_GET[ 'cf_con' ]
		&& wp_verify_nonce( $_GET[ 'cf_con_nonce' ], 'cf_con_nonce' )
	) {
		remove_filter( 'caldera_forms_render_get_form', 'cf_form_connector_change_form' );
		$_form = Caldera_Forms::get_form( Caldera_Forms_Sanitize::sanitize( $_GET[ 'cf_con_form_id' ] ) );
		if ( is_array( $_form ) ) {
			if ( isset( $_GET[ 'cf_id' ] ) && 0 < absint( $_GET[ 'cf_id' ] ) ) {
				add_filter( 'caldera_forms_render_entry_id', function( $entry_id ) {
					return (int) $_GET[ 'cf_id' ];
				} );
			}
			$form = $_form;
		}



	}

	return $form;

}


/**
 * Register the nav fields
 *
 * @since 1.0.0
 *
 *
 * @param array 	$fieldtypes		list of currently registered field types
 *
 * @return array	altered list of fieldtypes with password field added.
 */
function cf_form_connector_register_fields($fieldtypes){

	$fieldtypes['cfcf_back_nav'] = array(
		"field"		=>	"Back Connected Form",
		"file"		=>	CF_FORM_CON_PATH . "includes/templates/back_field.php",
		"category"	=>	__("Text Fields,User,Basic", "cf-users"),
		"description" => 'Password field with confirm toggle',
		//"handler"	 =>	'cf_form_connector_handle_next',
		//"save"		=>	'cf_form_connector_save_next',
		"setup"		=>	array(
			"not_supported"	=>	array(
				'entry_list'
			)
		)
	);
	$fieldtypes['cfcf_next_nav'] = array(
		"field"		=>	"Next Connected Form",
		"file"		=>	CF_FORM_CON_PATH . "includes/templates/next_field.php",
		"category"	=>	__("Text Fields,User,Basic", "cf-users"),
		"description" => 'Password field with confirm toggle',
		//"handler"	 =>	'cf_form_connector_handle_next',
		//"save"		=>	'cf_form_connector_save_next',
		"setup"		=>	array(
			"not_supported"	=>	array(
				'entry_list'
			)
		)
	);	
	return $fieldtypes;
}
