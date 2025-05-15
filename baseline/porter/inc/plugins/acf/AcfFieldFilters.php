<?php 
class AcfFieldFilters 
{
    // Gravity Forms select fields
    private $select_form_field_keys = [ 
        // 'field_66e832514a0bc',
    ]; 
    // Template Part select fields
    private $select_template_part_field_keys = [
        'field_66f538963325d' => [ // Mega Navigation Item
            'exclude_prefix' => [
                'rcp-',
            ],
            'exclude_core' => true,
        ],
        'field_6740de0828e9b' => [ // Relevanssi Related 
            'exclude_prefix' => [
                'rcp-',
            ],
            'exclude_core' => true,
        ],
        'field_677d0d47bfa7c' => [ // Manual selection
            'exclude_prefix' => [
                'rcp-',
            ],
            'exclude_core' => true,
        ],
        'field_6715413aeca43' => [ // Query carousel template
            'exclude_prefix' => [
                'rcp-',
            ],
            'exclude_core' => true,
        ]
    ];
    private $template_part_core_postnames = [
        'header',
        'footer',
    ];
    // Post type select fields
    private $select_post_type_field_keys = [
        'field_6716071e754c8',
    ];

    public function __construct() 
    {
        // Filter Gravity Forms field
        add_filter('acf/load_field', [$this, 'filter_acf_form_choices']);

        // Filter Template Part field
        add_filter('acf/load_field', [$this, 'filter_template_part_choices']);

        // Filter Post type field
        add_filter('acf/load_field', [$this, 'filter_post_type_choices']);

        
    }

    // Filter for active Gravity Forms
    public function filter_acf_form_choices($field) 
    {
        // Check if the field is a Gravity Forms select field
        if (!in_array($field['key'], $this->select_form_field_keys)) {
            return $field;
        }

        // Add the blank "Select Form" option
        $field['choices'] = ['' => 'Select Form'];

        // Get all active Gravity Forms
        $forms = GFAPI::get_forms();
        if ($forms) {
            foreach ($forms as $form) {
                if ($form['is_active'] && !$form['is_trash']) {
                    $field['choices'][$form['id']] = $form['title'];
                }
            }
        }

        return $field;
    }

    // Filter for active Template Parts
    public function filter_template_part_choices( $field )
    {
        // Check if the field is a Template Part select field
        if ( !in_array( $field['key'], array_keys( $this->select_template_part_field_keys))) {
            return $field;
        }

        $fieldSettings = $this->select_template_part_field_keys[$field['key']];

        $field['choices'] = [];

        // Query to get all wp_template_part posts categorized as 'general'
        $args = array(
            'post_type'      => 'wp_template_part',
            'posts_per_page' => -1,  // Get all posts
            'post_status'    => 'publish', // Only get published posts
            'orderby'        => 'title',
            'order'          => 'ASC',
        );

        $template_parts = get_posts( $args );

        // Parse template parts
        $template_part_names = !empty( $template_parts ) 
            ? $this->parse_template_parts( $template_parts, $fieldSettings ) 
            : [];

        // Get template part slugs from the /parts directory
        $directory_template_parts = $this->get_template_parts_from_directory();

        // Merge the slugs and ensure uniqueness
        $all_template_parts = array_unique(array_merge($template_part_names, $directory_template_parts));
        
        // Sort alphabetically by name
        sort($all_template_parts, SORT_STRING | SORT_FLAG_CASE);

        // Add the blank "Select Template Part" option
        $field['choices'][''] = 'Select Template Part';

        // Add the template parts to the field choices
        foreach ( $all_template_parts as $template_part_name ) {
            $field['choices'][$template_part_name] = $template_part_name;
        }

        return $field;
    }

    /**
     * Parse template parts
     * 
     * @param array $template_parts
     * @param array $fieldSettings
     * @return array
     */
    private function parse_template_parts($template_parts, $fieldSettings) {
        $template_part_names = [];
        foreach ($template_parts as $template_part) {
            // Exclude core template parts (header, footer)
            if (
                isset($fieldSettings['exclude_core']) && 
                (
                    in_array($template_part->post_name, $this->template_part_core_postnames) ||
                    preg_match('/^(header|footer)(_|-)?/', $template_part->post_name)
                )
            ) {
                continue;
            }
    
            // Exclude template parts with specific prefixes
            if (isset($fieldSettings['exclude_prefix'])) {
                foreach ($fieldSettings['exclude_prefix'] as $prefix) {
                    if (strpos($template_part->post_name, $prefix) === 0) {
                        // Skip this template part
                        continue 2;
                    }
                }
            }
    
            $template_part_names[] = $template_part->post_name;
        }
        return $template_part_names;
    }

    /**
     * Get template part slugs from the /parts directory
     * 
     * @return array
     */
    private function get_template_parts_from_directory() {
        $template_part_slugs = [];
        $parts_directory = get_stylesheet_directory() . '/parts';

        if ( is_dir( $parts_directory ) ) {
            $files = scandir( $parts_directory );

            foreach ( $files as $file ) {
                // Include only HTML files
                if ( pathinfo( $file, PATHINFO_EXTENSION ) === 'html' ) {
                    $template_part_slugs[] = pathinfo( $file, PATHINFO_FILENAME );
                }
            }
        }

        return $template_part_slugs;
    }

    /**
     * Filter for active Post Types
     * 
     * @param array $field
     * @return array
     */
    public function filter_post_type_choices($field)
    {
        // Check if the field is a Post Type select field
        if (!in_array($field['key'], $this->select_post_type_field_keys)) {
            return $field;
        }

        // Add the blank "Select Post Type" option
        $field['choices'] = ['' => 'Select Post Type'];

        // Get all public post types
        $post_types = porter_get_custom_post_types( true, 'object' );
        if ($post_types) {
            foreach ($post_types as $post_type) {
                $field['choices'][$post_type->name] = $post_type->label;
            }
        }

        return $field;
    }


}

if (is_admin()) new AcfFieldFilters();
