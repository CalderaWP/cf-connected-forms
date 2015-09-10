<?php
// Connection builder
$forms = \Caldera_Forms::get_forms();
foreach( $forms as $form_id=>$form ){
	if( !empty( $form['is_connected_form'] ) ){
		unset( $forms[ $form_id ] );
		continue;
	}
	$forms[ $form_id ] = \Caldera_Forms::get_form( $form_id );
}

if( !empty( $element['condition_points']['conditions'] ) ){
    foreach( $element['condition_points']['conditions'] as $condition_point ){
        if( empty( $element['node'][ $condition_point['connect'] ] ) ){
            // dont output points for nonexistant forms
            continue;
        }
        if( $condition_point['connect'] == $condition_point['parent'] ) {
            unset( $element['node'][ $condition_point['connect'] ] );
            continue;
        }
    ?>

    <span class="condition-point" data-src="<?php echo $condition_point['id']; ?>" data-name="<?php echo $condition_point['name']; ?>" data-from="<?php echo $condition_point['parent']; ?>" data-to="<?php echo $condition_point['connect']; ?>"></span>

    <?php 
    }
}else{
    $element['condition_points'] = array();
}
?>

<input type="hidden" name="config[is_connected_form]" value="true">
<input type="hidden" id="forms-db" value="<?php echo esc_attr( json_encode( $forms ) ); ?>">

		<button type="button" class="button add-form-stage">Add Form</button>
		<hr>
        <div id="cf-forms-main" >
            <!-- forms -->

            <div class="cf-form-canvas canvas-wide flowchart-form cf-surface cf-surface-nopan" id="canvas">
            <?php
                if( !empty( $element['node'] ) ){

                    foreach( $element['node'] as $node_id=>$node ){
                        $location = explode( ',', $node['position'] );
                        
                    ?>
                    <div class="window cf-node form-node<?php if( !empty( $node['base'] ) ){ ?> start-point<?php } ?>" id="<?php echo $node_id; ?>" data-form="<?php echo $form_id; ?>" style="left: <?php echo $location[0]; ?>; top: <?php echo $location[1]; ?>;">
                        <strong><?php echo $forms[ $node['form'] ]['name']; ?></strong>
                        <input type="hidden" value="<?php echo $node['position']; ?>" name="config[node][<?php echo $node_id; ?>][position]" class="form-node-position">
                        <input type="hidden" value="<?php echo $node['form']; ?>" name="config[node][<?php echo $node_id; ?>][form]">
                        <button type="button" class="button button-small add-condition" data-form="<?php echo $node['form']; ?>" data-parent="<?php echo $node_id; ?>">
                            <span class="dashicons dashicons-plus" style="font-size: 12px; line-height: 21px;"></span>
                        </button>
                        <button type="button" class="button button-small remove-node">
                            <span class="dashicons dashicons-no" style="font-size: 12px; line-height: 21px;"></span>
                        </button>
                        <?php if( !empty( $node['base'] ) ){ ?>
                            <input type="hidden" value="true" name="config[node][<?php echo $node_id; ?>][base]">
                        <?php }  ?>
                    </div>

                    <?php
                    }
                }
            ?>
            </div>
            <!-- /forms -->
        </div>
<script>


