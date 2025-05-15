<?php 
class codh_Restrict_by_user_role
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
		// Remove setings for non admins
		add_action( 'init', [ $this, 'maybe_filter_theme_json' ] );
	}

    public function maybe_filter_theme_json()
    {
        // Check to make sure the theme has a theme.json file
        if ( wp_theme_has_theme_json() ) {
            add_filter( 'wp_theme_json_data_user', [$this, 'filter_theme_json_user_options'], 10, 1 );
        }
    }

    public function filter_theme_json_user_options( $theme_json )
    {
        // admins get default settings
        if ( current_user_can( 'edit_theme_options' ) ) return $theme_json;

        // everyone else gets settings removed
        $limited_options = [
            "version"  => 2,
            "settings" => [
                "appearanceTools" => false,
                "border" => [
                    "color" => false,
                    "radius" => false,
                    "style" => false,
                    "width" => false,
                ],
                "lightbox" => [
                    "enabled" => false,
                ],
                "position" => [
                    "sticky" => false,  
                ],
                "dimensions" => [
                    "aspectRatio" => false,
                    "minHeight" => false,
                ],
                "spacing" => [
                    "blockGap" => false,
                    "margin" => false,
                    "padding" => false,
                    "customSpacingSize" => false,
                ],
                "typography" => [
                    "customFontSize" => false,
                    "fontStyle" => false,
                    "fontWeight" => false,
                    "fluid" => false,
                    "letterSpacing" => false,
                    "lineHeight" => false,
                    "textColumns" => false,
                    "textDecoration" => false,
                    "writingMode" => false,
                    "textTransform" => false,
                ],
            ],
        ];

        $theme_json->update_with( $limited_options );

        return $theme_json;
        
    }


}
new codh_Restrict_by_user_role();
