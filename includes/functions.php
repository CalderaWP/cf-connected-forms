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
//Most hooks, if added will break advanced file fields submission, so don't use on those
//SEE: https://github.com/CalderaWP/cf-connected-forms/issues/23
if ( empty( $_POST[ 'control' ] ) ) {
	add_filter( 'caldera_forms_render_entry_id', 'cf_form_connector_get_stage_state', 10, 2 );
	add_action( 'init', 'cf_form_connector_init_current_position' );
	add_filter( 'caldera_forms_get_form', 'cf_form_connector_setup_processors_check' );
	add_filter( 'caldera_forms_submit_get_form', 'cf_form_connector_setup_processors' );
	add_filter( 'caldera_forms_ajax_return', 'cf_form_connector_control_form_load', 10, 3 );
	add_action( 'caldera_forms_redirect', 'cf_form_connector_control_form_load_manual', 25, 3 );
	add_action( 'admin_init', 'cf_connected_form_init_license' );
	add_action( 'init', 'cf_form_connector_export_merge' );
	add_filter( 'caldera_forms_pre_do_bracket_magic', 'cf_form_connector_prev_magic_tag', 25, 5 );
	add_action( 'cf_form_connector_sequence_started', array( 'CF_Con_Form_Partial', 'status_hook' ), 8, 2 );
	add_action( 'cf_form_connector_sequence_advanced', array( 'CF_Con_Form_Partial', 'add_values_to_main_entry' ), 8, 4  );
}

//this one is for advanced file fields
add_filter( 'caldera_forms_get_form', 'cf_conn_form_switch_form_for_file_upload' );

//Possibly replace standard response
add_action( 'wp_ajax_get_entry', 'cf_form_connector_view_entry', 2 );

/**
 * Make sure we update ID in tracking data.
 *
 * @since 1.1.1
 */
add_action( 'caldera_forms_submit_process_end', function( $form, $referrer, $process_id, $entry_id ){
	if( isset ( $_GET[ 'con_current' ], $_GET[ 'con_base' ] ) ){
		$current = $_GET[ 'con_current' ];
		$base = $_GET[ 'con_base' ];
		if( $current != $form[ 'ID' ] ){
			return;
		}
	}else{
		return;
	}


	cf_form_connected_write_id_to_progress( $entry_id, $base, $current );
}, 25, 4 );


add_filter( 'caldera_forms_get_form', 'cf_connected_form_merge_fields_filter', 1 );
function cf_connected_form_merge_fields_filter( $form ){
	if( ! empty( $form [ 'is_connected_form' ]) ) {
		if ( ! empty( $form[ 'node' ] ) ) {
			remove_filter( 'caldera_forms_get_form', 'cf_connected_form_merge_fields_filter', 1 );

			$form[ 'fields' ] = array();
			$form[ 'fields' ] = cf_form_connector_get_all_fields( $form );

		}

		add_filter( 'caldera_forms_get_form', 'cf_connected_form_merge_fields_filter', 1 );

	}
	return $form;
}

/**
 * Initializes the licensing system
 *
 * @uses "admin_init" action
 *
 * @since 0.2.0
 */
function cf_connected_form_init_license(){

	$plugin = array(
		'name'		=>	'Connected Caldera Forms',
		'slug'		=>	'caldera-forms-connector',
		'url'		=>	'https://calderawp.com/',
		'version'	=>	CF_FORM_CON_VER,
		'key_store'	=>  'cf_connected_forms',
		'file'		=>  CF_FORM_CON_CORE,
	);

	new \calderawp\licensing_helper\licensing( $plugin );

}

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
	if ( isset( $_POST, $_POST[ 'data' ] ) ) {
		parse_str( $_POST[ 'data' ], $newform );
		if ( ! empty( $newform[ 'connected_form_primary' ] ) ) {
			$form[ 'is_connected_form' ] = true;
		}
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
		$form = \Caldera_Forms_Forms::get_form( $_GET['edit'] );
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

			//$panels['form_layout']['tabs']['layout']['canvas'] = CF_FORM_CON_PATH . 'includes/templates/partial.php';
			$panels['form_layout']['tabs'][ 'partial' ] = array(
				'name' => __( 'Partial Submissions', 'connected-forms' ),
				'label' => __( 'Settings for partial submissions', 'connected_Forms' ),
				'location' => 'lower',
				'actions' => array(),
				'side_panel' => null,
				'canvas' => CF_FORM_CON_PATH . 'includes/templates/partials.php'
			);

			// add scripts
			wp_enqueue_script( 'jsplumb', CF_FORM_CON_URL . 'assets/js/jsPlumb-1.7.10-min.js', array(), CF_FORM_CON_VER );
			wp_enqueue_script( 'connector-ui', CF_FORM_CON_URL . 'assets/js/connector-ui.min.js', array('jsplumb'), CF_FORM_CON_VER );
			wp_enqueue_style( 'connector-ui', CF_FORM_CON_URL . 'assets/css/connector-ui.css', array(), CF_FORM_CON_VER );

		}
	}

	return $panels;

});

