<?php
// Anonymous class with usage of porter_filters_trait for enhancing block queries.
new class {
	
    use porter_filters_trait;

    public $name = 'use-filters';

    /**
	 * Constructor function to hook into WordPress and modify the block query.
	 */
	function __construct() 
	{
		// Hook into the block rendering process to allow for custom query modifications 
		// based on specific block attributes or conditions.
		add_filter('pre_render_block', [$this, 'maybe_filter_block'], 10, 2);

		// Integrate with Relevanssi search to adjust the query parameters used by Relevanssi
		// when performing a search. This ensures compatibility with Relevanssi's enhanced search capabilities.
		add_filter('relevanssi_modify_wp_query', [$this, 'relevanssi_modify_wp_query']);
	}

	
	/**
	 * Modifies the WordPress query for searches handled by Relevanssi.
	 * 
	 * This method checks if the current query is a search query and the main query. If so, 
	 * it applies custom amendments to the query variables based on specific criteria defined
	 * in `get_amended_query_args`. This allows for dynamic modifications to search queries,
	 * such as filtering by taxonomy or meta values, based on the request parameters or other conditions.
	 *
	 * @param WP_Query $query The WP_Query instance being modified.
	 * @return WP_Query The modified query if conditions are met, or the original query otherwise.
	 */
	public function relevanssi_modify_wp_query($query) 
	{
		// Check if the current query is a search and is the main query
		if($query->is_search() && $query->is_main_query())
		{
			// Apply custom amendments to the query variables.
			$query->query_vars = $this->get_amended_query_args($query->query_vars);
		}
		// Return the (potentially modified) query
		return $query;
	}


    /**
     * Modifies query arguments based on filters applied.
     * 
     * This method takes original query arguments and amends them based on the presence of
     * certain GET parameters or filter settings, adjusting the query for taxonomy or meta queries,
     * and setting 'ignore_sticky_posts' if necessary.
     *
     * @param array $args Original WP_Query arguments.
     * @return array Amended query arguments.
     */
    public function get_amended_query_args($args) 
	{
		// Inherited queries need the post type to be manually set after filtering
		// $args['post_type'] = 'post';
		$args['post_status'] = 'publish';

		// Parse the GET variables into a taxonomy query
		$amended_args = $this->parse_filter_query( $args );
		
		// If there is a tax query, ignore sticky posts
		if ( isset( $amended_args['tax_query']) || isset( $amended_args['meta_query'])) {
			$amended_args['ignore_sticky_posts'] = true;
		}
		
		
		$new_args = array_merge(
			$args,
			$amended_args
		);


		// If this is the projects archive, include the `funding-call` post type
		if( 'project' == $new_args['post_type'] ) $new_args['post_type'] = ['project', 'funding-call'];

		return $new_args;
	}


	/**
     * Filters the query arguments for public queries.
     * 
     * This method is intended to be used as a filter callback to modify query arguments
     * for blocks that are rendered on the front end.
     *
     * @param array $args Original query arguments.
     * @param mixed $block The current block being rendered.
     * @return array Modified query arguments.
     */
	public function public_query_filter( $args, $block )
	{
		$args = $this->get_amended_query_args( $args );
		return $args;
	}
	
	/**
     * Conditionally filters blocks before they are rendered.
     * 
     * This method checks if the current block matches specific criteria (e.g., a certain className)
     * and applies query modifications accordingly. It supports modifying global post queries
     * for blocks set to inherit global queries and adds a filter for other blocks.
     *
     * @param mixed $pre_render The block's pre-render output. Null if not yet rendered.
     * @param array $parsed_block The parsed block data.
     * @return mixed Modified pre-render output or null if no modifications were made.
     */
	public function maybe_filter_block($pre_render, $parsed_block)
	{
		// fail early for anything that isn't a core/query block
		if( !isset( $parsed_block['blockName'] ) ) return;
		if( 'core/query' != $parsed_block['blockName'] ) return;
		if( !isset($parsed_block['attrs']['className'] )) return;
		if( $this->name != $parsed_block['attrs']['className']) return;
		if( is_search() ) return;

		// Apply filters 
		// If the block is set to inherit, we need to hack the global post query
		if ( isset($parsed_block['attrs']['query']['inherit']) && 
			true === $parsed_block['attrs']['query']['inherit'] ) {
			global $wp_query;
			$wp_query = new \WP_Query($this->get_amended_query_args($wp_query->query_vars));
		} else {
			// Else we can just use the query_loop filter
			add_filter('query_loop_block_query_vars', [$this, 'public_query_filter'], 10, 2);
		}
		
		return $pre_render;
	}


};

