<?php 

return [
	/**
	 * To disable the pixel injection, set this to false.
	 */
	'inject-pixel'=>true,

	/**
	 * To disable injecting tracking links, set this to false.
	 */
	'track-links'=>true,

	/**
	 * Optionally expire old emails, set to 0 to keep forever.
	 */
	'expire-days'=>60,

	/**
	 * Where should the pingback URL route be?
	 */
    'route' => [
        'prefix' => 'email',
        'middleware' => [],
    ],

    /**
     * Where should the admin route be?
     */
    'admin-route' => [
        'prefix' => 'email-manager',
        'middleware' => 'super',
    ],

];
