var cf_connected_ajax_handler;

jQuery( function( $ ){

	$( document ).on( 'click', '.cffld_backnav_btn', function(){
		var $clicked = $(this),
			$form = $clicked.closest('.caldera_forms_form');

		//remove required
		$form.find('[required]').removeAttr('required');

		//set the back nav field to true
		$('#' + $clicked.data('field') + '_input' ).val(1);


		//submit
		$form.submit();
	} );

	cf_connected_ajax_handler = function( obj ){
		$( document ).trigger( 'cf.connected', obj );
		var $target = $( '#' + obj.target ),
			inst_id = $( obj.form ).find('form.caldera_forms_form').prop('id');
		$target.replaceWith( obj.form );
		var $newForm =  $( document.getElementById( inst_id ) );

		//scroll to top and focus first field
		if ( $newForm.length) {
			$('html, body').animate({
				scrollTop: $newForm.offset().top - 200
			}, 750, function () {
				$newForm.find('input:visible:enabled:first').focus();
			});
		}

		if( obj.hasOwnProperty( 'field_config' ) ){
			$( obj.footer_append ).appendTo( 'body' );

			//reinit state
			var state = new CFState( obj.form_instance, $ );
			state.init( obj.field_config.fields.defaults, obj.field_config.fields.calcDefaults );
			window.cfstate[ obj.form_id ] = state;

			//reinit field config
			config_object = new Caldera_Forms_Field_Config( obj.field_config.configs, $(document.getElementById( obj.form_id ) ), $, state );
			config_object.init();

			//check for star fields and reinit them
			if( obj.field_config.fields.hasOwnProperty( 'inputs' ) && obj.field_config.fields.inputs.length ){
				$.each( obj.field_config.fields.inputs, function( i, field ){
					if( 'star_rating' === field.type ){
						var func = field.id + '_stars';
						window[func].apply(null, [] );
					}
				})
			}
		}

		if( typeof caldera_conditionals === "undefined" || typeof caldera_conditionals[inst_id] === "undefined"){
			return;
		}
		if( typeof calders_forms_init_conditions === 'function'){
			calders_forms_init_conditions();
		}


	}
});
