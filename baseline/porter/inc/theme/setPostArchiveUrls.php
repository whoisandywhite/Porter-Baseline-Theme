<?php 
class setPosttypeArchiveUrls {
    
    use Porter_Config_Trait;
    

    /**
     * Constructor function to hook into WordPress
     */
    function __construct() 
    {
        add_filter( 'register_post_type_args', [$this, 'filter_post_type_args'], 10, 2 );

        add_action( 'acf/options_page/save', [$this, 'flush_permalinks'], 10, 2 );
    }

    /**
     * Set the post archive slug from ACF option value for multiple post types, including the parent page in the slug.
     */
    public function filter_post_type_args( $args, $post_type )
    {
        // Get all custom post types
        $custom_post_types = porter_get_custom_post_types(false);

        // Check if the current post type is in the list
        if( !in_array( $post_type, $custom_post_types ) ) return $args;

        // Get the ACF option key based on the post type
        $page_id = get_field( "{$post_type}_archive_page_id", 'option' );
        if( empty( $page_id ) ) return $args;

        // Get the slug for the archive page
        $slug = $this->get_full_slug( $page_id );
        if( empty( $slug ) ) return $args;

        // Set the archive slug
        $args['has_archive'] = $slug;

        return $args;
    }

    /**
     * Retrieve the full slug for a page, including its parent pages (if any).
     *
     * @param int $page_id The page ID.
     * @return string The full slug with parent slugs included.
     */
    private function get_full_slug( $page_id )
    {
        $slug = get_post_field( 'post_name', $page_id );
        if( empty( $slug ) ) return '';

        // Retrieve parent pages and append them to the slug
        $parent_id = wp_get_post_parent_id( $page_id );
        while( $parent_id ) {
            $parent_slug = get_post_field( 'post_name', $parent_id );
            if( !empty( $parent_slug ) ) {
                $slug = $parent_slug . '/' . $slug;  // Append parent slug to the beginning
            }
            $parent_id = wp_get_post_parent_id( $parent_id ); // Move up the hierarchy
        }

        return $slug;
    }

    /**
     * Flush permalinks on save options page
     */
    function flush_permalinks( $post_id, $menu_slug )
    {
        if( 'key-pages' != $menu_slug ) return;
        flush_rewrite_rules();
    }

}
new setPosttypeArchiveUrls();
