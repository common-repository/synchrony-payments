/**
 * This section of the code registers a new block, sets an icon and a category, and indicates what type of fields it'll include.
 *
 * @package Synchrony\Payments\Blocks
 */

wp.blocks.registerBlockType(
	'block-synchrony/mpp-widget-area',
	{
		title: 'MPP Block Widget',
		icon: 'format-image',
		category: 'widgets',
		keywords:["MPP Widget","Synchrony"],
		description:"Display a selection of mpp banner on block area.",
		supports:{
			align:true,
			"spacing": {
				"margin": true,
				"padding": true,
				"blockGap": true
			}
		},
		attributes: {
			banner_id: {type: 'string'},
			content: {type: 'string'}
		},
		edit: function (props) {
			function updateContent(event) {
				props.setAttributes( {banner_id: event.target.value} )
				props.setAttributes( {content: mppData.content_list[event.target.value]} )
			}
			return React.createElement(
				"div",
				null,
				React.createElement(
					'div',
					null,
					React.createElement( "label", {className: "label", style:{fontWeight:"bold"} },"MPP Banner : " ),
					React.createElement(
						"select",
						{ name: "mpp-widget", id: "mpp-widget", style:{paddingTop:8,paddingBottom:8,width:"-webkit-fill-available"}, onChange: updateContent, defaultValue:props.attributes.banner_id },
						null,
						React.createElement( "option",{ value: '' },'Please select' ),
						mppData.mpp_banners.map( ({ value, label }, index) => React.createElement( "option",{ value: value },label.replace( "&#215;", "x" ) ) )
					)
				),
				React.createElement(
					'div',
					null,
					React.createElement( 'div', { dangerouslySetInnerHTML: { __html: props.attributes.content } } )
				)
			);
		},
		save: function (props) {
			return wp.element.createElement(
				"div",
				{ dangerouslySetInnerHTML: { __html: props.attributes.content } }
			);
		},
		example: {
			attributes: {
				backgroundColor: '#000000',
				opacity: 0.8,
				padding: 30,
				textColor: '#FFFFFF',
				radius: 10,
				title: 'I am a slide title',
			},
		}
	}
)
