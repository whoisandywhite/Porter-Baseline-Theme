<?php
/**
* Theme
*
* Functions, hooks, filters and actions which will be used
* only in this project.
*
* @package codh
* @version 1.0.0
*/

class codh_Theme
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
		// Language Loading
		add_action( 'init', [$this, 'load_theme_textdomain'] );
		// Add codh creator link - do not remove
		add_action( 'wp_footer', [ $this, 'add_developer_link' ] );
	}


	/**
	 * Gives developer credit. Do not remove.
	 */
	public function add_developer_link() : void
	{
		print( '<a class="sr-only" href="https://whoisandywhite.com" title="WordPress website developer">WordPress Gutenberg theme by Andy White</a>' );
	}
	

	/**
	 * Sets up multilangage utilities.
	 */
	public function load_theme_textdomain() {
		load_theme_textdomain( 'codh', get_template_directory() . '/languages' );
	}
}

new codh_Theme();















