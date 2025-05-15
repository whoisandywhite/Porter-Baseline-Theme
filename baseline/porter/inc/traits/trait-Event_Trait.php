<?php 
trait Event_Trait {

	function get_display_date( $post_id )
	{
        // $start_date = get_field( 'start_date', $post_id );
		$start_date = get_post_meta( $post_id, 'start_date', true );
        $start = strtotime( $start_date );
		$start_r = [
			'd' => date( 'd', $start ),
			'm' => date( 'm', $start ),
			'y' => date( 'Y', $start ),
		];
        // $end_date = get_field( 'end_date', $post_id );
		$end_date = get_post_meta( $post_id, 'end_date', true );
        $end = strtotime( $end_date );
		$end_r = [
			'd' => date( 'd', $end ),
			'm' => date( 'm', $end ),
			'y' => date( 'Y', $end ),
		];

		// dates match
		// get default wordpress date format
		if ( $start == $end ) return date(  get_option( 'date_format' ), $start );

		// what matches?
		$yearsMatch 	= ( $start_r['y'] == $end_r['y']) ? true : false;
		$monthsMatch 	= ( $start_r['m'] == $end_r['m']) ? true : false;
		$daysMatch 		= ( $start_r['d'] == $end_r['d']) ? true : false;

		// different years
		if ( !$yearsMatch ) return date(  get_option( 'date_format' ), $start ) . ' - ' . date(  get_option( 'date_format' ), $end );

		// different months
		if ( !$monthsMatch ) return date( 'd F', $start ) . ' - ' . date( 'd F Y', $end );

		// different days
		if ( !$daysMatch ) return date( 'd F', $start ) . ' - ' . date( 'd F Y', $end );

		return print_r( $start_r, true ) . ' - ' . print_r( $end_r, true );
	}

	// Method to display event times with customizable format
    function get_display_times( $post_id, $format = '%s - %s' ) 
	{
        $start_time = get_field('start_time', $post_id);
        $end_time = get_field('end_time', $post_id);

		if( empty( $start_time ) || empty( $end_time ) ) return;

        // Return formatted time
        return sprintf($format, $start_time, $end_time);
    }


	function generate_google_maps_directions_link($location) 
	{
		if( !isset($location['lat']) || !isset($location['lng']) ) return;

		$base_url = 'https://www.google.com/maps/dir/?api=1';
		
		// Use lat/lng to generate the destination
		$destination = 'destination=' . urlencode($location['lat'] . ',' . $location['lng']);
		
		return $base_url . '&' . $destination;
	}
}