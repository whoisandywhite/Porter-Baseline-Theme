<?php 
class PostTaxonomySync 
{
    private static $allow_term_creation = false; // Global flag

    public function __construct() 
    {
        // Hook into post creation and update
        add_action('save_post_institution', [$this, 'sync_taxonomy_with_post'], 10, 3);
        
        // Hook into post trashing and deletion
        add_action('trashed_post', [$this, 'handle_post_trashed'], 10, 1);
        add_action('before_delete_post', [$this, 'handle_post_deleted'], 10, 1);

        // Filter term link to redirect to linked post object
        add_filter('term_link', [$this, 'filter_term_link_to_post'], 10, 3);

        // Prevent manually added terms
        add_action('create_term', [$this, 'prevent_manual_term_creation'], 10, 3);

        // Hide the "Add Term" form in the admin for specific taxonomies
        add_action('admin_head', [$this, 'hide_add_term_form']);
    }

    /**
     * Sync taxonomy term with post when a post is created or updated.
     *
     * @param int $post_id The post ID.
     * @param WP_Post $post The post object.
     * @param bool $update Whether this is an update or new post creation.
     */
    public function sync_taxonomy_with_post($post_id, $post, $update)
    {
        // Check if the post is an auto-draft or a revision
        if ($post->post_status === 'auto-draft' || wp_is_post_revision($post_id)) {
            return;
        }

        $post_type = $post->post_type;
        $taxonomy = '';

        switch ($post_type) {
            case 'institution':
                $taxonomy = 'related-institution';
                break;
            default:
                return; // If not a handled post type, exit the function
        }

        // Allow programmatic term creation
        self::$allow_term_creation = true;

        // Look for the term that has the `linked_post_object` field pointing to this post
        $term = $this->get_term_by_linked_post($post_id, $taxonomy);

        if ($term) {
            // Update existing term
            wp_update_term($term->term_id, $taxonomy, [
                'name' => $post->post_title,
                // Ensure the slug follows the sanitized title unless it changes the existing term structure
                'slug' => sanitize_title($post->post_title)
            ]);
            clean_term_cache($term->term_id, $taxonomy);
        } else {
            // Ensure slug uniqueness by appending numeric suffix if needed
            $slug = sanitize_title($post->post_title);
            if (term_exists($slug, $taxonomy)) {
                $slug = wp_unique_term_slug($slug, (object) ['taxonomy' => $taxonomy]);
            }

            // Create a new term with the validated slug
            $result = wp_insert_term($post->post_title, $taxonomy, [
                'slug' => $slug,
            ]);

            if (!is_wp_error($result)) {
                $term_id = $result['term_id'];
                update_field('linked_post_object', $post_id, $taxonomy . '_' . $term_id);
                clean_term_cache($term_id, $taxonomy);
            }
        }

        // Reset the flag after term creation
        self::$allow_term_creation = false;
    }

    /**
     * Get the term by looking for a `linked_post_object` that matches the post ID.
     *
     * @param int $post_id The ID of the post to search for.
     * @param string $taxonomy The taxonomy to search within.
     * @return WP_Term|false The term if found, false if not.
     */
    private function get_term_by_linked_post($post_id, $taxonomy)
    {
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        ]);

        foreach ($terms as $term) {
            $linked_post_id = get_field('linked_post_object', $taxonomy . '_' . $term->term_id);
            if ((int)$linked_post_id === (int)$post_id) {
                return $term;
            }
        }

        return false;
    }

    /**
     * Prevent manually added taxonomy terms.
     *
     * @param int $term_id The term ID.
     * @param int $tt_id The term taxonomy ID.
     * @param string $taxonomy The taxonomy slug.
     */
    public function prevent_manual_term_creation($term_id, $tt_id, $taxonomy)
    {
        $allowed_taxonomies = ['related-institution'];

        if (in_array($taxonomy, $allowed_taxonomies) && !self::$allow_term_creation) {
            // Prevent the term from being created
            wp_delete_term($term_id, $taxonomy);

            // Display an error message to the user
            wp_die(__('You cannot add terms to this taxonomy manually. Terms are automatically managed through the linked posts.'));
        }
    }

    /**
     * Handle post trashing.
     *
     * @param int $post_id The post ID.
     */
    public function handle_post_trashed($post_id)
    {
        $this->delete_taxonomy_term($post_id);
    }

    /**
     * Handle post deletion.
     *
     * @param int $post_id The post ID.
     */
    public function handle_post_deleted($post_id)
    {
        $this->delete_taxonomy_term($post_id);
    }

    /**
     * Delete the corresponding taxonomy term when a post is trashed or deleted.
     *
     * @param int $post_id The post ID.
     */
    private function delete_taxonomy_term($post_id)
    {
        $post = get_post($post_id);

        if ($post && ($post->post_type === 'institution')) 
        {
            $taxonomy = 'related-institution';

            // Get the linked term ID from the ACF field
            $term_id = get_field('linked_post_object', $taxonomy . '_' . $post_id);

            // If the term exists, delete it
            if ($term_id) {
                wp_delete_term($term_id, $taxonomy);
            }
        }
    }

    /**
     * Filter the taxonomy term URL to redirect to the linked post URL.
     *
     * @param string $url The term link URL.
     * @param WP_Term $term The term object.
     * @param string $taxonomy The taxonomy slug.
     * @return string The modified URL (post URL if linked).
     */
    public function filter_term_link_to_post($url, $term, $taxonomy)
    {
        $allowed_taxonomies = ['related-institution'];

        if (in_array($taxonomy, $allowed_taxonomies)) {
            $linked_post_id = get_field('linked_post_object', $taxonomy . '_' . $term->term_id);

            if ($linked_post_id) {
                $post_url = get_permalink($linked_post_id);
                if ($post_url) {
                    return $post_url;
                }
            }
        }

        return $url;
    }

    /**
     * Hide the "Add Term" form for specified taxonomies in the admin.
     */
    public function hide_add_term_form()
    {
        $screen = get_current_screen();

        // Only hide the form for specific taxonomies in the admin
        if (in_array($screen->taxonomy, ['related-institution'])) {
            echo '<style>
                #col-container .form-wrap {
                    display: none !important;
                }
            </style>';
        }
    }
}

new PostTaxonomySync();
