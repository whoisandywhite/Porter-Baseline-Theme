<?php
/**
 * GFDefaultFormStyles Class
 */
class GFDefaultFormStyles
{
    /**
     * Constructor method.
     * Initializes the class by adding necessary filters for customizing Gravity Forms and ACF fields.
     */
    public function __construct()
    {
        // Define default form styles for Gravity Forms.
        add_filter('gform_default_styles', [$this, 'orbital_default_styles'], 10, 1);
    }

    /**
     * Define default form styles.
     * 
     * This function sets the default styles for Gravity Forms, including input sizes, colors, and other styling options.
     * 
     * @return string JSON encoded string representing the default style settings for Gravity Forms.
     */
    public function orbital_default_styles()
    {
        return '{"theme":"orbital","inputSize":"lg","inputBorderRadius":"0","inputBorderColor":"#007EB6","inputBackgroundColor":"#fff","inputColor":"#10202F","inputPrimaryColor":"#DC006C","inputImageChoiceAppearance":"card","inputImageChoiceStyle":"square","inputImageChoiceSize":"md","labelFontSize":"20","labelColor":"#10202F","descriptionFontSize":"16","descriptionColor":"#10202F","buttonPrimaryBackgroundColor":"#DC006C","buttonPrimaryColor":"#FFFFFF"}';    
    }
}

// Instantiate the GFDefaultFormStyles class.
new GFDefaultFormStyles();
