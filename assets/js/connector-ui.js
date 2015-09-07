var add_newNode, deleteEndpoint, redraw_all;

function init_jsPlumb(){
    jsPlumb.ready(function () {

        var instance = jsPlumb.getInstance({
            // default drag options
            DragOptions: { cursor: 'move', zIndex: 2000 },
            PaintStyle : {
                lineWidth: 2,
                strokeStyle: "#a3be5f",
                joinstyle: "round",
                outlineColor: "#F1F1F1",
                outlineWidth: 1
            },
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

        deleteEndpoint = function( obj ){
            instance.deleteEndpoint( obj.trigger.data('endpoint') );
            remove_node( obj.trigger.data('id') );
            //remove_node
        }
        add_newNode = function( obj ) {

            var d = document.createElement("div");
            var id = jsPlumbUtil.uuid();
            d.className = "window cf-node form-node";
            d.id = id;
            d.dataset.form = obj.trigger.data('form');
            d.innerHTML = "<strong>" + obj.trigger.data('name') + "</strong><input type=\"hidden\" class=\"form-node-position\" name=\"config[node][" + id + "][position]\" value=\"\"><input type=\"hidden\" name=\"config[node][" + id + "][form]\" value=\"" + obj.trigger.data('form') + "\"><button data-parent=\"" + id + "\" data-form=\"" + obj.trigger.data('form') +"\" class=\"button button-small\" type=\"button\"><span class=\"dashicons dashicons-plus\"></span></button> - <span class=\"dashicons dashicons-no\"></span>";
            d.style.left = "0px";
            d.style.top = "0px";
            instance.getContainer().appendChild(d);        
            if( jQuery('.start-point').length ){
    			d.style.left = "130px";
    			d.style.top = "-50px";
            	instance.addEndpoint(d, targetEndpoint, { anchor: 'Continuous' });
        	}else{
        		d.className = "window cf-node form-node start-point";
                d.innerHTML += "<input type=\"hidden\" class=\"form-node-position\" name=\"config[node][" + id + "][base]\" value=\"true\">";
        	}
            instance.repaintEverything();
            instance.draggable(d);

        };

        removeNode = function( el ){
            //console.log( instance );
            var endpoints = instance.getEndpoints( el ),
                ep_id = [];
            if( endpoints ){
                for( var ep = 0; ep < endpoints.length; ep++ ){
                    ep_id.push( endpoints[ ep ].id );
                    instance.deleteEndpoint( endpoints[ ep ] );
                }
            }
            instance.remove( el );

            return ep_id;
        }
        redraw_all = function(){
            instance.repaintEverything();
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
                isTarget: true,
                overlays: [
                    //[ "Label", { location: [0.5, -0.5], cssClass: "endpointTargetLabel" } ]
                ]
            },
            init = function (connection) {
                //connection.getOverlay("label").setLabel( connection.sourceId.substring(15) + "-" + connection.targetId.substring(15) );
            };

         addPointHome = function( el, id, wait ){	

         	var endpoint = instance.addEndpoint( el, sourceEndpoint, { anchor: 'Continuous', uuid : id });

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
                            'class' : 'button-primary ajax-trigger'
                        } ) + ";Delete|" + JSON.stringify( {
                            'data-modal-autoclose' : 'addEndpoint',
                            'data-endpoint'     :  id,
                            'data-request'  :   'deleteEndpoint',
                            'data-id'           :  endpoint.id,
                            'class' : 'button ajax-trigger'
                        } ) + "|button delete-endpoint"

                    }).baldrick().trigger('click');
            });
            
            if( ! wait ){
         	  instance.repaintEverything();
            }

         	return endpoint;
         }
        var addTarget = function( to, uuid ){
            instance.addEndpoint( to, targetEndpoint, { anchor: 'Continuous', uuid : uuid });
        }

        var initial_nodes = jsPlumb.getSelector(".cf-form-canvas .form-node");
        // suspend drawing and initialise.
        instance.batch(function () {
            
            instance.draggable( initial_nodes, { grid: [20, 20] });
            // do connections
            var initial_connections = jsPlumb.getSelector(".condition-point");
            if( initial_connections.length ){
                var db = jQuery('#cf-conditions-db'),
                    data = JSON.parse( db.val() ),
                    endpoints = {},
                    new_db = {
                        _open_condition : '',
                        conditions : {},
                        forms : data.forms
                    };

                for( var n = 0; n < initial_connections.length; n++ ){
                    var from = 'start' + Math.round(Math.random() * 99866) + Math.round(Math.random() * 99866);                

                    if( ! endpoints[ initial_connections[n].dataset.to ] ){
                        var to = 'end' + Math.round(Math.random() * 99866) + Math.round(Math.random() * 99866);
                        addTarget( initial_connections[n].dataset.to, to );
                        endpoints[ initial_connections[n].dataset.to ] = to;
                    }
                    
                    var endpoint = addPointHome( initial_connections[n].dataset.from, from, true );
                    data.conditions[ initial_connections[n].dataset.src ].id = endpoint.id;
                    new_db.conditions[ endpoint.id ] = data.conditions[ initial_connections[n].dataset.src ];
                    instance.connect({uuids: [ from, endpoints[ initial_connections[n].dataset.to ] ], editable: true});    
                }

                db.val( JSON.stringify( new_db ) );
            }

            // listen for new connections; initialise them the same way we initialise the connections at startup.
            instance.bind("connection", function (connInfo, originalEvent) {
                //init(connInfo.connection);
                var data = cf_get_base_form(),
                    db = jQuery('#cf-conditions-db'),
                    condition = data.conditions[ connInfo.sourceEndpoint.id ];
                
                data.conditions[ connInfo.sourceEndpoint.id ].connect = connInfo.target.id;
                db.val( JSON.stringify( data ) );
                connInfo.connection.getOverlay("label").setLabel( condition.name );
            });

        });

        jsPlumb.fire("jsPlumbFormsLoaded", instance);

    });
};