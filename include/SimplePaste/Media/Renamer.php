<?php

namespace ThePaste\Media;

use ThePaste\Core\Singleton;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Handles smart file renaming.
 */
class Renamer extends Singleton {

    /**
     * Constructor.
     */
    protected function __construct() {
        add_filter( 'wp_handle_upload_prefilter', [ $this, 'rename_pasted_file' ], 10, 1 );
    }

    /**
     * Renames the file based on the user-defined pattern.
     *
     * @param array $file The file array.
     * @return array The modified file array.
     */
    public function rename_pasted_file( $file ) {
        $pattern = get_option( 'the_paste_file_renaming_pattern', '{post_title}-{filename}' );

        // If the pattern is empty or default, do nothing.
        if ( empty( $pattern ) ) {
            return $file;
        }

        // Get the post context if available.
        $post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
        $post = $post_id ? get_post( $post_id ) : null;

        $new_name = $this->generate_name_from_pattern( $pattern, $file['name'], $post );

        if ( $new_name ) {
            $file['name'] = $new_name;
        }

        return $file;
    }

    /**
     * Generates a new filename from a pattern.
     *
     * @param string $pattern The pattern.
     * @param string $original_filename The original filename.
     * @param \WP_Post|null $post The post object, if available.
     * @return string The new filename.
     */
    private function generate_name_from_pattern( $pattern, $original_filename, $post ) {
        $pathinfo = pathinfo( $original_filename );
        $filename = $pathinfo['filename'];
        $extension = isset( $pathinfo['extension'] ) ? '.' . $pathinfo['extension'] : '';

        $replacements = [
            '{post_title}' => $post ? sanitize_title( $post->post_title ) : 'untitled',
            '{filename}'   => sanitize_title( $filename ),
            '{date}'       => date( 'Y-m-d' ),
            '{timestamp}'  => time(),
            '{random}'     => wp_generate_password( 8, false ),
        ];

        $new_name = str_replace(
            array_keys( $replacements ),
            array_values( $replacements ),
            $pattern
        );

        // Final sanitization and adding the extension back.
        return sanitize_title( $new_name ) . $extension;
    }
}
