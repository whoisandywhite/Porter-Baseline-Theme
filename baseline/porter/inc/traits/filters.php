<?php 
trait porter_filters_trait 
{
	/**
     * Generates and returns HTML for post filters based on specified post type and filter criteria.
     * 
     * @param string $postType The post type to generate filters for.
     * @param array $filters An array of filters to apply (e.g., meta, taxonomy).
     * @param array $filterSettings Settings for filter rendering and behavior.
     * @return string|null HTML content of the filters, or null if no filters are provided.
     */
	public function get_posttype_filters( $postType = 'post', $filters = [], $filterSettings = [] )
	{
		// Return early if no filters are provided
		if ( empty( $filters ) ) return;

		// Get the current page URL
		global $wp;
		$submitUrl = home_url( $wp->request );
		$action = '';
		// need to account for paged content
		global $paged;
		if( $paged )
		{
			// Regex pattern to match 'page/[number]' at the end of the URL
			$pattern = '/page\/\d+$/';
			$submitUrl = preg_replace( $pattern, '', $submitUrl );
			// Add the URL to the form as an action
			$action = "action='$submitUrl'";
		}

		// extract settings args
		extract( $filterSettings );

		$classes = 'porter-filters';
		if( $use_title_as_toggle ) $classes .= ' porter-filters--toggle-all';
		if( $toggle_each_filter_group ) $classes .= ' porter-filters--toggle-group';
		
		// Output filter form HTML
		ob_start();
		?>
		<div class="<?php echo $classes ?>">
			<?php # Filter title
			if ( isset( $filter_title ) ) echo wpautoheading( $filter_title, 'div', ['class'=>'porter-filters__title'] ) ?>
			
			<form class="porter-filters__form" <?php echo $action ?>>
				<div class="inner-content"> 
					<div class="porter-filter-list">
						<?php if( $include_search ) $this->generate_search_field(); ?>
						<?php if( is_search() ) echo '<input type="hidden" name="s" value="'.get_search_query().'">'; ?>
						<?php $this->generate_filters( $postType, $filters ) ?>
					</div>
					<div class="porter-filter-controls">
						<input class="porter-filter-controls__submit" type="submit" value="<?php echo isset( $submit_label ) ? __( $submit_label ) : __( "Submit" ); ?>" />
						<?php if ( isset( $_GET['filterPosts'])): ?>
							<a href="<?php echo $submitUrl ?>" class="porter-filter-controls__clear"><?php echo isset( $reset_label ) ? __( $reset_label ) : __( "Reset" ); ?></a>
						<?php endif ?>
					</div>
					<input type="hidden" name="queryId" value="999">
					<input type="hidden" name="filterPosts">
				</div>
			</form>

		</div>
		<?php
		return ob_get_clean();
	}



	
	/**
     * Outputs HTML for a search input field as part of the filter form.
     */
	public function generate_search_field() 
	{
		?>
		<div class="porter-filter porter-filter--search" role="search">
			<label class="search-label" for="porter-search-field"><span class="visually-hidden sr-only"><?php _e('Search','arlis') ?></span></label>
			<input class="porter-filter__text-field" name="ps" id="porter-search-field" type="text" placeholder="<?php _e('Search','arlis') ?>" value="<?php echo isset( $_GET['ps']) ? esc_html( $_GET['ps'] ) : ''; ?>">
		</div>
		<?php
	}


	/**
     * Generates and outputs filter form elements based on specified post type and filter criteria.
     * 
     * @param string $postType The post type for which filters are being generated.
     * @param array $filters An array detailing the filters to apply.
     */
	public function generate_filters( $postType, $filters )
	{
		if( empty( $filters ) ) return;

		// Iterate through each filter and generate appropriate HTML
		foreach ( $filters as $filter ) 
		{
			// Switch case to handle different filter types
			switch ( $filter['type']) 
			{
				case 'meta':
					$this->generate_meta_filter( $postType, $filter );
					break;
				
				case 'taxonomy':
					$this->generate_taxonomy_filter( $postType, $filter );
					break;

				case 'posttype':
					$this->generate_posttype_filter( $filter );
					break;
			}
		}
	}