/**
 * Add a magic tag for previous values
 *
 * @uses "caldera_forms_do_magic_tag"
 * @deprecated 1.1.0
 *
 * @since 1.0.4
 */
function cf_form_connector_magic_tag( $tag ) {
	_deprecated_function( __FUNCTION__, '1.1.0' );
	global $form;
	
	$parts = explode( ':', $tag );
	if( count( $parts ) !== 2 || $parts[0] !== 'prev' ){
		return $tag;
	}
	$check_field = $parts[1];

	$position = cf_form_connector_get_current_position();
	
	if( empty( $form['ID'] ) || empty( $position[ $form['ID'] ] ) ){
		return $tag;
	}

	$place = $position[ $form['ID'] ];
	
	if( isset( $place['field_values'][ $check_field ] ) ){
		return $place['field_values'][ $check_field ];
	}
	// lookup slug
	foreach( $place['fields'] as $field_id => $field ){
		
		if( $field['slug'] === $check_field && isset( $place['field_values'][ $field_id ] ) ){
			return $place['field_values'][ $field_id ];
		}
	}

	return $tag;

}

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
	$base_form = Caldera_Forms_Forms::get_form( cf_form_connector_get_base_form( $form ) );

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
	$form = Caldera_Forms_Forms::get_form( $current_form );
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

			$return_data = cf_form_connector_return_data( $current_form, $stage[ 'ID' ], $process_record[ $stage['ID'] ][ 'entry_id' ], 'back' );
			// check for ajax
			wp_send_json( array_merge( array(
				'target' => $stage[ 'ID' ] . '_' . (int) $_POST[ '_cf_frm_ct' ],
				'form'   => Caldera_Forms::render_form( $stage )
			), $return_data ) );

			
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

			$entry = new Caldera_Forms_Entry_Entry( (object) $new_entry );
			$e = new Caldera_Forms_Entry( $stage, false, $entry );
			$e->save();
			$entry_id = $e->get_entry_id();
			$process_record = array();
			$process_record[ $stage['ID'] ] = array(
				'entry_id' 		=>	$entry_id,
				'fields'		=>	array(),
				'field_values'	=>	array(),
				'completed'		=>	false,
				'current_form'	=>	$form['ID']
			);
			cf_form_connector_set_current_position( $process_record );

			/**
			 * Runs when the first step in a Connected Form is submitted
			 *
			 * @since 1.1.0
			 *
			 * @param string $connected_form_id ID of connected form
			 * @param int $entry_id If of entry
			 */
			do_action( 'cf_form_connector_sequence_started', $stage['ID'], $entry_id );
		}
	}

	// disable mailer
	$form['mailer']['enable_mailer'] = false;
	$form['mailer']['on_insert'] = false;
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

		$next_form_id = $stage['node'][ $condition['connect'] ]['form'];
		$_entry_id = null;
		if( isset( $process_record[ $next_form_id ], $process_record[ $next_form_id ][ 'id' ] ) ){
			$_entry_id = $process_record[ $next_form_id ][ 'id' ];
		}
		$form['processors'][ $processor_id ] = array(
			'ID' 			=> $processor_id,
			'type' 			=> 'form-connector',
			'config' 		=> array(
				'next_form_id' => $next_form_id,
				'back_button'  => $hasBack,
				'entry_id'     => $_entry_id
			),
			'runtimes' => array(
				'insert' => true
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
		CF_Con_Form_PTrack::set_config( $form['processors'][ $processor_id ][ 'config' ] );
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

		$process_record[ $form['stage_form'] ] = array();
		cf_form_connector_set_current_position( $process_record );

	}

	return $form;

}

