var cf_connected_ajax_handler;

jQuery( function( $ ){

	$( document ).on( 'click', '.cffld_backnav_btn', function(){
		var clicked = $(this),
			wrap = clicked.closest('.caldera_forms_form');
		
		wrap.find('[required]').removeAttr('required');
		$('#' + $(this).data('field') + '_input' ).val(1);
	} );


	cf_connected_ajax_handler = function( obj ){
		var target = $( '#' + obj.target );
		target.replaceWith( obj.form );
		calders_forms_init_conditions();
	}
});