	/**
     * Generates HTML for a meta-based filter for the specified post type.
     * 
     * @param string $postType The post type to generate the meta filter for.
     * @param array $filter Details of the meta filter to generate.
     */
	public function generate_meta_filter( $postType, $filter )
	{
		// Get meta values and output filter HTML
		$meta_values = $this->get_meta_values( $filter['meta_key'], $postType, true );

		if ( empty( $meta_values ) ) return;

		$meta_values = array_combine( $meta_values, $meta_values);
		$args = [
			'group' => 'meta',
			'key' => $filter['meta_key'],
			'group_label' => $filter['group_label']
		];
		?>
		<div class="porter-filter porter-filter--<?=$args['group']?>">
			<div class="porter-filter__label"><?php echo $filter['group_label'] ?></div>
			<div class="porter-filter__options">
				<?php # Render as
				switch ( $filter['render_as'] ) 
				{
					case 'select':
						$this->render_select_filter( $args, $meta_values );
						break;
					case 'radio':
						$this->render_radio_filter( $args, $meta_values );
						break;
					case 'checkbox':
						$this->render_checkbox_filter( $args, $meta_values );
						break;
				} ?>
			</div>
		</div>
		<?php
	}

	/**
     * Generates HTML for a taxonomy-based filter for the specified post type.
     * 
     * @param string $postType The post type to generate the taxonomy filter for.
     * @param array $filter Details of the taxonomy filter to generate.
     */
	public function generate_taxonomy_filter( $postType, $filter )
	{
		// Get taxonomy terms and output filter HTML
		$args = [
			'taxonomy' => $filter['taxonomy'],
			'hide_empty' => true,
			'post_types' => $postType,
		];

		// If we only want top level, just get that
		if( 'top-level' == $filter['hierarchy'] ) $args['parent'] = 0;

		// Get the terms
		$terms = get_terms( $args );

		// return if empty
		if( empty( $terms ) ) return;

		// If we want them nested, lets do that here
		if( 'nested' == $filter['hierarchy'] ) $terms = $this->sort_terms_hierarchicaly( $terms );

		$args = [
			'group' => 'pf-tax',
			'key' => $filter['taxonomy'],
			'group_label' => $filter['group_label']
		];

		?>
		<div class="porter-filter porter-filter--<?=$args['group']?>">
			<div class="porter-filter__label"><?php echo $filter['group_label'] ?></div>
			<div class="porter-filter__options">
				<?php // Render as
				switch ( $filter['render_as'] ) 
				{
					case 'select':
						$this->render_select_filter( $args, $terms );
						break;
					case 'radio':
						$this->render_radio_filter( $args, $terms );
						break;
					case 'checkbox':
						$this->render_checkbox_filter( $args, $terms );
						break;
				} ?>
			</div>
		</div>
		<?php
	}


	public function generate_posttype_filter( $filter )
	{
		// Post type filter only relevent on the search page
		if( !is_search()) return;

		// Get array of post types which exist in search results
		global $hns_search_result_type_counts;
		if( empty( $hns_search_result_type_counts ) ) return;

		$args = [
			'group' => 'pf-posttype',
			'key' => 'posttype',
			'group_label' => $filter['group_label']
		];
		?>
		<div class="porter-filter porter-filter--<?=$args['group']?>">
			<div class="porter-filter__label"><?php echo $filter['group_label'] ?></div>
			<div class="porter-filter__options">
				<?php // Render as
				switch ( $filter['render_as'] ) 
				{
					case 'select':
						$this->render_select_filter( $args, $hns_search_result_type_counts );
						break;
					case 'radio':
						$this->render_radio_filter( $args, $hns_search_result_type_counts );
						break;
					case 'checkbox':
						$this->render_checkbox_filter( $args, $hns_search_result_type_counts );
						break;
				} ?>
			</div>
		</div>
		<?php
	}

	
	/**
     * Recursively sorts an array of taxonomy terms into a hierarchical structure.
     * 
     * @param array $cats Array of taxonomy terms to sort.
     * @param integer $parentId The parent term ID to start sorting from.
     * @return array Sorted array of terms, with children nested under parents.
     */
	public function sort_terms_hierarchicaly(Array $cats, $parentId = 0)
	{
		$into = [];
		foreach ($cats as $i => $cat) {
			if ($cat->parent == $parentId) {
				$cat->children = $this->sort_terms_hierarchicaly($cats, $cat->term_id);
				$into[$cat->term_id] = $cat;
			}
		}
		return $into;
	}




