<?php
/**
 * Class JobTaxonomyCron
 *
 * Assigns taxonomies to "job" posts based on Gravity Forms entry values.
 * Uses wp-cron to process posts where 'has_run_job_taxonomies' is not set or false.
 */
class JobTaxonomyCron {

	/**
	 * Hook into cron and schedule the event.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'schedule_cron' ] );
		add_action( 'job_taxonomy_cron_hook', [ $this, 'process_jobs' ] );
	}

	/**
	 * Schedule the cron job to run hourly if not already scheduled.
	 */
	public function schedule_cron() {
		if ( ! wp_next_scheduled( 'job_taxonomy_cron_hook' ) ) {
			wp_schedule_event( time(), 'hourly', 'job_taxonomy_cron_hook' );
		}
	}

	/**
	 * The main callback to process job posts and assign taxonomies.
	 */
	public function process_jobs() {
		if ( ! class_exists( 'GFAPI' ) ) {
			error_log( "JobTaxonomyCron: GFAPI not available." );
			return;
		}

		$job_posts = get_posts( [
			'post_type'      => 'job',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'meta_query'     => [
				'relation' => 'OR',
				[
					'key'     => 'has_run_job_taxonomies',
					'compare' => 'NOT EXISTS'
				],
				[
					'key'   => 'has_run_job_taxonomies',
					'value' => '0'
				],
				[
					'key'   => 'has_run_job_taxonomies',
					'value' => false
				],
			],
		] );

		foreach ( $job_posts as $post ) {
			$post_id  = $post->ID;
			$entry_id = get_post_meta( $post_id, 'gf_entry_id', true );

			if ( ! $entry_id ) {
				error_log( "JobTaxonomyCron: No gf_entry_id for post ID {$post_id}." );
				update_post_meta( $post_id, 'has_run_job_taxonomies', true );
				continue;
			}

			$entry = GFAPI::get_entry( $entry_id );
			if ( is_wp_error( $entry ) || ! $entry ) {
				error_log( "JobTaxonomyCron: Could not retrieve entry {$entry_id} for post {$post_id}." );
				update_post_meta( $post_id, 'has_run_job_taxonomies', true );
				continue;
			}

			$form = GFAPI::get_form( $entry['form_id'] );
			if ( ! $form || empty( $form['fields'] ) ) {
				error_log( "JobTaxonomyCron: Could not retrieve form for entry {$entry_id}." );
				update_post_meta( $post_id, 'has_run_job_taxonomies', true );
				continue;
			}

			foreach ( $form['fields'] as $field ) {
				if ( empty( $field->inputName ) || strpos( $field->inputName, 'choices_taxonomy_' ) !== 0 ) {
					continue;
				}

				$taxonomy = str_replace( 'choices_taxonomy_', '', $field->inputName );

				if ( in_array( $field->type, [ 'checkbox', 'multiselect' ], true ) ) {
					$value = $field->get_value_export( $entry );
				} else {
					$value = rgar( $entry, (string) $field->id );
				}

				if ( empty( $value ) ) {
					continue;
				}

				$terms = is_array( $value ) ? $value : array_map( 'trim', explode( ',', $value ) );

				$term_ids = [];
				foreach ( $terms as $term ) {
					if ( is_numeric( $term ) ) {
						$term_ids[] = (int) $term;
					} else {
						$obj = get_term_by( 'slug', $term, $taxonomy ) ?: get_term_by( 'name', $term, $taxonomy );
						if ( $obj ) {
							$term_ids[] = $obj->term_id;
						} else {
							error_log( "JobTaxonomyCron: Term '{$term}' not found in '{$taxonomy}' for post {$post_id}." );
						}
					}
				}

				if ( ! empty( $term_ids ) ) {
					$result = wp_set_post_terms( $post_id, $term_ids, $taxonomy, false );
					if ( is_wp_error( $result ) ) {
						error_log( "JobTaxonomyCron: Failed to assign terms for post {$post_id} ({$taxonomy}): " . $result->get_error_message() );
					} else {
						error_log( "JobTaxonomyCron: Assigned terms to post {$post_id} for taxonomy '{$taxonomy}'." );
					}
				}
			}

			update_post_meta( $post_id, 'has_run_job_taxonomies', true );
		}
	}
}

// Initialize the class
new JobTaxonomyCron();
