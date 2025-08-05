<?php

namespace ThePaste\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Handles the plugin's updates from GitHub.
 */
class Updater extends Singleton {

    const GITHUB_API_URL = 'https://api.github.com/repos/sadewadee/the-paste/releases/latest';
    const TRANSIENT_KEY = 'the_paste_update_check';

    private $plugin_slug;

    /**
     * Constructor.
     */
    protected function __construct( $plugin_slug ) {
        $this->plugin_slug = $plugin_slug;
        add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_for_update' ] );
        add_filter( 'plugins_api', [ $this, 'plugin_info' ], 10, 3 );
    }

    /**
     * Checks for a new version of the plugin from GitHub.
     *
     * @param object $transient The update transient.
     * @return object The modified transient.
     */
    public function check_for_update( $transient ) {
        if ( empty( $transient->checked ) || ! isset( $transient->checked[ $this->plugin_slug ] ) ) {
            return $transient;
        }

        $current_version = $transient->checked[ $this->plugin_slug ];
        $release = $this->get_latest_release();

        if ( ! $release ) {
            return $transient;
        }

        if ( version_compare( $release->tag_name, $current_version, '>' ) ) {
            $transient->response[ $this->plugin_slug ] = (object) [
                'slug'        => 'the-paste',
                'plugin'      => $this->plugin_slug,
                'new_version' => $release->tag_name,
                'url'         => $release->html_url,
                'package'     => $release->zipball_url,
            ];
        }

        return $transient;
    }

    /**
     * Provides plugin information for the "View details" popup.
     *
     * @param false|object|array $result The result object or array.
     * @param string             $action The type of information being requested from the Plugin Installation API.
     * @param object             $args   Plugin API arguments.
     * @return false|object
     */
    public function plugin_info( $result, $action, $args ) {
        if ( 'plugin_information' !== $action || empty( $args->slug ) || 'the-paste' !== $args->slug ) {
            return $result;
        }

        $release = $this->get_latest_release();

        if ( ! $release ) {
            return $result;
        }

        $result = (object) [
            'name'              => 'The Paste (Fork)',
            'slug'              => 'the-paste',
            'version'           => $release->tag_name,
            'author'            => '<a href="https://github.com/sadewadee">sadewadee</a>',
            'requires'          => '5.0',
            'tested'            => '6.5',
            'requires_php'      => '7.4',
            'last_updated'      => $release->published_at,
            'homepage'          => 'https://github.com/sadewadee/the-paste',
            'download_link'     => $release->zipball_url,
            'sections'          => [
                'description' => 'A modern fork of the original "The Paste" WordPress plugin, focused on performance, security, and powerful new features for the modern WordPress editor.',
                'changelog'   => $release->body,
            ],
        ];

        return $result;
    }

    /**
     * Gets the latest release information from GitHub, with caching.
     *
     * @return object|false The release object or false on failure.
     */
    private function get_latest_release() {
        $cached = get_transient( self::TRANSIENT_KEY );
        if ( $cached ) {
            return $cached;
        }

        $response = wp_remote_get( self::GITHUB_API_URL );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return false;
        }

        $release = json_decode( wp_remote_retrieve_body( $response ) );

        if ( ! $release || empty( $release->tag_name ) ) {
            return false;
        }

        set_transient( self::TRANSIENT_KEY, $release, HOUR_IN_SECONDS );

        return $release;
    }
}