/**
 * When sequence is completed - save
 *
 * @since 1.0.8
 *
 * @param array $connected_form Connected form config
 * @param array $data Field data to save
 * @param array $fields All field configs for fields to save.
 */
function cf_form_connector_save_final( $connected_form, $data, $fields, $entry_id ){
	$form = $connected_form;
	$form ['fields' ] = $fields;

	/**
	 * Change data to save in Connected Forms entry
	 *
	 * Runs when sequence is completed, before data is saved
	 *
	 * @since 1.1.0
	 *
	 * @param array $data Values for each field in sequence
	 * @param array $form Form config for connected form
	 * @param int $entry_id ID of entry
	 */
	$data = apply_filters( 'cf_form_connector_sequence_complete_pre_save', $data, $form, $entry_id );
	if( is_array( $entry_id ) ){
		if( isset( $entry_id[ '_entry_id' ] ) ){
			$entry_id = $entry_id[ '_entry_id' ];
		}else{
			$entry_id = null;
		}
	}
	if ( ! $entry_id ) {
		Caldera_Forms_Save_Final::create_entry( $form, $data );
	}else{
		global $processed_data;
		$processed_data[ $form[ 'ID' ] . '_' . $entry_id ] = $data;
		$entry = new Caldera_Forms_Entry( $form, $entry_id );
		$entry = cf_form_connector_add_fields_to_entry(  $entry, $fields, $data );

		$entry->save();
		Caldera_Forms_Entry_Update::update_entry_status( 'active', $entry_id );

	}

	/**
	 * Runs after a connected forms sequence is completed and saved in database
	 *
	 * @since 1.1.0
	 *
	 * @param array $form Form config for connected form
	 * @param array $data Values for each field in sequence
	 * @param int $entry_id ID of entry
	 */
	do_action( 'cf_form_connector_sequence_complete', $form, $data, $entry_id );
}

/**
 * Add fields to entry object
 *
 * NOTE: Does not actually save data
 *
 * @since 1.1.0
 *
 * @param Caldera_Forms_Entry $entry Entry object
 * @param array $fields Field configs for fields to save
 * @param array $field_data Data for each field, keyed by field ID.
 *
 * @return Caldera_Forms_Entry
 */