jQuery( function( $ ){
	$( document ).on('click', '.form-node > .add-condition', function(){
		
        var id = 'end' + Math.round(Math.random() * 99866) + Math.round(Math.random() * 99866),
            endpoint = addPointHome( $(this).data('parent'), id ),
		    trigger = $('<a>', { 
    			'data-modal'		: 'addEndpoint',
    			'data-modal-title'	: 'Add Condition Point',
    			'data-template'		: '#conditions-tmpl',
    			'data-request'		: 'cf_new_condition_group',
    			'data-modal-width'	: '580',
    			'data-modal-height'	: '420',
    			'data-callback'		: 'baldrickTriggers',
    			'data-id'			:  endpoint.id,
                'data-parent'       :  endpoint.elementId,
    			'data-form'			:  $(this).data('form'),
    			'data-modal-buttons' : "Close|" + JSON.stringify( {
                    'data-before'       :  'check_node_name',
    				'data-modal-autoclose' : 'addEndpoint',
    				'data-request'	:	'cf_set_endpoint',
    				'data-id'		:  endpoint.id,
    				'class' : 'button-primary ajax-trigger'
    			} ) + ";Cancel|" + JSON.stringify( {
                    'data-modal-autoclose' : 'addEndpoint',
                    'data-endpoint'     :  id,
                    'data-request'  :   'deleteEndpoint',
                    'data-id'           :  endpoint.id,
                    'class' : 'button ajax-trigger'
                } ) + "|button delete-endpoint"

		}).baldrick().trigger('click');

	});

    $( document ).on( 'click', '.remove-node', function(){
        var data = cf_get_base_form(),
            ids = removeNode( $( this ).closest('.form-node')[0] );
            if( ids.length ){
                for( var i = 0; i < ids.length; i++ ){
                    delete data.conditions[ ids[i] ];
                }
            }
            cf_save_points( data );
    });

	$( document ).on('click', '.add-form-stage', function(){
		var trigger = $('<a>', { 
			'data-modal'		: 'addForm',
			'data-modal-title'	: 'Add Form',
			'data-template'		: '#add-form-tmpl',
			'data-request'		: '#forms-db',
			'data-type'			: 'json',
			'data-modal-width'	: '400',
			'data-modal-height'	: '600',			
			'data-callback'		: 'baldrickTriggers'
		}).baldrick().trigger('click');
		
		//add_newNode();

	});
});
function check_node_name( obj ){
    var form_fields = jQuery( '#addEndpoint_baldrickModalBody').find('[required]');
    form_fields.each( function(){
        if( ! this.value.length ){
            this.focus();
            return false;
        }
    });
    return true;
}