	/**
	 * Renders a select dropdown for filtering posts.
	 * 
	 * @param array $args Associative array of arguments for the select field, including 'group' and 'key'.
	 * @param array $options Array of options to populate the select dropdown.
	 */
	public function render_select_filter( $args, $options )
	{
		extract( $args );
		?>
		<select name="<?php echo $group.'['.$key.']'; ?>" data-id="<?php echo sanitize_title("$group--$key") ?>">
			<option value=""><?php _e("Select $group_label") ?></option>
			<?php // Loop through the options and render the html options
			foreach ( $options as $optKey => $option ) {
				$this->render_select_option( $args, $optKey, $option );
			} ?>
		</select>
		<?php
	}

	/**
	 * Renders an individual option for a select dropdown.
	 * 
	 * @param array $args Associative array of arguments including 'group', 'key', and any other relevant data.
	 * @param string|int $optKey The key or identifier for the option, used for the value attribute.
	 * @param mixed $option The option to be rendered, typically an object or associative array for taxonomy terms, or simply the display text for meta values.
	 * @param int $level (optional) Depth level for nested options, used for indentation (e.g., in hierarchical taxonomies). Defaults to 0 for top level.
	 */
	public function render_select_option( $args, $optKey, $option, $level = 0 )
	{
		extract( $args );

		// Taxonomy fields
		if( 'pf-tax' == $group )
		{	
			$padding = '';
			$i = $level;
			while ($i > 0) { $padding .= '&nbsp;&nbsp;- '; $i--;}

			echo sprintf(
				"<option value='%s' %s>%s</option>",
				esc_attr( $option->term_id ),
				$this->is_option_selected( $group, $key, $option->term_id ),
				esc_html( $padding.strip_tags($option->name) )
			);

			if( !empty( $option->children ) ) {
				foreach( $option->children as $childOptKey => $childOption ) {
					$this->render_select_option( $args, $childOptKey, $childOption, $level+1 );
				}
			}
		}

		// Meta fields
		if( 'meta' == $group )
		{
			echo sprintf(
				"<option value='%s' %s>%s</option>",
				esc_attr( $optKey ),
				$this->is_option_selected( $group, $key, $optKey ),
				esc_html( $option )
			);
		}

		// Posttype fields
		if( 'posttype' == $group )
		{
			echo sprintf(
				"<option value='%s' %s>%s</option>",
				esc_attr( $optKey ),
				$this->is_option_selected( $group, $key, $optKey ),
				esc_html( $option )
			);
		}
	}




	/**
	 * Renders radio buttons for filtering posts.
	 * 
	 * @param array $args Associative array of arguments for the radio buttons, including 'group' and 'key'.
	 * @param array $options Array of options to create radio buttons for.
	 */
	public function render_radio_filter( $args, $options )
	{
		extract( $args );
		// Loop through the options
		foreach ( $options as $optKey => $option ) {
			$this->render_radio_option( $args, $optKey, $option );
		}
	}
	
