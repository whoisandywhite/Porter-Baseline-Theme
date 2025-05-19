<?php
class baseline_filterQueryBlockExample {

    private $className = 'example';
    private $blockName = 'core/query';

    private $queryId;

    public function __construct() 
    {
        // Hook into the pre_render_block filter to check for the class and add a query modification filter
        add_filter('pre_render_block', array($this, 'maybe_filter_block'), 10, 2);
    }

    /**
     * Conditionally filters blocks before they are rendered.
     *
     * @param mixed $pre_render The block's pre-render output. Null if not yet rendered.
     * @param array $parsed_block The parsed block data.
     * @return mixed Modified pre-render output or null if no modifications were made.
     */
    public function maybe_filter_block($pre_render, $parsed_block) 
    {
        // Fail early for anything that isn't a core/query block
        if (!isset($parsed_block['blockName']) || $parsed_block['blockName'] !== $this->blockName ) {
            return $pre_render;
        }

        // Check if the $name class is present
        if (!isset($parsed_block['attrs']['className']) || strpos($parsed_block['attrs']['className'], $this->className ) === false) {
            return $pre_render;
        }

        // Save this queryId for later reference
        $this->queryId = $parsed_block['attrs']['queryId'];

        // If the block is set to inherit, we modify the global query
        if (isset($parsed_block['attrs']['query']['inherit']) && true === $parsed_block['attrs']['query']['inherit']) {
            global $wp_query;
            $wp_query = new \WP_Query($this->get_amended_query_args($wp_query->query_vars));
        } else {
            // Else we can just use the query_loop_block_query_vars filter
            add_filter('query_loop_block_query_vars', array($this, 'modify_query_vars'), 10, 2);
        }

        return $pre_render;
    }

    /**
     * Modify the query variables to include multiple post types.
     *
     * @param array $query_vars The query variables.
     * @param array $block The block data.
     * @return array Modified query variables.
     */
    public function modify_query_vars($query_vars, $block) 
    {
        // Check if the queryId matches the current query
        if( $block->context['queryId'] !== $this->queryId ) {
            return $query_vars;
        }

        // Modify the query variables to include the selected post types
        $query_vars = $this->get_amended_query_args( $query_vars );
        return $query_vars;
    }

    /**
     * Modifies query arguments based on filters applied.
     *
     * @param array $args Original WP_Query arguments.
     * @return array Amended query arguments.
     */
    public function get_amended_query_args($args) 
    {
        // Filter the args here
        
        return $args;
    }
}

// Instantiate the class
// new baseline_filterQueryBlockExample();
