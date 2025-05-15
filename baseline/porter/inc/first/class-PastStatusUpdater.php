<?php 
/**
 * Past Status Updater
 *
 * Drop this into your theme's functions.php or as an MU‐plugin.
 * 
 * Features:
 *  - Registers a new "past_{post_type}" status.
 *  - Hourly WP‐Cron to flip any posts whose date is in the past.
 *  - "Past" admin filter tab on the edit screen.
 *  - Manual triggers via /wp-admin/?update{PostType}=1 or ?migrate{PostType}=1.
 */

 /**
 * Example: Event past‐status updater.
 * Replace 'event' / 'end_date' / 'event_has_past' / 'past_event' 
 * with your own CPT, meta-key, and status slug.
 */

    // class EventPastStatusUpdater extends PastStatusUpdater {
    //     protected function get_meta_key()   { return 'event_has_past'; }
    //     protected function get_post_type()  { return 'event';          }
    //     protected function get_cron_hook()  { return 'update_event_past_status'; }
    //     protected function get_status()     { return 'past_event';     }
    //     protected function get_date_source(\WP_Post $post) {
    //         return get_post_meta( $post->ID, 'end_date', true );
    //     }
    // }
    // new EventPastStatusUpdater();


 if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Base class for registering statuses and scheduling updates.
 */

 abstract class PastStatusUpdater {
    abstract protected function get_meta_key();
    abstract protected function get_post_type();
    abstract protected function get_cron_hook();
    abstract protected function get_status();
    abstract protected function get_date_source( \WP_Post $post );

    public function __construct() {
        // 1) Register status & add rewrite for single URLs
        add_action( 'init',          [ $this, 'register_post_status' ], 20 );
        add_filter( 'views_edit-' . $this->get_post_type(), [ $this, 'add_admin_filter' ] );

        // 2) Ensure main query for single post includes "past_*"
        add_filter( 'request',       [ $this, 'filter_request' ] );

        // 3) Schedule & hook the cron
        add_action( $this->get_cron_hook(), [ $this, 'update_status' ] );
        if ( ! wp_next_scheduled( $this->get_cron_hook() ) ) {
            wp_schedule_event( time(), 'hourly', $this->get_cron_hook() );
        }

        // 4) Manual trigger URLs
        add_action( 'admin_init', [ $this, 'handle_manual_triggers' ] );
    }

    /**
     * Register the custom past_* status and add a rewrite rule based on CPT slug
     */
    public function register_post_status() {
        $status = $this->get_status();
        $pt     = $this->get_post_type();

        register_post_status( $status, [
            'label'                     => _x( ucfirst( str_replace( '_', ' ', $status ) ), 'status label' ),
            'public'                    => false,
            'publicly_queryable'        => true,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => false,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                ucfirst( str_replace( '_', ' ', $status ) ) . ' <span class="count">(%s)</span>',
                ucfirst( str_replace( '_', ' ', $status ) ) . ' <span class="count">(%s)</span>'
            ),
        ] );

        // Add rewrite rule so /{cpt_slug}/{postname}/ works
        $pt_obj = get_post_type_object( $pt );
        $slug   = ( $pt_obj && ! empty( $pt_obj->rewrite['slug'] ) )
                  ? $pt_obj->rewrite['slug']
                  : $pt;

        add_rewrite_rule(
            sprintf( '%s/([^/]+)/?$', preg_quote( $slug, '/' ) ),
            sprintf( 'index.php?post_type=%s&name=$matches[1]', $pt ),
            'top'
        );

        // Flush rules once
        if ( ! get_option( 'past_status_rewrites_flushed', false ) ) {
            flush_rewrite_rules();
            update_option( 'past_status_rewrites_flushed', true );
        }
    }

    /**
     * Inject post_status into query when resolving single CPT URLs
     */
    public function filter_request( $query_vars ) {
        if ( ! is_admin()
          && isset( $query_vars['post_type'], $query_vars['name'] )
          && $query_vars['post_type'] === $this->get_post_type()
        ) {
            $query_vars['post_status'] = [ 'publish', $this->get_status() ];
        }
        return $query_vars;
    }

    public function add_admin_filter( $views ) {
        $pt     = $this->get_post_type();
        $status = $this->get_status();

        $count = wp_count_posts( $pt )->{ $status } ?? 0;
        $url   = add_query_arg(
            [ 'post_status' => $status, 'post_type' => $pt ],
            admin_url( 'edit.php' )
        );
        $class = ( get_query_var('post_status') === $status ) ? 'current' : '';

        $views[ $status ] = sprintf(
            '<a href="%1$s" class="%2$s">%3$s</a>',
            esc_url( $url ),
            esc_attr( $class ),
            sprintf( '%s (%s)',
                ucfirst( str_replace('_',' ',$status) ),
                number_format_i18n( $count )
            )
        );

        return $views;
    }

    public function update_status() {
        $pt     = $this->get_post_type();
        $status = $this->get_status();
    
        // If your date meta is saved as YYYYMMDD (8 digits), compare to date('Ymd')
        $current_ymd = date( 'Ymd' );
        $now_ts      = time();
    
        $q = new WP_Query([
            'post_type'      => $pt,
            'posts_per_page' => -1,
            'post_status'    => [ 'publish', $status ],
        ]);
    
        foreach ( $q->posts as $post ) {
            $raw = $this->get_date_source( $post );
            if ( ! $raw ) {
                continue;
            }
    
            $should_flip = false;
    
            if ( ctype_digit( $raw ) && strlen( $raw ) === 8 ) {
                // Treat as YYYYMMDD
                if ( (int) $raw < (int) $current_ymd ) {
                    $should_flip = true;
                }
            } else {
                // Treat as timestamp or parseable date string
                if ( ctype_digit( $raw ) && strlen( $raw ) > 8 ) {
                    // probably a Unix timestamp
                    $ts = (int) $raw;
                } else {
                    $ts = strtotime( $raw );
                }
                if ( $ts && $ts < $now_ts ) {
                    $should_flip = true;
                }
            }
    
            if ( $should_flip ) {
                // preserve taxonomies
                $preserve = [];
                foreach ( get_object_taxonomies( $pt ) as $tax ) {
                    $terms = wp_get_object_terms( $post->ID, $tax, ['fields'=>'slugs'] );
                    if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                        $preserve[ $tax ] = $terms;
                    }
                }
    
                // flip status
                wp_update_post([
                    'ID'          => $post->ID,
                    'post_status' => $status,
                ]);
    
                // reassign preserved terms
                foreach ( $preserve as $tax => $terms ) {
                    wp_set_object_terms( $post->ID, $terms, $tax, false );
                }
    
                // drop old meta flag
                delete_post_meta( $post->ID, $this->get_meta_key() );
            }
        }
    }
    

    public function migrate_existing() {
        $meta_key = $this->get_meta_key();
        $status   = $this->get_status();
        $pt       = $this->get_post_type();

        $q = new WP_Query([
            'post_type'      => $pt,
            'posts_per_page' => -1,
            'meta_query'     => [
                [ 'key' => $meta_key, 'value' => '1', 'compare' => '=' ],
            ],
        ]);

        foreach ( $q->posts as $post ) {
            // preserve only non-empty taxonomies
            $preserve = [];
            foreach ( get_object_taxonomies( $pt ) as $tax ) {
                $terms = wp_get_object_terms( $post->ID, $tax, ['fields'=>'slugs'] );
                if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                    $preserve[ $tax ] = $terms;
                }
            }

            wp_update_post([
                'ID'          => $post->ID,
                'post_status' => $status,
            ]);

            foreach ( $preserve as $tax => $terms ) {
                wp_set_object_terms( $post->ID, $terms, $tax, false );
            }

            delete_post_meta( $post->ID, $meta_key );
        }
    }

    public function handle_manual_triggers() {
        $pt = ucfirst( $this->get_post_type() );

        if ( isset( $_GET[ 'update' . $pt ] ) ) {
            $this->update_status();
            wp_die( "{$pt} status updated." );
        }
        if ( isset( $_GET[ 'migrate' . $pt ] ) ) {
            $this->migrate_existing();
            wp_die( "{$pt} migration completed." );
        }
    }
}