	/**
	 * Renders an individual radio button option.
	 * 
	 * @param array $args Associative array of arguments including 'group', 'key', and any other relevant data.
	 * @param string|int $optKey The key or identifier for the option, used for the value attribute.
	 * @param mixed $option The option to be rendered, typically an object or associative array for taxonomy terms, or simply the display text for meta values.
	 * @param int $level (optional) Depth level for nested options, used for indentation (e.g., in hierarchical taxonomies). Defaults to 0 for top level.
	 */
	public function render_radio_option( $args, $optKey, $option, $level = 0 )
	{
		extract( $args );

		// Taxonomy fields
		if( 'pf-tax' == $group )
		{	
			$padding = '';
			$i = $level;
			while ($i > 0) { $padding .= '&nbsp;&nbsp;'; $i--;}

			echo sprintf(
				'<div class="porter-radio-option"><label>%s<input type="radio" name="%s" value="%s" %s> <span>%s</span></label>',
				$padding,
				$group.'['.$key.'][]',
				esc_attr( $option->term_id ),
				$this->is_option_selected( $group, $key, $option->term_id, 'checked' ),
				esc_html( strip_tags($option->name) )
			);

			if( !empty( $option->children ) ) {
				foreach( $option->children as $childOptKey => $childOption ) {
					$this->render_radio_option( $args, $childOptKey, $childOption, $level+1 );
				}
			}

			echo '</div>';
		}

		// Meta Fields
		if( 'meta' == $group )
		{
			echo sprintf(
				'<div class="porter-radio-option"><label><input type="radio" name="%s" value="%s" %s> <span>%s</span></label></div>',
				$group.'['.$key.'][]',
				esc_attr( $optKey ),
				$this->is_option_selected( $group, $key, $optKey, 'checked' ),
				esc_html( $option )
			);
		}

		// Posttype Fields
		if( 'pf-posttype' == $group )
		{
			echo sprintf(
				'<div class="porter-radio-option"><label><input type="radio" name="%s" value="%s" %s> <span>%s</span></label></div>',
				$group.'['.$key.'][]',
				esc_attr( $optKey ),
				$this->is_option_selected( $group, $key, $optKey, 'checked' ),
				esc_html( $option )
			);
		}
	}


	/**
	 * Renders checkbox inputs for filtering posts.
	 * 
	 * @param array $args Associative array of arguments for the checkboxes, including 'group' and 'key'.
	 * @param array $options Array of options to create checkboxes for.
	 */
	public function render_checkbox_filter( $args, $options )
	{
		extract( $args );
		// Loop through the options
		foreach ( $options as $optKey => $option ) {
			$this->render_checkbox_option( $args, $optKey, $option );
		}
	}

	/**
	 * Renders an individual checkbox option.
	 * 
	 * @param array $args Associative array of arguments including 'group', 'key', and any other relevant data.
	 * @param string|int $optKey The key or identifier for the option, used for the value attribute.
	 * @param mixed $option The option to be rendered, typically an object or associative array for taxonomy terms, or simply the display text for meta values.
	 * @param int $level (optional) Depth level for nested options, used for indentation (e.g., in hierarchical taxonomies). Defaults to 0 for top level.
	 */
	public function render_checkbox_option( $args, $optKey, $option, $level = 0 )
	{
		extract( $args );

		// Taxonomy fields
		if( 'pf-tax' == $group )
		{	
			$padding = '';
			$i = $level;
			while ($i > 0) { $padding .= '&nbsp;&nbsp;'; $i--;}

			echo sprintf(
				'<div class="porter-checkbox-option"><label>%s<input type="checkbox" name="%s" value="%s" %s> <span>%s</span></label>',
				$padding,
				$group.'['.$key.'][]',
				esc_attr( $option->term_id ),
				$this->is_option_selected( $group, $key, $option->term_id, 'checked' ),
				esc_html( strip_tags($option->name) )
			);

			if( !empty( $option->children ) ) {
				foreach( $option->children as $childOptKey => $childOption ) {
					$this->render_checkbox_option( $args, $childOptKey, $childOption, $level+1 );
				}
			}

			echo '</div>';
		}

		// Meta Fields
		if( 'meta' == $group )
		{
			echo sprintf(
				'<div class="porter-checkbox-option"><label><input type="checkbox" name="%s" value="%s" %s> <span>%s</span></label></div>',
				$group.'['.$key.'][]',
				esc_attr( $optKey ),
				$this->is_option_selected( $group, $key, $optKey, 'checked' ),
				esc_html( $option )
			);
		}

		// Posttype Fields
		if( 'pf-posttype' == $group )
		{
			echo sprintf(
				'<div class="porter-checkbox-option"><label><input type="checkbox" name="%s" value="%s" %s> <span>%s</span></label></div>',
				$group.'['.$key.'][]',
				esc_attr( $optKey ),
				$this->is_option_selected( $group, $key, $optKey, 'checked' ),
				esc_html( $option )
			);
		}
	}