function cf_form_connector_add_fields_to_entry( Caldera_Forms_Entry $entry, array $fields, array $field_data ) {
	$entry_id = $entry->get_entry_id();
	foreach ( $field_data as $field_id => $value ) {
		if ( ! isset( $fields[ $field_id ] ) ) {
			continue;
		}

		$field = $fields[ $field_id ];
		if ( Caldera_Forms_Fields::not_support( Caldera_Forms_Field_Util::get_type( $field ), 'entry_list' ) ) {
			continue;
		}
		$slug        = $field[ 'slug' ];
		$_value      = array(
			'entry_id' => $entry_id,
			'value'    => $value,
			'field_id' => $field_id,
			'slug'     => $slug

		);
		$field_value = new Caldera_Forms_Entry_Field( (object) $_value );
		$entry->add_field( $field_value );

	}

	return $entry;

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
			'runtimes' => array(
				'insert' => true
			),
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
	}elseif( !empty( $form['is_connected_form'] ) ){
		wp_enqueue_script( 'cf-conditionals' );
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
				$meta = Caldera_Forms::get_entry_meta( $connected_entry, Caldera_Forms_Forms::get_form( $connected_form ) );
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
		$stage_form = Caldera_Forms_Forms::get_form( $form['stage_form'] );
		$process_record = cf_form_connector_get_current_position();
		$form = CF_Con_Form_PTrack::maybe_add_connections( $form );
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
 * @param $form
 *
 * @return mixed
 */
function cf_form_connector_maybe_add_connections( $form )
{
	if ( empty( $form[ 'form_connection' ] ) ) {
		$_processor = CF_Con_Form_PTrack::get_config();
		if ( ! empty( $_processor ) ) {
			$form[ 'form_connection' ] = $_processor[ 'config' ];


		}

	}

	return $form;
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
		$stage_form = Caldera_Forms_Forms::get_form( $form['stage_form'] );
		$process_record = cf_form_connector_get_current_position();
		$process_record[ $form['stage_form'] ][ 'fields' ] = array_merge( ( array ) $process_record[ $form['stage_form'] ]['fields'], $form['fields'] );
		$process_record[ $form['stage_form'] ][ 'field_values' ] = array_merge( ( array ) $process_record[ $form['stage_form'] ]['field_values'], Caldera_Forms::get_submission_data( $form ) );
		$form = CF_Con_Form_PTrack::maybe_add_connections( $form );
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


			cf_form_connector_set_current_position( $process_record );

			// handler proper redirects
			if( !empty( $out['url'] ) ){	
				wp_send_json( $out );
			}

			$connected_form_id = $stage_form[ 'ID' ];
			$entry_id = $process_record[ $connected_form_id ][ 'entry_id' ];

			/**
			 * Runs when a form in a Connected Forms sequence, which is not the last form is submitted
			 *
			 * @since 1.1.0
			 *
			 * @param string $connected_form_id ID of connected form
			 * @param string $current_form_id The ID of the form in sequence that was just submitted
			 * @param int $entry_id If of entry
			 * @param array $sequence_data Data for current sequence
			 */
			do_action( 'cf_form_connector_sequence_advanced', $connected_form_id, $form[ 'ID' ], $entry_id, $process_record[ $connected_form_id ] );

			$return_data = cf_form_connector_return_data( $form[ 'ID' ], $connected_form_id, $entry_id );
			$return_data = array_merge( array(
				'target' => $form[ 'stage_form' ] . '_' . (int) $_POST[ '_cf_frm_ct' ],
				'form'   => Caldera_Forms::render_form( $stage_form ),
			), $return_data );
			wp_send_json( $return_data );
		}else{
			// is current = stage ? yup last form last process.
			if( empty( $form['form_connection'] )
			   || ( !empty( $process_record[ $form['stage_form'] ][ 'current_form'] ) && $process_record[ $form['stage_form'] ][ 'current_form'] === $form['stage_form'] ) )
			{
				


				$connected_form = Caldera_Forms_Forms::get_form( $form['stage_form'] );
				if( is_array( $connected_form ) && ( ! empty(  $connected_form['mailer']['enable_mailer'] ) || $connected_form['mailer']['on_insert'] ) ){

					$entry_id = $process_record[ $connected_form[ 'ID' ] ][ 'entry_id' ];
					$data =  $process_record[ $form['stage_form'] ][ 'field_values' ];
					cf_form_connector_save_final( $connected_form, $data, $process_record[ $connected_form[ 'ID' ] ][ 'fields' ], $entry_id );

					$process_record[ $form['stage_form'] ] = array();
					cf_form_connector_set_current_position( $process_record );
					if (  class_exists( 'Caldera_Forms_Magic_Summary' ) ) {
						$message_setting = $connected_form[ 'mailer' ][ 'email_message' ];
						if ( false !== strpos( $message_setting, '{summary}' ) ) {
							$magic_parser = new Caldera_Forms_Magic_Summary( $connected_form, $data );
							if ( ! isset( $connected_form[ 'mailer' ][ 'email_type' ] ) || $connected_form[ 'mailer' ][ 'email_type' ] == 'html' ) {
								$magic_parser->set_html_mode( true );

							} else {
								$magic_parser->set_html_mode( false );
							}

							$magic_parser->set_fields( cf_form_connector_get_all_fields( $connected_form ) );

							$message_setting                               = str_replace( '{summary}', $magic_parser->get_tag(), $message_setting );
							$connected_form[ 'mailer' ][ 'email_message' ] = $message_setting;
						}
					}



					Caldera_Forms_Save_Final::do_mailer( $connected_form, $entry_id, $data );
				}
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

/**
 * Prepare filterable parts of AJAX return
 *
 * @since 1.1.1
 *
 * NOTE: Add target and form indexes after this or shit will break.
 *
 * @param string $last_form_id Last form ID
 * @param string $connected_form_id Connected form ID
 * @param int $entry_id Entry ID for connected form
 * @param string $type Optional. Type of return. Default is "advance"
 * @return array
 */
function cf_form_connector_return_data( $last_form_id, $connected_form_id, $entry_id, $type = 'advance' ){
	$return_data = array(
		'connected_form_id' => $connected_form_id,
		'last_form_id'      => $last_form_id,
		'entry_id'          => $entry_id,
		'type'              => $type
	);

	/**
	 * Filter data to be sent back to DOM by connected forms
	 *
	 * Use to customize data for cf.connected JS event. DOES NOT include target and form which intentionally not filterable
	 *
	 * @since 1.1.1
	 *
	 * @param array $return_data Data to be sent back to DOM
	 *
	 */
	return apply_filters( 'cf_form_connector_return_data', $return_data );
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
		$new_form = false;

		$base_form = cf_form_connector_get_base_form( $form );


		if ( empty( $_POST ) && method_exists( 'Caldera_Forms_Forms', 'get_fields') ) {
			$field_types_in_sequence = array();
			$field_types = apply_filters( 'caldera_forms_get_field_types', array() );
			foreach ( $form[ 'node' ] as $node ) {
				$_form = Caldera_Forms_Forms::get_form( $node[ 'form' ] );
				$fields = Caldera_Forms_Forms::get_fields( $_form );
				foreach( $fields as $id => $field ){
					$type = Caldera_Forms_Field_Util::get_type( $field,$_form );
					if( ! array_key_exists( $type, $field_types_in_sequence ) ){
						$field_types_in_sequence[ $type ] = $field;
					}

				}

			}

			if( ! empty( $field_types_in_sequence ) ){
				foreach (  $field_types_in_sequence  as $type  => $field  ) {

					Caldera_Forms_Render_Assets::enqueue_field_scripts( $field_types, $field);
				}
			}
		}

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
			$new_form = Caldera_Forms_Forms::get_form( $process_record[ $form['ID'] ]['current_form'] );
			if( !empty( $process_record[ $form['ID'] ][ $new_form['ID'] ]['pre_data'] ) && empty( $process_record[ $form['ID'] ][ $new_form['ID'] ]['id'] ) ){
				add_filter( 'caldera_forms_render_pre_get_entry', 'cf_form_connector_partial_populate_form', 10, 2 );
			}
		}

		if( empty( $new_form ) ){
			// not form replacement, load up base
			$new_form = Caldera_Forms_Forms::get_form( $base_form );
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


		if ( isset( $new_form[ 'layout_grid' ], $new_form[ 'layout_grid' ][ 'structure' ] ) ) {
			$pages = explode( '#', $new_form['layout_grid']['structure'] );
		}else{
			$pages = array();
		}

		$submit_button_position = false;
		foreach ( $new_form[ 'fields' ] as $field_id => $field ) {
			// remove any submit buttons

			if ( $field[ 'type' ] == 'button' && $field[ 'config' ][ 'type' ] == 'submit' ) {
				$submit_button_position = $new_form[ 'layout_grid' ][ 'fields' ][ $field_id ];
				unset( $form[ 'fields' ][ $field_id ] );
				unset( $new_form[ 'layout_grid' ][ 'fields' ][ $field_id ] );
			}

		}

		//Remove all submit/back/next buttons
		//No multi-page forms and no seperate submits
		foreach( Caldera_Forms_Forms::get_fields( $new_form, false ) as $field_id => $field ){
			if( 'button' == Caldera_Forms_Field_Util::get_type( $field, $new_form ) && in_array( $field[ 'config' ][ 'type' ], array(
					'submit',
					'back',
					'next',
				) ) ){
				unset( $new_form[ 'fields' ][ $field_id ] );
			}

		}

		$rows     = explode( '|', $new_form[ 'layout_grid' ][ 'structure' ] );
		$last_row = count( $rows ) + 1;
		if(count( $pages ) <= 1 ) {
			// single page- add to this one
			$new_form[ 'layout_grid' ][ 'structure' ] .= '|6:6';
			$submit_button_position = $last_row . ':2';

		}
		if( empty( $submit_button_position ) ){
			$submit_button_position = $last_row . ':2';

		}

		$new_form[ 'layout_grid' ][ 'fields' ][ 'cffld_nextnav' ] = $submit_button_position;



		$new_form[ 'layout_grid' ][ 'fields' ][ 'cffld_stage' ]   = $last_row . ':2';

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



		// setup the js handler if ajax
		wp_enqueue_script( 'cf-form-connector-ajax', CF_FORM_CON_URL . 'assets/js/cf-connected-ajax.min.js', array( 'jquery' ), CF_FORM_CON_VER , true );
		$new_form['custom_callback'] = 'cf_connected_ajax_handler';

		//always use ajax
		$new_form[ 'form_ajax' ] = 1;
		$form[ 'form_ajax' ] = 1;

		return $new_form;
	}

	return $form;
} );

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
	$form['form_connection'] = $config;
	$form['form_connection']['entry_id'] = Caldera_Forms::get_field_data( '_entry_id', $form );
	CF_Con_Form_PTrack::set_config($form['form_connection']);
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
		$_form = Caldera_Forms_Forms::get_form( Caldera_Forms_Sanitize::sanitize( $_GET[ 'cf_con_form_id' ] ) );
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
		"field"		=>	__( "Back Navigation --- Connected Forms", 'cf-form-connector' ),
		"file"		=>	CF_FORM_CON_PATH . "includes/templates/back_field.php",
		"category"	=>	__("Button", "cf-form-connector"),
		"description" => __( 'Move Backwards In A Connected Forms Sequence', 'cf-form-connector' ),
		"setup"		=>	array(
			"not_supported"	=>	array(
				'entry_list'
			)
		)
	);
	$fieldtypes['cfcf_next_nav'] = array(
		"field"		=>	__( "Forward Navigation --- Connected Forms", 'cf-form-connector' ),
		"file"		=>	CF_FORM_CON_PATH . "includes/templates/next_field.php",
		"category"	=>	__("Button", "cf-form-connector"),
		"description" => __( 'Move Forwards In A Connected Forms Sequence', 'cf-form-connector' ),
		"setup"		=>	array(
			"not_supported"	=>	array(
				'entry_list'
			)
		)
	);

	return $fieldtypes;
}

