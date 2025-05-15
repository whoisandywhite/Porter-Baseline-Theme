<?php 
/**
* Google Maps API
*
* @package seetec
* @version 1.0.0
*/

class GoogleMapsApi
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
		// Filter for backend
		add_filter('acf/fields/google_map/api', [ $this, 'admin_acf_maps_key' ]);
	}

    // Include key for ACF backend
    public function admin_acf_maps_key($api)
    {
		$api['key'] = get_field( 'google_maps_api_key','option');
		return $api;
	}
}
new GoogleMapsApi();