	/**
     * Checks if a specific filter option is selected.
     *
     * @param string $group The filter group.
     * @param string $key The filter key.
     * @param string $value The value to check.
     * @param string $returnString The string to return if the option is selected.
     * @return mixed Returns $returnString if selected, false otherwise.
     */
	public function is_option_selected( $group, $key, $value, $returnString = 'selected' )
	{
		// Check if a filter option is selected
		if ( !isset( $_GET[$group] ) ) return false;

		if ( array_key_exists( $key , $_GET[$group] ) 
			&& !empty( $_GET[$group][$key] )
		) {
			if ( is_array( $_GET[$group][$key] ) ) {
				if( in_array( $value, $_GET[$group][$key] ) ) return $returnString;
			} else {
				if( $value == $_GET[$group][$key] ) return $returnString;
			}
		}
		return false;
	}


	/**
     * Parses the filter query and amends it based on the given criteria.
     *
     * @param array $query The original query.
     * @return array Amended query.
     */
	public function parse_filter_query( $query )
	{
		// Parse and amend the query based on filters
		$amended_query = [];
		$amended_query = $this->parse_tax_query( $query );
		$amended_query = $this->parse_meta_query( $amended_query );
		$amended_query = $this->parse_posttype_query( $amended_query );
		$amended_query = $this->parse_search_query( $amended_query );

		// echo '<pre>';
		// print_r( $amended_query );
		// echo '</pre>';

		return array_merge(
			$query,
			$amended_query
		);
	}

	/**
     * Parses taxonomy query parameters and amends the query.
     *
     * @param array $query The original query.
     * @return array Amended query.
     */
	public function parse_tax_query( $query )
	{
		$amended_query = [];

		if ( isset( $_GET['pf-tax'] ) && !empty( $_GET['pf-tax']) ) 
		{
			$appendTaxQuery = [];

			foreach ( $_GET['pf-tax'] as $taxName => $termId ) 
			{
				if( empty( $termId ) || -1 == $termId ) continue;

				$taxName = sanitize_title( $taxName );
				
				if ( !is_array( $termId )) $termId = $termId;

				$appendTaxQuery[] = [
					'taxonomy' => $taxName,
					'field' => 'term_id',
					'terms' => $termId,
				];
			}

		}

		if ( !empty( $appendTaxQuery )) 
		{
			$appendTaxQuery['relation'] = 'AND';
			$amended_query['tax_query'] = $appendTaxQuery;
		}

		return array_merge(
			$query,
			$amended_query
		);
	}

	/**
     * Parses meta query parameters and amends the query.
     *
     * @param array $query The original query.
     * @return array Amended query.
     */
	public function parse_meta_query( $query )
	{
		$amended_query = [];

		if ( isset( $_GET['meta'] ) && !empty( $_GET['meta']) ) 
		{
			$appendMetaQuery = [];

			foreach ( $_GET['meta'] as $metaKey => $metaValue ) 
			{
				if ( empty( $metaValue ) ) continue;

				$metaKey = sanitize_title( $metaKey );
				$compare = is_array( $metaValue ) ? 'IN' : '=';

				$appendMetaQuery[] = [
					'key' => $metaKey,
					'compare' => $compare,
					'value' => $metaValue,
				];
			}

		}

		if ( !empty( $appendMetaQuery )) 
		{
			$appendMetaQuery['relation'] = 'AND';
			$amended_query['meta_query'] = $appendMetaQuery;
		}

		return array_merge(
			$query,
			$amended_query
		);
	}

