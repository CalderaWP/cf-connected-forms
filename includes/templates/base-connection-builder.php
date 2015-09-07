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
?>
<input type="hidden" name="config[is_connected_form]" value="true">
<input type="hidden" id="forms-db" value="<?php echo esc_attr( json_encode( $forms ) ); ?>">
<style type="text/css">

.flowchart-form .window {
  background-color: #fff;
  border: 1px solid #cfcfcf;
  color: #333;
  cursor: move;

  opacity: 0.6;
  padding: 6px 14px;
  position: absolute;
  text-align: center;
  transition: box-shadow 0.15s ease-in 0s;
  width: auto;
  z-index: 20;
}
.flowchart-form .window:hover {
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  opacity: 1;
}
.flowchart-form .active {
    border: 1px dotted green;
}

.flowchart-form .hover {
    border: 1px dotted red;
}

#flowchartWindow1 {
    top: 10px;
    left: 10px;
}

#flowchartWindow2 {
    top: 0px;
    left: 0px;
}

#flowchartWindow3 {
    top: 100px;
    left: 400px;
}

#flowchartWindow4 {
    top: 200px;
    left: 400px;
}
#flowchartWindow5 {
    top: 300px;
    left: 400px;
}
#flowchartWindow6 {
    top: 400px;
    left: 400px;
}
#flowchartWindow7 {
    top: 250px;
    left: 400px;
}
#flowchartWindow8 {
    top: 0px;
    left: 800px;
}
#flowchartWindow9 {
    top: 400px;
    left: 700px;
}

.flowchart-form ._jsPlumb_connector {
    z-index: 4;
}

.flowchart-form ._jsPlumb_endpoint, .endpointTargetLabel, .endpointSourceLabel {
    z-index: 21;
    cursor: pointer;
    font-size: 10px;

}

.endpointSourceLabel:hover{
	opacity: 0.5;
}
.endpointSourceLabel:hover{
	opacity: 1;
}
.flowchart-form .aLabel {
	display: none;
    padding: 0.4em;
    font: 12px sans-serif;
    color: #444;
    z-index: 21;
    opacity: 0.8;
    filter: alpha(opacity=80);
    cursor: pointer;
}

.flowchart-form .aLabel._jsPlumb_hover {
    background-color: #fff;
    color: #333;
    border: 1px solid #cfcfcf;
    display: inline-block;
}

.window._jsPlumb_connected {
    opacity: 1;
}

.form-node.start-point{
	box-shadow: 9px 0 0 #a3be5f inset;
	cursor: default;
}

path, ._jsPlumb_endpoint {
    cursor: pointer;
}

._jsPlumb_overlay {
    background-color:transparent;
}
.jtk-form-canvas {
	position: relative;  
}

.jtk-node .button {
  margin: -1px -5px 0 5px;
  padding: 3px 1px !important;
}
</style>

		<button type="button" class="button add-form-stage">Add Form</button>
		<hr>
        <div id="jtk-forms-main" >
            <!-- forms -->

            <div class="jtk-form-canvas canvas-wide flowchart-form jtk-surface jtk-surface-nopan" id="canvas">
            	<?php 
            	/*
            	foreach( $element['node_position'] as $node_form => $position ){ 
            		if( empty( $forms[ $node_form ] ) ){
            			continue;
            		}
            		$position = explode( ',', $position );

            	?>
					<div class="window jtk-node form-node" id="<?php echo $node_form; ?>" style="left: <?php echo $position[0]; ?>; top: <?php echo $position[1]; ?>;">
						<strong><?php echo $forms[ $node_form ]['name']; ?></strong><input type="text" value="" name="config[node_position][<?php echo $node_form; ?>]" class="form-node-position">
						<button type="button" class="button button-small" data-parent="<?php echo $node_form; ?>">
							<span class="dashicons dashicons-plus"></span>
						</button>
					</div>
            	<?php 

            	} */
            	?>
            </div>
            <!-- /forms -->
        </div>
