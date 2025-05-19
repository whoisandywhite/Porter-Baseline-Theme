<?php
/**
* Theme Scripts
*
* Functions, hooks, filters and actions which will be used
* only in this project.
*
* @package baseline
* @version 1.0.1
*/

class baseline_ThemeScripts
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
		// Add script to the site head
		add_action( 'wp_head', array( $this, 'header_scripts' ) );

		// Add script to site footer
		add_action( 'wp_footer', [ $this, 'footer_scripts' ] );
	}


	/**
	 * Header Scripts
	 */
	public function header_scripts() : void
	{		
		// Output baseline header Scripts on production
		if( 'production' == wp_get_environment_type() ) $this->baseline_header_tracking_scripts();
	}

	
	/**
	 * Outputs baseline Javascript snippets in header
	 */
	public function baseline_header_tracking_scripts()
	{
		?>
		<?php 
	}


	/**
	 * Footer Scripts
	 */
	public function footer_scripts() : void
	{
		// Displays helpful version information on non-production environments
		if( 'production' != wp_get_environment_type() && 'local' != wp_get_environment_type() ) {
			echo sprintf('<div style="background:gray;padding:0.5rem;color:white;font-size:0.8rem;">Current Version: %s</div>', wp_get_theme()->get('Version'));
			?>
  			<?php 
		}
		// Output baseline Footer Scripts on production
		if( 'production' == wp_get_environment_type() ) $this->baseline_footer_scripts();
	}

	
	/**
	 * Outputs baseline Javascript snippets in footer
	 */
	public function baseline_footer_scripts()
	{ 
		?>
		<!-- baseline footer scripts -->
		<?php
	}


}

new baseline_ThemeScripts();