	/**
     * Parses posttype query parameters and amends the query.
     *
     * @param array $query The original query.
     * @return array Amended query.
     */
	public function parse_posttype_query( $query )
	{
		$amended_query = [];

		if ( isset( $_GET['pf-posttype']['posttype'] ) && !empty( $_GET['pf-posttype']['posttype']) ) 
		{
			$posttypeFilters = [];

			foreach ( $_GET['pf-posttype']['posttype'] as $ptName ) 
			{
				if( empty( $ptName ) ) continue;
				$posttypeFilters[] = sanitize_title( $ptName );
			}

		}

		if ( !empty( $posttypeFilters )) 
		{
			$amended_query['post_type'] = $posttypeFilters;
		}

		return array_merge(
			$query,
			$amended_query
		);
	}

	/**
     * Parses search query parameters and amends the query.
     *
     * @param array $query The original query.
     * @return array Amended query.
     */
	public function parse_search_query( $query )
	{
		$amended_query = [];

		if ( isset( $_GET['ps'] ) && !empty( $_GET['ps']) ) 
		{
			$amended_query['s'] = sanitize_text_field( $_GET['ps'] );
			$amended_query['relevanssi'] = 1;
		}

		return array_merge(
			$query,
			$amended_query
		);
	}

	/**
     * Generates a select filter for post types.
     *
     * @param array $post_types Array of post types.
     * @return string HTML content of the select filter.
     */
	public function get_type_select_filters( $post_types )
	{
		if ( empty( $post_types ) ) return;

		$defaultSelected = ( !isset( $_GET['type']) || empty( $_GET['type'] ) ) ? 'selected' : ''; 
		ob_start(); ?>
		<div>
			<select class="porter-select" name="type" data-id="<?php echo sanitize_title("porter-posttype") ?>">
				<option value="" <?= $defaultSelected ?>><?php _e( 'Type') ?></option>
				<?php # Loop through the post_types
				foreach ( $post_types as $posttype_name ) 
				{
					$post_type = get_post_type_object( $posttype_name );
					if ( ! is_a( $post_type , 'WP_Post_Type' ) ) continue;

					$is_selected = ( isset( $_GET['type'] ) && ( $posttype_name == $_GET['type'] ) ) ? 'selected' : '';
					echo sprintf(
						"<option value='%s' %s>%s</option>",
						$posttype_name,
						$is_selected,
						$post_type->labels->singular_name
					);
				} ?>
			</select>
		</div>
		<?php 
		return ob_get_clean();
	}



	/**
     * Retrieves meta values for a given meta key and post type.
     *
     * @param string $meta_key The meta key.
     * @param string $post_type The post type.
     * @param bool $distinct Whether to retrieve distinct values only.
     * @param string $post_status The status of the post.
     * @return mixed Array of meta values or error message.
     */
	public function get_meta_values( string $meta_key, string $post_type = 'post', bool $distinct = false, string $post_status = 'publish' ) {
	    
	    global $wpdb, $wp_post_types;
	    
	    if( !isset( $wp_post_types[$post_type] ) ) {
			error_log( 'Invalid post type.' );
			return false; 
		}
	    
	    $transient_key = 'get_' . $wp_post_types[$post_type]->name . '_type_' . $meta_key . '_meta_values';
	   
	    $get_meta_values = get_transient( $transient_key );

	    if( true === (bool)$get_meta_values )
	        return $get_meta_values;
	    
	    $distinct = $distinct ? ' DISTINCT' : '';
	    
	    $get_meta_values = $wpdb->get_col( $wpdb->prepare( "
	        SELECT{$distinct} pm.meta_value FROM {$wpdb->postmeta} pm 
	        LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id 
	        WHERE pm.meta_key = %s 
	        AND p.post_type = %s 
	        AND p.post_status = %s 
	    ", $meta_key, $post_type, $post_status ) );
	    
	    set_transient( $transient_key, $get_meta_values, DAY_IN_SECONDS );

	    return $get_meta_values;
	}

}