<script>
var add_newNode;
jsPlumb.ready(function () {

    var instance = jsPlumb.getInstance({
        // default drag options
        DragOptions: { cursor: 'pointer', zIndex: 2000 },
        // the overlays to decorate each connection with.  note that the label overlay uses a function to generate the label text; in this
        // case it returns the 'labelText' member that we set on each connection in the 'init' method below.
        ConnectionOverlays: [
            [ "Arrow", { location: 1, width: 10, length : 8 } ],
            [ "Label", {
                location: 0.1,
                id: "label",
                cssClass: "aLabel"
            }]
        ],
        Container: "canvas"
    });


    add_newNode = function( obj ) {

        var d = document.createElement("div");
        var id = jsPlumbUtil.uuid();
        d.className = "window jtk-node form-node";
        d.id = id;
        d.innerHTML = "<strong>" + obj.trigger.data('name') + "</strong><input type=\"hidden\" class=\"form-node-position\" name=\"config[node_position][" + obj.trigger.data('form') +"]\" value=\"\"><button data-parent=\"" + id + "\" data-form=\"" + obj.trigger.data('form') +"\" class=\"button button-small\" type=\"button\"><span class=\"dashicons dashicons-plus\"></span></button> - <span class=\"dashicons dashicons-no\"></span>";
        d.style.left = "0px";
        d.style.top = "0px";
        instance.getContainer().appendChild(d);        
        if( jQuery('.form-node').length > 1 ){
			d.style.left = "130px";
			d.style.top = "-50px";
        	instance.addEndpoint(d, targetEndpoint, { anchor: 'Continuous' });
        	instance.repaintEverything();
        	instance.draggable(d);
    	}else{
    		d.className = "window jtk-node form-node start-point";
    	}


    };

    removeNode = function( el ){
        instance.remove( el );
    }

    // this is the paint style for the connecting lines..
    var connectorPaintStyle = {
            lineWidth: 2,
            strokeStyle: "#a3be5f",
            joinstyle: "round",
            outlineColor: "#F1F1F1",
            outlineWidth: 1
        },
    // .. and this is the hover style.
        connectorHoverStyle = {
            lineWidth: 2	,
            strokeStyle: "#738e2f",
            outlineWidth: 1,
            outlineColor: "#F1F1F1"
        },
        endpointHoverStyle = {
            fillStyle: "#738e2f",
            strokeStyle: "#738e2f"
        },
    // the definition of source endpoints (the small blue ones)
        sourceEndpoint = {
            endpoint: "Dot",
            paintStyle: {
                strokeStyle: "#a3be5f",
                fillStyle: "#ffffff",
                radius: 6,
                lineWidth: 2
            },
            isSource: true,
            connector: [ "Flowchart", { stub: [20, 20], gap: 10, cornerRadius: 2, alwaysRespectStubs: false } ],
            maxConnections: 1,
            allowLoopback: false,
            connectorStyle: connectorPaintStyle,
            hoverPaintStyle: endpointHoverStyle,
            connectorHoverStyle: connectorHoverStyle,
            dragOptions: {},
            overlays: [
                [ "Label", {
                	label : "",
                    location: [0.5, 1.5],
                    cssClass: "endpointSourceLabel"
                } ]
            ]
        },
    // the definition of target endpoints (will appear when the user drags a connection)
        targetEndpoint = {
            endpoint: "Dot",
            paintStyle: {
                strokeStyle: "#a3be5f",
                fillStyle: "#a3be5f",
                radius: 6,
                lineWidth: 2
            },
            hoverPaintStyle: endpointHoverStyle,
            maxConnections: -1,
            dropOptions: { hoverClass: "hover", activeClass: "active" },
            allowLoopback: false,
            isTarget: true,
            overlays: [
                //[ "Label", { location: [0.5, -0.5], cssClass: "endpointTargetLabel" } ]
            ]
        },
        init = function (connection) {
            //connection.getOverlay("label").setLabel( connection.sourceId.substring(15) + "-" + connection.targetId.substring(15) );
        };

     addPointHome = function( el, id ){	

     	var endpoint = instance.addEndpoint( el.data('id'), sourceEndpoint, { anchor: 'Continuous' });

        endpoint.bind("click", function(endpoint) {
                var trigger = jQuery('<a>', { 
                    'data-modal'        : 'addEndpoint',
                    'data-modal-title'  : 'Add Condition Point',
                    'data-template'     : '#conditions-tmpl',
                    'data-request'      : 'edit_node',
                    'data-modal-width'  : '580',
                    'data-modal-height' : '420',
                    'data-callback'     : 'baldrickTriggers',
                    'data-id'           :  endpoint.id,
                    'data-form'         :  jQuery(this).data('form'),
                    'data-modal-buttons' : "Close|" + JSON.stringify( {
                        'data-modal-autoclose' : 'addEndpoint',
                        'data-request'  :   'cf_set_endpoint',
                        'data-id'       :  jQuery(this).data('parent'),
                        'class' : 'button ajax-trigger'
                    } )
                }).baldrick().trigger('click');
        });

     	instance.repaintEverything();

     	return endpoint;
     }

    var _addEndpoints = function (toId, sourceAnchors, targetAnchors) {
        for (var i = 0; i < sourceAnchors.length; i++) {
            var sourceUUID = toId + sourceAnchors[i];
            instance.addEndpoint("flowchart" + toId, sourceEndpoint, { anchor: sourceAnchors[i], uuid: sourceUUID } );

        }
        if( targetAnchors ){
	        for (var j = 0; j < targetAnchors.length; j++) {
	            var targetUUID = toId + targetAnchors[j];
	            instance.addEndpoint("flowchart" + toId, targetEndpoint, { anchor: targetAnchors[j], uuid: targetUUID });
	        }
	       }
    };

    // suspend drawing and initialise.
    instance.batch(function () {

        _addEndpoints("Window2", []);


        // listen for new connections; initialise them the same way we initialise the connections at startup.
        instance.bind("connection", function (connInfo, originalEvent) {
            //init(connInfo.connection);
        	var db = JSON.parse( jQuery('#cf-conditions-db').val() ),
        		condition = db.conditions[ connInfo.sourceEndpoint.id ];

            connInfo.connection.getOverlay("label").setLabel( condition.name );
        });

        // make all the window divs draggable
        instance.draggable(jsPlumb.getSelector(".flowchart-form .window:not(.astatic)") );

        instance.bind("connectionDrag", function (connection) {
            console.log("connection " + connection.id + " is being dragged. suspendedElement is ", connection.suspendedElement, " of type ", connection.suspendedElementType);
        });

        instance.bind("connectionDragStop", function (connection) {


            console.log("connection " + connection.id + " was dragged");
        	jQuery('.form-node').each( function(){
        		var node = jQuery( this ),
        			pos = node.find('.form-node-position');

        			pos.val( node.css('left') + ',' + node.css('top') );
        	});         
        });

        instance.bind("connectionMoved", function (params) {
            console.log("connection " + params.connection.id + " was moved");
        });
    });

    jsPlumb.fire("jsPlumbFormsLoaded", instance);
});
jQuery( function( $ ){
	$( document ).on('click', '.form-node > .button', function(){
		//addPointHome( $(this).parent() );
		var trigge= $('<a>', { 
			'data-modal'		: 'addEndpoint',
			'data-modal-title'	: 'Add Condition Point',
			'data-template'		: '#conditions-tmpl',
			'data-request'		: 'cf_new_condition_group',
			'data-modal-width'	: '580',
			'data-modal-height'	: '420',
			'data-callback'		: 'baldrickTriggers',
			'data-id'			:  $(this).data('parent'),
			'data-form'			:  $(this).data('form'),
			'data-modal-buttons' : "Add Point|" + JSON.stringify( {
                'data-before'       :  'check_node_name',
				'data-modal-autoclose' : 'addEndpoint',
				'data-request'	:	'cf_set_endpoint',
				'data-id'		:  $(this).data('parent'),
				'class' : 'button ajax-trigger'
			} )
		}).baldrick().trigger('click');

	});

    $( document ).on( 'click', '.dashicons-no', function(){
        removeNode( $( this ).closest('.form-node')[0] );
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
<input type="hidden" id="cf-conditions-db" name="config[condition_points]" value="" 
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
				<input type="hidden" name="conditions[{{id}}][id]" value="{{id}}">
				<input type="hidden" name="conditions[{{id}}][form]" id="condition-group-form-{{id}}" value="{{form}}" required class="required block-input">				
				<div class="condition-point-{{id}}" style="width: 550px; float: left;">
					<div class="caldera-config-group">
						<label for="{{id}}_lable"><?php _e( 'Name', 'caldera-forms' ); ?></label>
						<div class="caldera-config-field">
							<input type="text" name="conditions[{{id}}][name]" id="condition-group-name-{{id}}" data-sync="#condition-group-{{id}}" value="{{#if name}}{{name}}{{else}}{{id}}{{/if}}" required class="required block-input">
						</div>
					</div>
					
					<div class="caldera-config-group">						
						<div class="caldera-config-field">
							<input type="hidden" name="conditions[{{id}}][type]" value="use">
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
								<input type="hidden" name="conditions[{{../../../id}}][group][{{parent}}][{{@key}}][parent]" value="{{parent}}">
								<span style="display:inline-block;">{{#if @first}}
									<?php _e( 'if', 'caldera-forms' ); ?>
								{{else}}
									<?php _e( 'and', 'caldera-forms' ); ?>
								{{/if}}</span>
								<input type="hidden" name="conditions[{{../../../id}}][fields][{{@key}}]" value="{{field}}" id="condition-bound-field-{{@key}}" data-live-sync="true">
								<select style="max-width:120px;vertical-align: inherit;" name="conditions[{{../../id}}][group][{{parent}}][{{@key}}][field]" data-sync="#condition-bound-field-{{@key}}">
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
								<select style="max-width:110px;vertical-align: inherit;" name="conditions[{{../../id}}][group][{{parent}}][{{@key}}][compare]">
									<option value="is" {{#is compare value="is"}}selected="selected"{{/is}}><?php _e( 'is', 'caldera-forms' ); ?></option>
									<option value="isnot" {{#is compare value="isnot"}}selected="selected"{{/is}}><?php _e( 'is not', 'caldera-forms' ); ?></option>
									<option value="&gt;" {{#is compare value="&gt;"}}selected="selected"{{/is}}><?php _e( 'is greater than', 'caldera-forms' ); ?></option>
									<option value="&lt;" {{#is compare value="&lt;"}}selected="selected"{{/is}}><?php _e( 'is less than', 'caldera-forms' ); ?></option>
									<option value="startswith" {{#is compare value="startswith"}}selected="selected"{{/is}}><?php _e( 'starts with', 'caldera-forms' ); ?></option>
									<option value="endswith" {{#is compare value="endswith"}}selected="selected"{{/is}}><?php _e( 'ends with', 'caldera-forms' ); ?></option>
									<option value="contains" {{#is compare value="contains"}}selected="selected"{{/is}}><?php _e( 'contains', 'caldera-forms' ); ?></option>
								</select>
								<span data-value="" class="caldera-conditional-field-value" style="padding: 0 12px 0; display:inline-block; width:200px;">

								{{#find @root/forms ../../../form}}
									{{#find fields ../field}}
										{{#if config/option}}
											<select style="width:165px;vertical-align: inherit;" name="conditions[{{../../../../id}}][group][{{../../../parent}}][{{@key}}][value]">
												<option></option>
												{{#each config/option}}
													<option value="{{@key}}" {{#is ../../../value value=@key}}selected="selected"{{/is}}>{{label}}</option>
												{{/each}}
											</select>
										{{else}}
											<input type="text" class="block-input" name="conditions[{{../../../../id}}][group][{{../../../parent}}][{{@key}}][value]" value="{{../../value}}" {{#unless ../../../field}}placeholder="Select field first" disabled=""{{/unless}}>
										{{/if}}
									{{else}}
										<input type="text" class="block-input" name="conditions[{{../../../../id}}][group][{{../../parent}}][{{@key}}][value]" value="{{../value}}" {{#unless ../../field}}placeholder="Select field first" disabled=""{{/unless}}>
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
				<div style="float: left; width: 288px; padding-left: 12px;">
				{{#if @root/fields}}
					<h4 style="border-bottom: 1px solid rgb(191, 191, 191); margin: 0px 0px 6px; padding: 0px 0px 6px;"><?php _e('Applied Fields', 'caldera-forms'); ?></h4>
					<p class="description"><?php _e('Select the fields to apply this condition to.', 'caldera-forms' ); ?></p>
					{{#each @root/fields}}

						<label style="display: block; margin-left: 20px;{{#find ../../fields ID}}opacity:0.7;{{/find}}"><input style="margin-left: -20px;" type="checkbox" data-bind-condition="#field-condition-type-{{ID}}" value="{{../id}}" {{#is conditions/type value=../id}}checked="checked"{{else}}{{#find @root/conditions conditions/type}}disabled="disabled"{{/find}}{{/is}} {{#find ../../fields ID}}disabled="disabled"{{/find}}>{{label}} [{{slug}}]</label>
						
					{{/each}}
				{{/if}}
				</div>
			{{/if}}
		</div>
	{{/find}}

</script>
<script type="text/javascript">
	var cf_new_condition_line, cf_new_condition_group, cf_set_endpoint, cf_get_base_form, edit_node;



	jQuery( function( $ ){

        edit_node = function( obj ){

            var db = JSON.parse( jQuery( '#cf-conditions-db' ).val() );
            db._open_condition = obj.trigger.data('id');
            console.log( db );
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
				$.extend(true, data_fields, modal_form.formJSON() );
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
				db = $('#cf-conditions-db'),
				endpoint = addPointHome( obj.trigger, id ),
				id = endpoint.id;

			if( !data.conditions ){
				data.conditions = {};
			}

			data.conditions[id] = {
				id : id,
				form : obj.trigger.data('form')
			};

			data._open_condition = id;
			db.val( JSON.stringify( data ) );

			return data;
		}

		cf_set_endpoint = function( obj ){
			var data = cf_get_base_form(),
				db = $('#cf-conditions-db');
                console.log( data );
			db.val( JSON.stringify( data ) );
		}

		$( document ).on('click', '[data-add-line]', function(){
			var clicked = $( this ),
				id = clicked.data('addLine'),
				db = $('#cf-conditions-db'),
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
			cf_get_base_form();
			db.val( JSON.stringify( data ) ).trigger( 'rebuild-conditions' );
		});
		
		$( document ).on('click', '[data-add-group]', function(){
			var clicked = $( this ),
				pid = clicked.data('addGroup'),
				db = $('#cf-conditions-db'),
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

			db.val( JSON.stringify( data ) ).trigger( 'rebuild-conditions' );
		});
		
		cf_new_condition_point = function(){
			var clicked = $( this ),
				id = 'cp' + Math.round(Math.random() * 99887766) + '' + Math.round(Math.random() * 99887766),
				db = $('#cf-conditions-db'),
				data = cf_get_base_form();
			
			data._open_condition = id;

			db.val( JSON.stringify( data ) ).trigger( 'rebuild-conditions' );
			return data;
		};

		$( document ).on('change', '[data-live-sync]', function(){

			var data = cf_get_base_form(),
				db = $('#cf-conditions-db');

			db.val( JSON.stringify( data ) ).trigger( 'rebuild-conditions' );
			
		});
		$( document ).on('click', '#tab_conditions', function(){

			var data = cf_get_base_form(),
				db = $('#cf-conditions-db');

			db.val( JSON.stringify( data ) ).trigger( 'rebuild-conditions' );
			
		});

		$( document ).on('click', '[data-open-group]', function(){
			var clicked = $( this ),
				id = clicked.data('openGroup'),
				db = $('#cf-conditions-db'),
				data = cf_get_base_form();

			data._open_condition = id;
			db.val( JSON.stringify( data ) ).trigger( 'rebuild-conditions' );

		});

		$( document ).on('click', '[data-remove-line]', function(){
			var clicked = $( this ),
				id = clicked.data('removeLine');
			
			$('.condition-line-' + id).remove();

			var db = $('#cf-conditions-db'),
				data = cf_get_base_form();

			db.val( JSON.stringify( data ) ).trigger( 'rebuild-conditions' );
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

			var db = $('#cf-conditions-db'),
				data = cf_get_base_form();
			
			data._open_condition = '';

			db.val( JSON.stringify( data ) ).trigger( 'rebuild-conditions' );
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

			var data = cf_get_base_form(),
				db = $('#cf-conditions-db');

			db.val( JSON.stringify( data ) ).trigger( 'rebuild-conditions' );
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

</script>