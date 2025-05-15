<?php
/**
 * Porter Block Init functions
 *
 * @package Porter
 **/
namespace porterBlockMeta;

function helpers()
{
	$helpers = new class { use \Porter_Blocks_Trait; };
	$helpers->dir = __DIR__;
	return $helpers;
}
function eventTrait()
{
	return new class { use \Event_Trait ; };
}

/**
 * 
 * Block Pre-render function
 *
 **/
function pre_render( $block, $content, $is_preview, $post_id, $wp_block, $context)
{
	// Get acf values
	$key 		= get_field( 'key' );
	$value 		= get_field( $key, get_the_ID() );
	$render_as 	= get_field( 'render_as' );
	$prepend 	= get_field( 'prepend' );
	$append 	= get_field( 'append' );

	// Set block args
	$attrs = [
		'class' => 'meta-key--'.$key,
	];
	$args = [
		'class' => get_block_wrapper_attributes( $attrs ),
		'anchor' => helpers()->anchor( $block ),
	];

	$inlineClass = str_replace( 'wp-block-acf-meta', '', $args['class'] );
	$inlineClass = str_replace( 'meta-key--'.$key, '', $inlineClass );

	$args['output'] = '';

	//Values that need to be processed
	switch ( $key ) 
	{
		case 'event_display_date':
			$value = '';
			if( 'event' == get_post_type() || 'meeting' == get_post_type() ) {
				$value = '<i class="fa-regular fa-fw fa-calendar me-1"></i> ' . eventTrait()->get_display_date( get_the_ID() );
			}
			break;
		
		case 'event_display_times':
			$value = '';
			if( 'event' == get_post_type() || 'meeting' == get_post_type() ) {
				$times = eventTrait()->get_display_times( get_the_ID() );
				if( !empty( $times ) ) {
					$value = '<i class="fa-regular fa-fw fa-clock me-1"></i> ' . $times;
				}
			}
			break;
	}

	// handle empty values
	if ( empty( $value ) && $is_preview ) {
		$args['output'] = "<code>Render: $key as $render_as</code>";
	}

	if ( !empty( $value ) )
	{
		switch ( $render_as ) 
		{
			// Render as: Plain
			case 'plain':
				$fontWeight = get_field( 'font_weight' );
				$value = $prepend.$value.$append;
				if ( !empty( $fontWeight ) ) {
					$value = '<span style="font-weight:'.$fontWeight.'">'.$prepend.$value.$append.'</span>';
				}
				$args['output'] = sprintf( 
					"<div %s %s>%s</div>", 
					$inlineClass, 
					$args['anchor'],
					$value 
				);
				break;
			
			// Render as: Element
			case 'element':
				$element 	= get_field( 'element' );
				$elemAttrs = [];
				if ( !empty( $block['backgroundColor'] ) ) $elemAttrs['backgroundColor'] = $block['backgroundColor'];
				if ( !empty( $block['textColor'] ) ) $elemAttrs['textColor'] = $block['textColor'];
				if ( !empty( $block['style'] ) ) $elemAttrs['style'] = $block['style'];
				
				// deal with paragraphs
				if ( 'p' == $element['type'] ) 
				{
					$blockTemplate = sprintf( 
						"<!-- wp:paragraph { %s } --><p %s>%s</p><!-- /wp:paragraph -->", 
						json_encode( $elemAttrs ),
						$inlineClass,
						$prepend.$value.$append
					);
				}
				// deal with headings
				if ( str_contains( $element['type'], 'h' ) )
				{
					$elemAttrs['level'] = str_replace( 'h', '', $element['type'] );
	
					$blockTemplate = sprintf( 
						"<!-- wp:heading { %s } --><h%s %s>%s</h%s><!-- /wp:heading -->", 
						json_encode( $elemAttrs ),
						$elemAttrs['level'],
						$inlineClass,
						esc_html( $prepend.$value.$append ),
						$elemAttrs['level']
					);
				}
	
				$args['output'] = do_blocks( $blockTemplate );
				break;
	
			// Render as: Link
			case 'link':
				$link = get_field( 'link' );
	
				if( empty( $link ) ) break;
				
				$target = $link['target'];
				$url = ( 'value' == $link['url_type'] ) ? $value : $link['custom_url'];
				$class = 'meta-link--'.get_field('link_type');
	
				if ( 'icon' == $link['label_type'] ) {
					$class = 'meta-link-plain';
					$label = sprintf(
						'<i class="far fa-%s"></i>',
						$link['label_icon_class']
					);
				} else {
					$label = ( 'value' == $link['label_type'] ) ? $value : $link['custom_label'];
				}

				$label = $prepend.$label.$append;
	
				if( empty( $url ) ) break;
	
				$link = sprintf( 
					'<a href="%s" class="%s" %s>%s</a>',
					$url,
					$class,
					$target,
					$label
				);

				$args['output'] = sprintf( 
					"<div %s %s>%s</div>", 
					$inlineClass, 
					$args['anchor'],
					$link
				);

				break;
				
		}
	}


	// Render the template
	if ( !empty( $args['output' ] ) || $is_preview )
		echo \get_template_part( helpers()->path().'/template', '', $args);
}





/**
 * Return choices to acf "key" select field
 *
 **/
function get_key_choices( $field ) 
{
	$field['choices'] = [
		"Events" => [
			"event_display_date" => "Event Display Date",
			"event_display_times" => "Event Times",
		],
	];

	return $field;
}
add_filter('acf/load_field/key=field_65967988249af', __NAMESPACE__.'\\get_key_choices', 10, 1);



















