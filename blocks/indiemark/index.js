( function ( wp ) {
	'use strict';
	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	wp.blocks.registerBlockType( 'indieweb/indiemark', {
		edit: function ( props ) {
			return el( wp.element.Fragment, null,
				el( wp.blockEditor.InspectorControls, null,
					el( wp.components.PanelBody, { title: __( 'IndieMark', 'indieweb' ) },
						el( wp.components.SelectControl, {
							label: __( 'Level', 'indieweb' ), value: props.attributes.level,
							options: [ { label: __( 'Detect automatically', 'indieweb' ), value: 'auto' } ].concat(
								[ 0, 1, 2, 3, 4, 5 ].map( function ( level ) {
									return { label: wp.i18n.sprintf( __( 'Level %d', 'indieweb' ), level ), value: String( level ) };
								} )
							),
							onChange: function ( value ) { props.setAttributes( { level: value } ); }
						} )
					)
				),
				el( 'div', wp.blockEditor.useBlockProps( { className: 'indieweb-indiemark-editor' } ),
					el( wp.serverSideRender, { block: 'indieweb/indiemark', attributes: props.attributes } )
				)
			);
		},
		save: function () { return null; }
	} );
} )( window.wp );
