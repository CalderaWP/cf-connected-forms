var cf_connected_ajax_handler;

jQuery( function( $ ){

	$( document ).on( 'click', '.cffld_backnav_btn', function(){
		var clicked = $(this),
			wrap = clicked.closest('.caldera_forms_form');
		
		wrap.find('[required]').removeAttr('required');
		$('#' + $(this).data('field') + '_input' ).val(1);
	} );


	cf_connected_ajax_handler = function( obj ){
		var target = $( '#' + obj.target ),
			inst_id = $( obj.form ).find('form.caldera_forms_form').prop('id');
		target.replaceWith( obj.form );
		var $newForm =  $( document.getElementById( inst_id ) );

		//scroll to top and focus first field
		$('html, body').animate({
			scrollTop: $newForm.offset().top - 200
		}, 750, function() {
			$newForm.find( 'input:visible:enabled:first' ).focus();
		});

		if( typeof caldera_conditionals === "undefined" || typeof caldera_conditionals[inst_id] === "undefined"){
			return;
		}
		if( typeof calders_forms_init_conditions === 'function'){
			calders_forms_init_conditions();
		}
	}
});