/**
 * Merge entries during export
 *
 * @uses init
 *
 * @since 1.0.2
 */
function cf_form_connector_export_merge(){
	if( is_admin() && isset( $_GET['export'], $_GET[ 'page' ] ) &&  $_GET['export'] && 'caldera-forms' == $_GET[ 'page' ] ){
		$id = strip_tags( $_GET[ 'export' ] );
		$form = Caldera_Forms_Forms::get_form( $id );
		global $cf_con_fields;
		$cf_con_fields = array();
		if( isset( $form[ 'is_connected_form' ] ) && isset( $form[ 'node' ]) ){
			$cf_con_fields = cf_from_connector_merge_fields( $form );

		}

		add_filter( 'caldera_forms_get_form', function( $form, $id ){
			if( $_GET[ 'export' ] == $id && !empty( $form['is_connected_form'] ) ){
				global $cf_con_fields;
				$form[ 'fields' ] = $cf_con_fields;
			}

			return $form;
		}, 50, 2 );


	}

}

function cf_from_connector_merge_fields( $connected_form ){
	$merged_fields = array();
	foreach( array_reverse( $connected_form[ 'node' ] ) as $node ){
		$_id = $node[ 'form' ];
		$_form = Caldera_Forms_Forms::get_form( $_id );
		if( method_exists( 'Caldera_Forms_Forms', 'get_fields')){
			$_fields = Caldera_Forms_Forms::get_fields( $_form, false );
		}else{
			$_fields = $_form[ 'fields' ];
		}

		if( empty( $merged_fields ) ){
			$merged_fields = $_fields;
		}else{
			$merged_fields = array_merge( $merged_fields, $_fields );
		}
	}

	return $merged_fields;
}

