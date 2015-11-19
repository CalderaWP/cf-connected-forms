<?php
/**
 * CF Form Connector functions
 *
 * @package   CF_Connected_Forms
 * @author    Josh Pollock <Josh@CalderaWP.com>
 * @license   GPL-2.0+
 * @link
 * @copyright 2015 David Cramer & Josh Pollock for CalderaWP
 */

/**
 * Add our hooks
 */
add_filter( 'caldera_forms_render_entry_id', 'cf_form_connector_get_stage_state', 10, 2 );
add_action( 'init', 'cf_form_connector_init_current_position' );
add_filter( 'caldera_forms_get_form' ,'cf_form_connector_setup_processors_check');
add_filter( 'caldera_forms_submit_get_form' ,'cf_form_connector_setup_processors');
add_filter( 'caldera_forms_ajax_return', 'cf_form_connector_control_form_load', 10, 3 );
add_action( 'caldera_forms_redirect', 'cf_form_connector_control_form_load_manual', 25, 3 );
add_filter( 'caldera_forms_render_form_attributes', 'cf_form_connector_add_form_flag', 11, 2 );


/**
 * Set new connected form type as template
 *
 * @since 0.2.0
 */
add_action( 'caldera_forms_get_form_templates', function( $templates ){
	$templates['cf_connected_form'] = array(
		'name'		=> __( 'Connected Form', 'cf-connected-forms' ),
		'template'	=>	array(
			'is_connected_form' => true
		)
	);

	return $templates;
}, 12);

/**
 * Setup connected form on create
 *
 * @since 0.2.0
 */
add_filter( 'caldera_forms_create_form', function( $form ){
	parse_str( $_POST['data'], $newform );
	if( ! empty( $newform['connected_form_primary'] ) ){
		$form['is_connected_form'] = true;
	}

	return $form;

} );

/**
 * Setup form tabs for connected form
 *
 * @since 0.2.0
 */
add_filter( 'caldera_forms_get_panel_extensions', function( $panels ){
	if( !empty( $_GET['edit'] ) ){
		$form = \Caldera_Forms::get_form( $_GET['edit'] );
		if( !empty( $form['is_connected_form'] ) ){

			//setup new panels for this type.
			//uneeded panels
			unset( $panels['form_layout']['tabs']['pages'] );
			unset( $panels['form_layout']['tabs']['conditions'] );
			unset( $panels['form_layout']['tabs']['processors'] );
			unset( $panels['form_layout']['tabs']['variables'] );
			unset( $panels['form_layout']['tabs']['responsive'] );

			//add needed
			$panels['form_layout']['tabs']['layout']['name'] = __( 'Connections', 'connected-forms' );
			$panels['form_layout']['tabs']['layout']['label'] = __( 'Connected Forms Builder', 'connected-forms' );
			$panels['form_layout']['tabs']['layout']['actions'] = array();
			$panels['form_layout']['tabs']['layout']['side_panel'] = null;
			$panels['form_layout']['tabs']['layout']['canvas'] = CF_FORM_CON_PATH . 'includes/templates/connection-builder.php';

			// add scripts
			wp_enqueue_script( 'jsplumb', CF_FORM_CON_URL . 'assets/js/jsPlumb-1.7.10-min.js', array(), CF_FORM_CON_VER );
			wp_enqueue_script( 'connector-ui', CF_FORM_CON_URL . 'assets/js/connector-ui.min.js', array('jsplumb'), CF_FORM_CON_VER );
			wp_enqueue_style( 'connector-ui', CF_FORM_CON_URL . 'assets/css/connector-ui.css', array(), CF_FORM_CON_VER );

		}
	}

	return $panels;

});

/**
 * Get base form for this connected form if is a connected form.
 *
 * @since 0.2.0
 *
 * @param array $form Form config
 *
 * @return bool
 */
function cf_form_connector_get_base_form( $form ){
	if( ! empty( $form['is_connected_form'] ) ){
		foreach( $form['node'] as $node_id => $form_node ){
			if( !empty( $form_node['base'] ) ){
				// setup the form processor bases
				return $form_node['form'];
			}
		}
	}
	return false;
}

/**
 * Verify this is the correct form
 *
 * @since 0.2.0
 *
 * @param array $form Form config
 * @param string $id Form ID
 *
 * @return bool
 */
function cf_form_connector_verify_id( $form, $id ){
	if( !empty( $form['is_connected_form'] ) ){
		foreach( $form['node'] as $node_id => $form_node ){
			if( ! empty( $form_node['form'] ) && $form_node['form'] == $id ){
				return true; // yup its there
			}

		}

	}

	return false;
}

