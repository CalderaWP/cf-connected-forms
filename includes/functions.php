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
		"pre_processor"		=>	'cf_form_connector_process',
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
	include_once CF_FORM_CON_PATH . '/includes/CF_Form_Connector.php';
	$class = new CF_Form_Connector( $config, $form );

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