/**
 * Validate form config
 *
 * This should probably go in CF itself
 *
 * @since 1.0.5
 */
add_filter( 'caldera_forms_get_form', function( $form ){
	if( ! isset( $form[ 'is_connected_form' ] ) ){
		$form[ 'is_connected_form' ] = false;
	}

	foreach( array( 'processors','layout_grid', 'fields', 'mailer' ) as $key ){
		if( ! isset( $form[ $key ] ) ){
			$form[ $key ] = array();
		}
	}

	return $form;
});

/**
 * Add query args to submit url to identify as connected forms and what forms are involvesd
 *
 * @since 1.0.6
 */
add_filter( 'caldera_forms_submission_url', function( $url, $form_id ){
	$form = Caldera_Forms_Forms::get_form( $form_id );
	if( !empty( $form['is_connected_form'] ) ){
		$positon = cf_form_connector_get_current_position();

		$current = $form_id;
		if( ! empty( $positon[ $form_id ] ) ){
			$current = $positon[ $form_id ][ 'current_form' ];
		}
		$url = add_query_arg( array( 'con_current' => $current, 'con_base' => $form_id ), $url  );
	}

	return $url;
}, 50, 2);



/**
 * When submitting a advanced fiel field in a connected form, change form ID from connectef form to current form
 *
 * @since 1.0.6
 *
 * @uses "caldera_forms_get_form" action
 *
 * @param $form
 *
 * @return array|null
 */