/**
 * Ensure we are at right place in sequence
 *
 * @uses "init"
 *
 * @since 0.2.0
 */
function cf_form_connector_init_current_position(){
	if( is_user_logged_in() ){
		if( isset( $_COOKIE['cfcfrm_usr'] ) ){
			// kill it
			$process_record = get_option( 'cfcfrm_' . $_COOKIE['cfcfrm_usr'], array() );
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

/**
 * Detemrine correct place in sequence
 *
 * @since 0.2.0
 *
 * @return array|mixed|void
 */
function cf_form_connector_get_current_position(){
		if(is_user_logged_in()){
			$user_id = get_current_user_id();
			//delete_user_meta( $user_id, CF_FORM_CON_SLUG );
			$data = get_user_meta( $user_id , CF_FORM_CON_SLUG, true );

		}else{
			// alternate method
			if( !empty( $_COOKIE['cfcfrm_usr'] ) ){
				$user_id = $_COOKIE['cfcfrm_usr'];
				$data = get_option( 'cfcfrm_' .  $user_id, array() );
			}else{
				$data = array();
			}
		}
	return $data;
}

/**
 * Set current place in the sequence
 *
 * @since 0.2.0
 *
 * @param array $data
 */
function cf_form_connector_set_current_position( $data ){
		if(is_user_logged_in()){
			$user_id = get_current_user_id();
			update_user_meta( $user_id, CF_FORM_CON_SLUG, $data );
		}else{
			// alternate method
			if( !empty( $_COOKIE['cfcfrm_usr'] ) ){
				$user_id = 'cfcfrm_' .  $_COOKIE['cfcfrm_usr'];
				update_option( $user_id, $data );
			}
		}
}

/**
 * Get the current state for this stage in process
 *
 * @since 0.2.0
 *
 * @uses "caldera_forms_render_entry_id"
 *
 * @param int $entry_id ID of entry
 * @param array $form The form configuration
 *
 * @return mixed
 */
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

/**
 * Setup processors on the connected form
 *
 * @since 0.2.0
 *
 * @uses "caldera_forms_submit_get_form"
 *
 * @param array $form The form configuration
 *
 * @return array|null
 */
function cf_form_connector_setup_processors( $form ){

	if( empty( $form['is_connected_form'] ) ){
		return $form;

	}

	// fetch the connected stage form
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
			
		}

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
				'fields'		=>	array(),
				'field_values'	=>	array(),
				'completed'		=>	false,
				'current_form'	=>	$form['ID']
			);
			cf_form_connector_set_current_position( $process_record );
		}
	}

	// disable mailer
	$form['mailer']['enable_mailer'] = false;
	// setup each connection condition
	$final_form_fields = array();
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
		$hasBack = false;
		if( !empty( $condition['back'] ) ){
			$hasBack = true;
		}

		$form['processors'][ $processor_id ] = array(
			'ID' 			=> $processor_id,
			'type' 			=> 'form-connector',
			'config' 		=> array(
				'next_form_id'			=> $stage['node'][ $condition['connect'] ]['form'],
				'back_button'			=> $hasBack
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
		$has_condition_points = true;
	}
	
	if( empty( $has_condition_points ) && $process_record[ $stage['ID'] ]['current_form'] == $stage['ID'] ){
		// last in process!
		$form = $stage;
		$form['stage_form'] = $stage['ID'];
		// setup field values
		global $processed_data;
		$processed_data[$stage['ID']] = $process_record[ $stage['ID'] ]['field_values'];
		$form['fields'] = $process_record[ $stage['ID'] ]['fields'];
		unset( $process_record[ $stage['ID'] ]['fields'] );
		unset( $process_record[ $stage['ID'] ]['field_values'] );
		unset( $process_record[ $stage['ID'] ]['completed'] );
		unset( $process_record[ $stage['ID'] ]['current_form'] );
		unset( $process_record[ $stage['ID'] ]['previous_form'] );
		unset( $process_record[ $stage['ID'] ]['entry_id'] );
		foreach( $process_record[ $stage['ID'] ] as $form_id => $data ){
			Caldera_Forms::set_submission_meta( 'form', array( $form_id => $data['id'] ), $form, '_connected_form');
		}

	}

	return $form;

}

/**
 * Check the processors in connected forms
 *
 * @since 0.2.0
 *
 * @uses "caldera_forms_get_form"
 *
 * @param array $form The form configuration
 *
 * @return mixed
 */
