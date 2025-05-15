<?php
/**
* Theme Scripts
*
* Functions, hooks, filters and actions which will be used
* only in this project.
*
* @package codh
* @version 1.0.1
*/

class codh_ThemeScripts
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
		// Output codh header Scripts on production
		if( 'production' == wp_get_environment_type() ) $this->codh_header_tracking_scripts();
	}

	
	/**
	 * Outputs codh Javascript snippets in header
	 */
	public function codh_header_tracking_scripts()
	{
		?>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-LYQSEGKNLL"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-LYQSEGKNLL');
</script>

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
<script>
  window.Userback = window.Userback || {};
  Userback.access_token = 'A-EUVMMH15SL4hbGFcM1yEx7RO2';
  (function(d) {
    var s = d.createElement('script');s.async = true;s.src = 'https://static.userback.io/widget/v1.js';(d.head || d.body).appendChild(s);
  })(document);
</script>
  			<?php 
		}
		// Output codh Footer Scripts on production
		if( 'production' == wp_get_environment_type() ) $this->codh_footer_scripts();
	}

	
	/**
	 * Outputs codh Javascript snippets in footer
	 */
	public function codh_footer_scripts()
	{ 
		?>
		<!-- codh footer scripts -->
		<?php
	}


}

new codh_ThemeScripts();