function add_new_endpoint( obj ){
	var data = {
		id : obj.trigger.data('id'),
		form : obj.trigger.data('form')
	};
	return data;
}
</script>
<script type="text/html" id="add-form-tmpl">	
<div style="width: 100%;" class="caldera-editor-conditions-panel">
	<ul class="active-conditions-list">
	{{#each this}}
		<li class="caldera-condition-nav caldera-forms-condition-group">
			<a style="cursor:pointer;">{{name}} <button class="button button-small ajax-trigger" data-form="{{ID}}"  data-name="{{name}}" data-request="add_newNode" style="float:right;margin:-3px;" data-modal-autoclose="addForm" type="button">Add Form</button></a>
		</li>
	{{/each}}
	</ul>
</div>
</script>
<input type="hidden" id="cf-conditions-db" name="config[condition_points]" value="<?php echo esc_attr( json_encode( $element['condition_points'] ) ); ?>"
class="ajax-trigger"
data-event="rebuild-conditions"
data-request="#cf-conditions-db"
data-type="json"
data-template="#conditions-tmpl"
data-target="#addEndpoint_baldrickModalBody"
>
<script type="text/html" id="add-endpoint-tmpl">
<button style="width:250px;" class="button ajax-trigger" data-request="cf_new_condition_group" data-template="#conditions-tmpl" data-target="#caldera-forms-conditions-panel" type="button"><?php _e( 'Add Condition', 'caldera-forms' ); ?></button>

<div id="caldera-forms-connect-conditions-panel"></div>
</script>

<script type="text/html" id="conditions-tmpl">
    <input type="hidden" name="_open_condition" value="{{_open_condition}}">
    {{#find conditions @root/_open_condition}}
        <div class="caldera-editor-condition-config caldera-forms-condition-edit" style=" width:auto;">

            {{#if form}}
                <input type="hidden" name="{{id}}[id]" value="{{id}}">
                <input type="hidden" name="{{id}}[form]" id="condition-group-form-{{id}}" value="{{form}}">
                <input type="hidden" name="{{id}}[connect]" value="{{connect}}">
                <input type="hidden" name="{{id}}[parent]" value="{{parent}}">
                <div class="condition-point-{{id}}" style="width: 550px; float: left;">
                    <div class="caldera-config-group">
                        <label for="condition-group-name-{{id}}"><?php _e( 'Name', 'caldera-forms' ); ?></label>
                        <div class="caldera-config-field">
                            <input type="text" name="{{id}}[name]" id="condition-group-name-{{id}}" data-sync="#condition-group-{{id}}" value="{{#if name}}{{name}}{{else}}{{id}}{{/if}}" required class="required block-input">
                        </div>
                    </div>
                    <div class="caldera-config-group">
                        <label for="condition-group-back-button-{{id}}"><?php _e( 'Back Button', 'caldera-forms' ); ?></label>
                        <div class="caldera-config-field">
                            <label><input id="condition-group-back-button-{{id}}" type="checkbox" name="{{id}}[back]" value="true" {{#if back}}checked="checked"{{/if}}>  <?php echo __('Enable Back Navigation', 'cf-form-connector'); ?></label>
                        </div>
                    </div>                
                    <div class="caldera-config-group">
                        <label for="condition-group-name-{{id}}"><?php echo __('Conditions', 'caldera-forms'); ?></label>
                        <div class="caldera-config-field">
                            <input type="hidden" name="{{id}}[type]" value="use">
                            <button type="button" data-add-group="{{id}}" class="pull-right button button-small"><?php echo __('Add Conditional Line', 'caldera-forms'); ?></button>
                        </div>
                    </div>
                    {{#each group}}
                        {{#unless @first}}
                            <span style="display: block; margin: 0px 0px 8px;"><?php _e( 'or', 'caldera-forms' ); ?></span>
                        {{/unless}}
                        <div class="caldera-condition-group caldera-condition-lines">
                        {{#each this}}

                            <div class="caldera-condition-line condition-line-{{@key}}">
                                <input type="hidden" name="{{../../../id}}[group][{{parent}}][{{@key}}][parent]" value="{{parent}}">
                                <span style="display:inline-block;">{{#if @first}}
                                    <?php _e( 'if', 'caldera-forms' ); ?>
                                {{else}}
                                    <?php _e( 'and', 'caldera-forms' ); ?>
                                {{/if}}</span>
                                <input type="hidden" name="{{../../../id}}[fields][{{@key}}]" value="{{field}}" id="condition-bound-field-{{@key}}" data-live-sync="true">
                                <select style="max-width:120px;vertical-align: inherit;" name="{{../../id}}[group][{{parent}}][{{@key}}][field]" data-sync="#condition-bound-field-{{@key}}">
                                    <option></option>
                                    <optgroup label="Fields">
                                    {{#find @root/forms ../../../form}}
                                        {{#each fields}}
                                            <option value="{{ID}}" {{#is ../../field value=ID}}selected="selected"{{/is}} {{#is conditions/type value=../../../../id}}disabled="disabled"{{/is}}>{{label}} [{{slug}}]</option>
                                        {{/each}}
                                    {{/find}}
                                    </optgroup>
                                    <?php /*<optgroup label="System Tags">
                                    {{#each @root/magic}}
                                        <option value="{{this}}" {{#is ../field value=this}}selected="selected"{{/is}}>{{this}}</option>
                                    {{/each}}
                                    </optgroup>*/ ?>
                                </select>
                                <select style="max-width:110px;vertical-align: inherit;" name="{{../../id}}[group][{{parent}}][{{@key}}][compare]">
                                    <option value="is" {{#is compare value="is"}}selected="selected"{{/is}}><?php _e( 'is', 'caldera-forms' ); ?>{{../compare}}</option>
                                    <option value="isnot" {{#is compare value="isnot"}}selected="selected"{{/is}}><?php _e( 'is not', 'caldera-forms' ); ?></option>
                                    <option value="isgreater" {{#is compare value="isgreater"}}selected="selected"{{/is}}><?php _e( 'is greater than', 'caldera-forms' ); ?></option>
                                    <option value="issmaller" {{#is compare value="issmaller"}}selected="selected"{{/is}}><?php _e( 'is less than', 'caldera-forms' ); ?></option>
                                    <option value="startswith" {{#is compare value="startswith"}}selected="selected"{{/is}}><?php _e( 'starts with', 'caldera-forms' ); ?></option>
                                    <option value="endswith" {{#is compare value="endswith"}}selected="selected"{{/is}}><?php _e( 'ends with', 'caldera-forms' ); ?></option>
                                    <option value="contains" {{#is compare value="contains"}}selected="selected"{{/is}}><?php _e( 'contains', 'caldera-forms' ); ?></option>
                                </select>
                                <span data-value="" class="caldera-conditional-field-value" style="padding: 0 12px 0; display:inline-block; width:200px;">

                                {{#find @root/forms ../../../form}}
                                    {{#find fields ../field}}
                                        {{#if config/option}}
                                            <select style="width:165px;vertical-align: inherit;" name="{{../../../../../id}}[group][{{../../../parent}}][{{@key}}][value]">
                                                <option></option>
                                                {{#each config/option}}
                                                    <option value="{{@key}}" {{#is ../../../../value value=@key}}selected="selected"{{/is}}>{{label}}</option>
                                                {{/each}}
                                            </select>
                                        {{else}}
                                            <input type="text" class="block-input" name="{{../../../../../id}}[group][{{../../../parent}}][{{@key}}][value]" value="{{../../../value}}" {{#unless ../../../field}}placeholder="Select field first" disabled=""{{/unless}}>
                                        {{/if}}
                                    {{else}}
                                        <input type="text" class="block-input" name="{{../../../../id}}[group][{{../../parent}}][{{@key}}][value]" value="{{../../value}}" {{#unless ../../field}}placeholder="Select field first" disabled=""{{/unless}}>
                                    {{/find}}
                                {{/find}}
                                </span>
                                <button class="button pull-right" data-remove-line="{{@key}}" type="button"><i class="icon-join"></i></button>
                            </div>
                        {{/each}}
                        <div style="margin: 12px 0 0;"><button class="button button-small" data-add-line="{{@key}}" data-group="{{../id}}" type="button"><?php _e( 'Add Condition', 'caldera-forms' ); ?></button></div>
                        </div>
                    {{/each}}

                </div>

            {{/if}}
        </div>
    {{/find}}


</script>
<script type="text/javascript">
	var cf_new_condition_line, cf_new_condition_group, cf_set_endpoint, cf_get_base_form, edit_node, remove_node, cf_save_points;



	jQuery( function( $ ){

        cf_save_points = function( data, rebuild ){
            var db = $('#cf-conditions-db') ;
            db.val( JSON.stringify( data ) );
            if( rebuild ){
                db.trigger( 'rebuild-conditions' );
            }
        }

        remove_node = function( id ){
            var data = cf_get_base_form();
            delete data.conditions[id];
            data._open_condition = null;
            cf_save_points( data );
        }
        edit_node = function( obj ){

            var db = JSON.parse( jQuery( '#cf-conditions-db' ).val() );
            db._open_condition = obj.trigger.data('id');

            return db;
        }

		cf_get_base_form = function(){
			var data_fields		= $('#cf-conditions-db').val();
            if( data_fields.length ){
                data_fields = JSON.parse( data_fields );
            }else{
                data_fields = {};
            }

			var forms = JSON.parse( $('#forms-db').val() );
			var modal_form = $('#addEndpoint_baldrickModalBody');
			if( modal_form.length ){
                var metadata = modal_form.formJSON();
                if( metadata._open_condition ){
                    data_fields._open_condition = metadata._open_condition;
                    data_fields.conditions[ metadata._open_condition ] = metadata[metadata._open_condition];
                }
				
			}
			var	object = {
				_open_condition	: data_fields._open_condition,
				conditions		: data_fields.conditions,
				forms			: forms,
				magic			: data_fields._magic
			};

			return object;
		}

		cf_new_condition_group = function( obj ){
			var data = cf_get_base_form(),
                id = obj.trigger.data('id');

			if( !data.conditions ){
				data.conditions = {};
			}

			data.conditions[id] = {
				id : id,
                parent : obj.trigger.data('parent'),
                name : 'out_' + Math.round(Math.random() * 99887766) + '' + Math.round(Math.random() * 99887766),
				form : obj.trigger.data('form'),
                connect : ''
			};

			data._open_condition = id;
			cf_save_points( data );

			return data;
		}

		cf_set_endpoint = function( obj ){
			var data = cf_get_base_form();
                
                if( data._open_condition ){
                    setConnectionLable( data.conditions[ data._open_condition ], obj );
                }

			cf_save_points( data );
		}

		$( document ).on('click', '[data-add-line]', function(){
			var clicked = $( this ),
				id = clicked.data('addLine'),
				data = cf_get_base_form(),
				pid = clicked.data('group'),
				cid = 'cl' + Math.round(Math.random() * 99887766) + '' + Math.round(Math.random() * 99887766);

			if( !data.conditions[pid].group ){
				data.conditions[pid].group = {};
			}
			if( !data.conditions[pid].group[id] ){
				data.conditions[pid].group[id] = {};	
			}
			
			// initial line
			data.conditions[pid].group[id][cid] = {
				parent		:	id
			};
			
			cf_save_points( data, true );
		});
		
		$( document ).on('click', '[data-add-group]', function(){
			var clicked = $( this ),
				pid = clicked.data('addGroup'),
				data = cf_get_base_form(),
				id = 'rw' + Math.round(Math.random() * 99887766) + '' + Math.round(Math.random() * 99887766),
				cid = 'cl' + Math.round(Math.random() * 99887766) + '' + Math.round(Math.random() * 99887766);

			if( !data.conditions[pid].group ){
				data.conditions[pid].group = {};
			}
			if( !data.conditions[pid].group[id] ){
				data.conditions[pid].group[id] = {};	
			}
			
			// initial line
			data.conditions[pid].group[id][cid] = {
				parent		:	id
			};

			cf_save_points( data, true );
		});
		
		cf_new_condition_point = function(){
			var clicked = $( this ),
				id = 'cp' + Math.round(Math.random() * 99887766) + '' + Math.round(Math.random() * 99887766),
				data = cf_get_base_form();
			
			data._open_condition = id;

			cf_save_points( data, true );
			return data;
		};

		$( document ).on('change', '[data-live-sync]', function(){

			var data = cf_get_base_form();

			cf_save_points( data, true );
			
		});
		$( document ).on('click', '#tab_conditions', function(){

			var data = cf_get_base_form();

			cf_save_points( data, true );
			
		});

		$( document ).on('click', '[data-open-group]', function(){
			var clicked = $( this ),
				id = clicked.data('openGroup'),
				data = cf_get_base_form();

			data._open_condition = id;
			cf_save_points( data, true );

		});

		$( document ).on('click', '[data-remove-line]', function(){
			var clicked = $( this ),
				id = clicked.data('removeLine');
			
			$('.condition-line-' + id).remove();

			var data = cf_get_base_form();

			cf_save_points( data, true );
		});

		$( document ).on('click', '[data-remove-group]', function(){
			var clicked = $( this ),
				id = clicked.data('removeGroup');
			
			if( clicked.data('confirm') ){
				if( !confirm( clicked.data('confirm') ) ){
					return;
				}
			}

			$('.condition-point-' + id).remove();

			var data = cf_get_base_form();
			
			data._open_condition = '';

			cf_save_points( data, true );
		});

		$( document ).on( 'keydown keyup keypress change', '[data-sync]', function( e ){
			var press = $( this ),
				target = $( press.data('sync') );
			if( target.is( 'input' ) ){
				target.val( press.val() ).trigger( 'change' );
			}else{
				target.html( press.val() );
			}
		});
		$( document ).on( 'change', '[data-bind-condition]', function(){
			
			$(document).trigger('show.fieldedit');

			var clicked = $(this),
				bind = $( clicked.data('bindCondition') );
			if( clicked.is(':checked') ){
				bind.val( clicked.val() );
			}else{
				bind.val( '' );
			}

			var data = cf_get_base_form();

			cf_save_points( data, true );
		});
        $( document ).on('mouseup', '.form-node', function(){            
            var node = jQuery( this ),
                pos = node.find('.form-node-position');
                pos.val( node.css('left') + ',' + node.css('top') );

        });
		$( document ).on( 'show.fieldedit', function(){

			var data = $('#caldera-forms-conditions-panel').formJSON(),
				condition_selectors = $( '.cf-conditional-selector');
			condition_selectors.each( function(){
				var select 	 = $(this),
					selected = select.parent().val(),
					field = select.parent().data('id');

				select.empty();
				for( var con in data.conditions ){
					var run = true;
					// check field is not in here.
					for( var grp in data.conditions[con].group ){
						for( var ln in data.conditions[con].group[grp] ){
							if( data.conditions[con].group[grp][ln].field === field ){
								run = false;
							}
						}
					}
					if( true === run ){
						var sel = '',
							line = '<option value="' + con + '" ' + ( selected === con ? 'selected="selected"' : '' ) + '>' + data.conditions[con].name + '</option>';
						
						select.append( line );
					}
				}

			});
		});

	} );

    jQuery( document ).ready( function(){
        init_jsPlumb();
    });

</script>