<?php
class GravityFormsFieldFilter
{
    public function __construct()
    {
        // Hook into Gravity Forms' field pre-render filter
        add_filter('gform_pre_render', [$this, 'populateFieldChoices']);
        add_filter('gform_pre_validation', [$this, 'populateFieldChoices']);
        add_filter('gform_pre_submission_filter', [$this, 'populateFieldChoices']);
        add_filter('gform_admin_pre_render', [$this, 'populateFieldChoices']);
    }

    /**
     * Populates the choices for multiple choice fields like checkbox, select, radio.
     *
     * @param array $form Gravity Forms form object.
     * @return array Modified form object.
     */
    public function populateFieldChoices($form)
    {
        foreach ($form['fields'] as &$field) {
            // Handle multiple choice field types (select, checkbox, radio)
            if (in_array($field->type, ['checkbox', 'select', 'radio'])) {
                $this->populateChoices($field);
            }
        }

        return $form;
    }

    /**
     * Populates the field choices based on the input name prefix.
     *
     * @param object $field The field object.
     */
    private function populateChoices(&$field)
    {
        if (!isset($field->inputName)) {
            return;
        }

        if (strpos($field->inputName, 'choices_') === 0) {
            $key = str_replace('choices_', '', $field->inputName);

            if (strpos($key, 'taxonomy_') === 0) {
                $taxonomy = str_replace('taxonomy_', '', $key);
                $choices = $this->getTaxonomyChoices($taxonomy);
            } elseif (strpos($key, 'posttype_') === 0) {
                $posttype = str_replace('posttype_', '', $key);
                $choices = $this->getPostTypeChoices($posttype);
            } else {
                return;
            }

            // Update field choices
            $field->choices = $choices;

            // If the field is a checkbox, update the inputs array
            if ($field->type === 'checkbox') {
                $field->inputs = $this->generateCheckboxInputs($field->id, $choices);
            }
        }
    }

    /**
     * Generates the inputs array for checkbox fields.
     *
     * @param int $fieldId The ID of the field.
     * @param array $choices The choices array.
     * @return array The inputs array.
     */
    private function generateCheckboxInputs($fieldId, $choices)
    {
        $inputs = [];
        foreach ($choices as $index => $choice) {
            $inputs[] = [
                'id' => "{$fieldId}." . ($index + 1),
                'label' => $choice['text'],
                'name' => '',
            ];
        }
        return $inputs;
    }

    /**
     * Retrieves choices for a specific taxonomy.
     * If the "Taxonomy Terms Order" or "Advanced Taxonomy Terms Order" plugin is active, respects custom ordering.
     * Falls back to alphabetical order otherwise.
     *
     * @param string $taxonomy The taxonomy name.
     * @return array Choices array formatted for Gravity Forms.
     */
    private function getTaxonomyChoices($taxonomy)
    {
        $use_custom_order = defined('CPT_TAX_ORDER_VERSION') || defined('ATTO_PRODUCT_ID');

        $args = [
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
        ];

        if ($use_custom_order) {
            $args['orderby'] = 'term_order';
        }

        $terms = get_terms($args);

        if (is_wp_error($terms) || empty($terms)) {
            return [];
        }

        // Fallback: If plugin not active, sort alphabetically
        if (!$use_custom_order) {
            usort($terms, function($a, $b) {
                return strcasecmp($a->name, $b->name);
            });
        }

        $choices = [];
        foreach ($terms as $term) {
            $choices[] = [
                'text'  => $term->name,
                'value' => $term->term_id,
            ];
        }

        return $choices;
    }


    /**
     * Retrieves choices for a specific post type.
     *
     * @param string $posttype The post type name.
     * @return array Choices array formatted for Gravity Forms.
     */
    private function getPostTypeChoices($posttype)
    {
        $posts = get_posts([
            'post_type' => $posttype,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        if (empty($posts)) {
            return [];
        }

        $choices = [];
        foreach ($posts as $post) {
            $choices[] = [
                'text' => $post->post_title,
                'value' => $post->ID,
            ];
        }

        return $choices;
    }
}

new GravityFormsFieldFilter();