function cf_form_connector_setup_processors_check( $form ){
	if( is_admin() && !empty( $form['is_connected_form'] ) ){
		// setup processors
		$form['processors']['_connected_form'] = array(
			'type'	=> 'form-connector',
			'config' => array()
		);

		// setup fields
		$form_fields = array();
		if( !empty( $_POST['action'] ) && $_POST['action'] == 'get_entry' && !empty( $_POST['entry'] ) ){
			// get meta
			$meta = Caldera_Forms::get_entry_meta( (int) $_POST['entry'] , $form );		

			if( !empty( $meta['form-connector']['data']['_connected_form']['entry']['form']['meta_value'] ) ){
				foreach( (array) $meta['form-connector']['data']['_connected_form']['entry']['form']['meta_value'] as $form_meta ){
					$form_fields = array_merge( $form_fields, $form_meta );
				}
			}
		}

		if( !empty( $form['condition_points']['forms'] ) ){
			$form['fields'] = array();
			foreach( $form['condition_points']['forms'] as $connected_id => $connected_form ){
				if( !empty( $form_fields[ $connected_id ] ) && !empty( $connected_form['fields'] ) ){
					$form['fields'] = array_merge( $form['fields'], $connected_form['fields'] );
				}
			}

			// filter the meta to include the connected meta stuff.
			add_filter( 'caldera_forms_get_entry_detail', 'cf_form_connector_setup_processors_meta', 10, 3 );
		}

	}

	return $form;
}


/**
 * Handle meta in processors of connected forms
 *
 * @since 0.2.0
 *
 * @param array $entry Entry data
 * @param int $entry_id Entry ID
 * @param array $form The form configuration
 *
 * @return mixed
 */
function cf_form_connector_setup_processors_meta( $entry, $entry_id, $form ){

	if( !empty( $entry['meta']['form-connector']['data']['_connected_form']['entry']['form']['meta_value'] ) ){
		foreach( (array) $entry['meta']['form-connector']['data']['_connected_form']['entry']['form']['meta_value'] as $form_meta ){
			foreach( $form_meta as $connected_form=>$connected_entry ){
				$meta = Caldera_Forms::get_entry_meta( $connected_entry, Caldera_Forms::get_form( $connected_form ) );
				if( !empty( $meta ) ){
					foreach ($meta as $meta_key => $meta_data ) {
						$entry['meta'][ $meta_key ] = $meta_data;
					}
				}
			}
		}
		unset( $entry['meta']['form-connector'] );
	}

	return $entry;
}


/**
 * Load connected forms within sequences
 *
 * @uses "caldera_forms_redirect"
 *
 * @param string $type Unused
 * @param string $url Unused
 * @param array $form The form configuration
 */
function cf_form_connector_control_form_load_manual($type, $url, $form ){

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
		}
	}
}

/**
 * Do something to loading of forms
 *
 * @since 0.2.0
 *
 * @uses "caldera_forms_ajax_return"
 *
 * @param string $out
 * @param array $form The form configuration
 *
 * @return mixed
 */
