<?php
/**
* Theme
*
* Functions, hooks, filters and actions which will be used
* only in this project.
*
* @package baseline
* @version 1.0.0
*/

class baseline_Theme
{

	/**
	 * Prepare the class
	 */
	public function __construct()
	{
		$this->define_hooks();
	}

	/**
	 * Define admin hooks
	 *
	 * @return void
	 */
	public function define_hooks(): void
	{
		// Terms clause
		add_filter('terms_clauses', [ $this, 'terms_clauses' ], 99999, 3);
		// Language Loading
		add_action( 'init', [$this, 'load_theme_textdomain'] );
		// Add baseline creator link - do not remove
		add_action( 'wp_footer', [ $this, 'add_developer_link' ] );
	}


	/**
	 * terms_clauses
	 *
	 * filter the terms clauses
	 *
	 * @param $clauses array
	 * @param $taxonomy string
	 * @param $args array
	 * @return array
	 * @link http://wordpress.stackexchange.com/a/183200/45728
	 */
	public function terms_clauses( $clauses, $taxonomy, $args )
	{
		global $wpdb;

		if ( isset( $args['post_types'] ) && !empty( $args['post_types'] ) )
		{
			$post_types = $args['post_types'];

			// allow for arrays
			if ( is_array( $args['post_types'] ) )
			{
				$post_types = implode( "','", $args['post_types'] );
			}

			$clauses['join'] 	.= " INNER JOIN $wpdb->term_relationships AS r ON r.term_taxonomy_id = tt.term_taxonomy_id INNER JOIN $wpdb->posts AS p ON p.ID = r.object_id";
			$clauses['where'] 	.= " AND p.post_type IN ('". esc_sql( $post_types ). "') GROUP BY t.term_id";
		}

		return $clauses;
	}


	/**
	 * Gives developer credit. Do not remove.
	 */
	public function add_developer_link() : void
	{
		print( '<a class="sr-only" href="https://whoisandywhite.com" title="WordPress theme development by Andy White">Andy White, Freelance WordPress Developer London</a>' );
	}
	

	/**
	 * Sets up multilangage utilities.
	 */
	public function load_theme_textdomain() {
		load_theme_textdomain( 'baseline', get_template_directory() . '/languages' );
	}

}

new baseline_Theme();