function cf_conn_form_switch_form_for_file_upload( $form ){
	if( isset( $_REQUEST[ 'control' ], $_REQUEST[ 'con_current' ] ) ){
		set_query_var( 'cf_api', $_REQUEST[ 'con_current' ] );

		remove_filter( 'caldera_forms_get_form', __FUNCTION__ );
		$form = Caldera_Forms_Forms::get_form( $_REQUEST[ 'con_current' ] );
	}

	return $form;
}


add_filter( 'caldera_forms_get_field_order', 'cf_conn_form_set_order_for_email', 25, 2 );
function cf_conn_form_set_order_for_email( $order, $form ){
	if( isset( $form[ 'node' ] ) ){
		$fields = cf_form_connector_get_all_fields( $form );

		if( ! empty( $fields ) ){
			$order = array_keys( $fields );
		}
	}

	return $order;
}

/**
 * Get all fields of forms in sequence
 *
 * @since 1.0.8
 *
 * @param array $connected_form Connected form config
 *
 * @return array
 */
function cf_form_connector_get_all_fields( $connected_form ){
	$fields = array();

	if( isset( $connected_form[ 'node' ] ) ) {

		foreach ( $connected_form[ 'node' ] as $connected_form ) {
			$_form  = Caldera_Forms_Forms::get_form( $connected_form[ 'form' ] );
			$fields = array_merge( Caldera_Forms_Forms::get_fields( $_form ), $fields );
		}

	}

	return $fields;

}

/**
 * Override entry view UI for Connected Forms
 *
 * THIS IS A TERRIBLE HACK, I feel bad, it feels bad, it is bad. Also, it works.
 *
 * @since 1.0.8
 *
 *  @uses "wp_ajax_get_entry" action
 */
function cf_form_connector_view_entry(){
	if( isset( $_POST, $_POST[ 'nonce' ], $_POST[ 'entry'], $_POST[ 'form' ] ) ){

		$form = Caldera_Forms_Forms::get_form( strip_tags( $_POST[ 'form' ] ) );

		if( empty( $form[ 'is_connected_form'] ) ){
			return;
		}

		remove_action( 'wp_ajax_browse_entries', array( Caldera_Forms_Entry_UI::get_instance(), 'view_entries' ) );

		$form[ 'fields' ] = cf_form_connector_get_all_fields( $form );

		if( ! current_user_can( Caldera_Forms::get_manage_cap( 'entry-view', $form )  ) || ! wp_verify_nonce( $_POST[ 'nonce' ], 'cf_view_entry' ) ){
			wp_send_json_error( $_POST );
		}

		$entry_id = absint( $_POST[ 'entry' ] );

		if( 0 < $entry_id && is_array( $form ) ){
			add_filter( 'caldera_forms_view_field_checkbox', function( $field_value, $field, $form  ){
				//see: https://github.com/CalderaWP/Caldera-Forms/issues/1090
				$_field_value = maybe_unserialize( str_replace( '&quot;', '"', $field_value ) );
				if( is_array( $_field_value ) ){
					$field_value = '';
					foreach ( $_field_value as $opt => $opt_value ){
						$field_value .= $field[ 'config' ][ 'option' ][ $opt ][ 'label' ];
					}


				}
				return $field_value;
			}, 25, 3 );
			$entry = Caldera_Forms::get_entry( $entry_id, $form );
			if( is_wp_error( $entry ) ){
				wp_send_json_error( $entry );
			}else{
				status_header( 200 );
				wp_send_json( $entry );
			}
		}else{
			wp_send_json_error( $_POST );
		}



	}

	wp_send_json_error( $_POST );

}