function cf_form_connector_control_form_load( $out, $form ){
	
	if( $out['type'] !== 'complete' ){
		return $out;
	}

	if( !empty( $form['stage_form'] ) ){

		$stage_form = Caldera_Forms::get_form( $form['stage_form'] );
		$process_record = cf_form_connector_get_current_position();
		
		if( !empty( $form['form_connection'] ) ){

			$process_record[ $form['stage_form'] ][ $form['ID'] ] = array(
				'id'	=> $form['form_connection']['entry_id']
			);

			if( empty( $form['form_connection']['back_button'] ) ){
				$process_record[ $form['stage_form'] ][ $form['ID'] ]['no_back'] = true;
			}
			
			if( !empty( $process_record[ $form['stage_form'] ][ 'previous_form'] ) ){
				$process_record[ $form['stage_form'] ][ $form['ID'] ]['back'] = $process_record[ $form['stage_form'] ][ 'previous_form'];
			}
			$process_record[ $form['stage_form'] ][ 'previous_form'] = $form['ID'];
			$process_record[ $form['stage_form'] ][ 'current_form'] = $form['form_connection']['next_form_id'];
			$process_record[ $form['stage_form'] ][ 'fields' ] = array_merge( ( array ) $process_record[ $form['stage_form'] ]['fields'], $form['fields'] );
			$process_record[ $form['stage_form'] ][ 'field_values' ] = array_merge( ( array ) $process_record[ $form['stage_form'] ]['field_values'], Caldera_Forms::get_submission_data( $form ) );

			cf_form_connector_set_current_position( $process_record );

			// handler proper redirects
			if( !empty( $out['url'] ) ){	
				wp_send_json( $out );
			}

			wp_send_json( array( 'target' => $form['stage_form'] . '_' . (int) $_POST['_cf_frm_ct'], 'form' => Caldera_Forms::render_form( $stage_form ) ) );
		}else{
			// is current = stage ? yup last form last process.
			if( !empty( $process_record[ $form['stage_form'] ][ 'current_form'] ) && $process_record[ $form['stage_form'] ][ 'current_form'] === $form['stage_form'] ){
				
				$process_record[ $form['stage_form'] ] = array();
				cf_form_connector_set_current_position( $process_record );
				
				return $out;
			}

			$process_record[ $form['stage_form'] ][ $form['ID'] ] = array(
				'id'	=> (int) Caldera_Forms::do_magic_tags( '{entry_id}' )
			);
			if( !empty( $process_record[ $form['stage_form'] ][ 'previous_form'] ) ){
				$process_record[ $form['stage_form'] ][ $form['ID'] ]['back'] = $process_record[ $form['stage_form'] ][ 'previous_form'];
			}
			$process_record[ $form['stage_form'] ][ 'previous_form'] = $form['ID'];
			$process_record[ $form['stage_form'] ][ 'current_form'] = $form['stage_form'];
			$process_record[ $form['stage_form'] ]['fields'] = array_merge( ( array ) $process_record[ $form['stage_form'] ]['fields'], $form['fields'] );
			$process_record[ $form['stage_form'] ]['field_values'] = array_merge( ( array ) $process_record[ $form['stage_form'] ]['field_values'], Caldera_Forms::get_submission_data( $form ) );

			cf_form_connector_set_current_position( $process_record );

			Caldera_Forms::process_submission();
			exit;
		}

	}

	return $out;

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
				if( !empty( $process_record[ $form['ID'] ][ $process_record[ $form['ID'] ]['previous_form'] ] ) && !empty( $process_record[ $form['ID'] ][ $process_record[ $form['ID'] ]['previous_form'] ]['no_back'] ) ){
					$no_back_button = true;
				}
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
		// check if there are any connection points	
		foreach( $form['condition_points']['conditions'] as $condition_point => $condition ){

			// ye, lets only use the conditions for this form
			if( $condition['form'] != $new_form['ID'] ){
				continue;
			}
			// check its there
			if( empty( $form['node'][ $condition['connect'] ] ) ){
				continue;
			}
			// yes theres a connection at least AKA not the last form
			$has_connections = true;
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
				
				if( empty( $no_back_button ) ){
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
			}
			$new_form['fields']['cffld_nextnav'] = array(
				'ID' => 'cffld_nextnav',
				'type' => 'cfcf_next_nav',
				'label' => ( !empty( $has_connections ) ? __('Next', 'cf-form-connector' ) : __('Submit', 'cf-form-connector' ) ),
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
			wp_enqueue_script( 'cf-form-connector-ajax', CF_FORM_CON_URL . 'assets/js/cf-connected-ajax.min.js', array( 'jquery' ), CF_FORM_CON_VER , true );
			$new_form['custom_callback'] = 'cf_connected_ajax_handler';
		}
		return $new_form;
	}

	return $form;
} );

/**
 * Setup form attributes to mark as a connected form.
 *
 * @since 0.2.0
 *
 * @uses "caldera_forms_render_form_attributes"
 *
 * @param array $atts

 * @param array $form The form config
 *
 * @return mixed
 */
function cf_form_connector_add_form_flag( $atts, $form ){
	if( !empty( $form['connected_stage'] ) ){

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
	if( is_admin() && !empty( $_GET['edit'] ) ){
		return $processors;
	}

	$processors['form-connector'] = array(
		"name"				=>	__('Connected Forms', 'cf-form-connector'),
		"description"		=>	__( 'Connect multiple forms.', 'cf-form-connector'),
		//"icon"				=>	CF_FORM_CON_URL . "icon.png",
		"author"			=>	"David Cramer & Josh Pollock for CalderaWP LLC",
		"author_url"		=>	"https://CalderaWP.com",
		"post_processor"	=>	'cf_form_connector_process',
		"template"			=>	CF_FORM_CON_PATH . "includes/config.php",

	);

	return $processors;

}

/**
 * Proccesss submission
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
 * @since 0.2.0
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