/**
 * Parse {prev:*} magic tag
 *
 * @since 1.1.0
 *
 * @uses "caldera_forms_pre_do_bracket_magic" filter
 *
 * @param $return_value
 * @param $tag
 * @param $magics
 * @param $entry_id
 * @param $form
 *
 * @return mixed
 */
function cf_form_connector_prev_magic_tag( $return_value, $tag, $magics, $entry_id, $form ){
	if( 2 != count( $magics ) || ! isset( $magics[1][0] ) || false === strpos( $tag, '{prev' ) ){
		return $return_value;
	}
	$parts = explode( ':', $magics[1][0] );
	if (  empty( $parts[1] ) ) {
		return $return_value;
	}

	$slug_or_id = $parts[1];

	$tracking = cf_form_connector_get_current_position();
	if( isset( $tracking[ $form[ 'ID'] ]  ) ){
		$sequence = $tracking[ $form[ 'ID'] ];
		foreach ( $sequence[ 'fields' ] as $field_id => $field ){
			if( $slug_or_id === $field[ 'slug' ] ){
				if( isset( $sequence[ 'field_values' ][ $field['ID'] ] ) ){
					return  $sequence[ 'field_values' ][ $field['ID'] ];

				}
			}

		}

		foreach ( $sequence[ 'field_values' ] as $field_id => $value ){
			if( $slug_or_id == $field_id ){
				return $value;

			}

		}

	}





	return $return_value;

}

/**
 * Get field data for one form in connected form sequence
 *
 * @since 1.1.0
 *
 * @param array $sequence_data Current data for sequence
 * @param string $current_form_id Current form ID
 *
 * @return array
 */
function cf_form_connector_current_fields( $sequence_data, $current_form_id ){
	if( ! isset( $sequence_data[ $current_form_id ] ) ){
		return array();
	}
	$form = Caldera_Forms_Forms::get_form( $current_form_id );
	if( empty( $form ) ){
		return array();
	}
	$fields = Caldera_Forms_Forms::get_fields( $form, false );
	$field_ids = wp_list_pluck( $fields, 'ID' );
	$field_values = array();
	foreach ( $sequence_data[ 'field_values'] as $field_id => $value ){
		if( in_array( $field_id, $field_ids ) ){
			$field_values[ $field_id ] = $value;
		}
	}

	return $field_values;


}

/**
 * Write the entry ID to sequence data
 *
 * @since 1.1.1
 *
 * @param int $entry_id Entry ID of sub-form
 * @param string $base ID of connected form
 * @param string $current ID of connected form.
 */
function cf_form_connected_write_id_to_progress( $entry_id, $base, $current ){
	$process_record = cf_form_connector_get_current_position();
	if ( ! empty( $process_record ) && isset( $process_record[ $base ] ) ) {
		if ( ! isset( $process_record[ $base ][ $current ] ) ) {
			$process_record[ $base ][ $current ] = array();
		}
		$process_record[ $base ][ $current ][ 'id' ] = $entry_id;
	}
	cf_form_connector_set_current_position( $process_record );
}


/**
 * Make sure that the extra fields we add for progress are always hidden
 *
 * @since 1.1.1
 */
add_filter( 'caldera_forms_field_attributes', function( $attrs, $field, $form){
	if( isset( $attrs[ 'data-field' ] ) && 'cffld_stage' == $attrs[ 'data-field' ] ){
		$attrs[ 'type' ] = 'hidden';
	}
	return $attrs;
}, 10, 3 );
