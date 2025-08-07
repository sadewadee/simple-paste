<?php

namespace SimplePaste\Media;

use SimplePaste\Core\Singleton;

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
        // Hook for regular file uploads
        add_filter( 'wp_handle_upload_prefilter', [ $this, 'rename_pasted_file' ], 10, 1 );
        
        // Hook for plupload/media uploader (used by SimplePaste)
        add_action( 'add_attachment', [ $this, 'rename_attachment_file' ], 10, 1 );
        
        // Hook for REST API uploads (Gutenberg) - Priority 20 to run after Optimizer
        add_action( 'rest_insert_attachment', [ $this, 'rename_rest_attachment_file' ], 20, 3 );
        
        // Store filename pattern in session for plupload uploads
        add_action( 'wp_ajax_upload-attachment', [ $this, 'store_filename_pattern' ], 1 );
        
        // Hook for when attachment metadata is updated (Alt Text/Title changes)
        add_action( 'updated_post_meta', [ $this, 'on_attachment_meta_updated' ], 10, 4 );
        add_action( 'wp_update_attachment_metadata', [ $this, 'on_attachment_updated' ], 10, 2 );
    }

    /**
     * Renames the file based on the user-defined pattern.
     *
     * @param array $file The file array.
     * @return array The modified file array.
     */
    public function rename_pasted_file( $file ) {
        // Detect editor type
        $editor_type = 'Unknown';
        if ( isset( $_POST['action'] ) && $_POST['action'] === 'upload-attachment' ) {
            $editor_type = 'Gutenberg/Media Library';
        } elseif ( isset( $_POST['html-upload'] ) ) {
            $editor_type = 'Classic Editor';
        } elseif ( wp_doing_ajax() && isset( $_POST['async-upload'] ) ) {
            $editor_type = 'TinyMCE/Classic';
        }
        
        error_log( 'SimplePaste Renamer: Starting file rename process for: ' . $file['name'] . ' (Editor: ' . $editor_type . ')' );
        
        // Check if this is a pasted file (generic names indicate paste)
        $is_pasted_file = $this->is_pasted_file( $file['name'] );
        error_log( 'SimplePaste Renamer: Is pasted file: ' . ( $is_pasted_file ? 'yes' : 'no' ) );
        
        // Only rename pasted files, not manually uploaded files with custom names
        if ( ! $is_pasted_file ) {
            error_log( 'SimplePaste Renamer: Skipping rename - not a pasted file (has custom name)' );
            return $file;
        }
        
        $pattern = get_option( 'simple_paste_file_renaming_pattern', '{post_title}-{filename}' );
        error_log( 'SimplePaste Renamer: Using pattern: ' . $pattern );

        // If the pattern is empty, do nothing.
        if ( empty( $pattern ) ) {
            error_log( 'SimplePaste Renamer: Pattern is empty, skipping rename' );
            return $file;
        }

        // Get the post context if available.
        $post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
        $post = $post_id ? get_post( $post_id ) : null;
        
        error_log( 'SimplePaste Renamer: Post context - ID: ' . $post_id . ', Title: ' . ( $post ? $post->post_title : 'N/A' ) );

        $new_name = $this->generate_name_from_pattern( $pattern, $file['name'], $post );
        error_log( 'SimplePaste Renamer: Generated new name: ' . $new_name );

        if ( $new_name && $new_name !== $file['name'] ) {
            error_log( 'SimplePaste Renamer: Renaming from "' . $file['name'] . '" to "' . $new_name . '"' );
            $file['name'] = $new_name;
        } else {
            error_log( 'SimplePaste Renamer: No rename needed or new name is same as original' );
        }

        return $file;
    }

    /**
     * Store filename pattern for plupload uploads.
     */
    public function store_filename_pattern() {
        $pattern = get_option( 'simple_paste_file_renaming_pattern', '{post_title}-{filename}' );
        if ( ! empty( $pattern ) ) {
            set_transient( 'simple_paste_filename_pattern_' . get_current_user_id(), $pattern, 300 ); // 5 minutes
        }
    }

    /**
     * Rename attachment file after upload (for plupload/media uploader).
     *
     * @param int $attachment_id The attachment ID.
     */
    public function rename_attachment_file( $attachment_id ) {
        // Detect editor type for plupload uploads
        $editor_type = 'Media Library/Plupload';
        if ( wp_doing_ajax() ) {
            $editor_type = 'Media Library/AJAX Upload';
        }
        
        error_log( 'SimplePaste Renamer: Attachment file rename triggered for ID: ' . $attachment_id . ' (Editor: ' . $editor_type . ')' );
        
        $pattern = get_transient( 'simple_paste_filename_pattern_' . get_current_user_id() );
        
        if ( empty( $pattern ) ) {
            return;
        }
        
        $attachment = get_post( $attachment_id );
        if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
            return;
        }
        
        $attachment = get_post( $attachment_id );
         if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
             return;
         }
         
         // Get the file path
         $file_path = get_attached_file( $attachment_id );
         if ( ! $file_path || ! file_exists( $file_path ) ) {
             return;
         }
         
         $original_filename = basename( $file_path );
         
         // Check if this is a pasted file
         $is_pasted_file = $this->is_pasted_file( $original_filename );
         error_log( 'SimplePaste Renamer: Attachment is pasted file: ' . ( $is_pasted_file ? 'yes' : 'no' ) );
         
         // Only rename pasted files
          if ( ! $is_pasted_file ) {
              error_log( 'SimplePaste Renamer: Skipping attachment rename - not a pasted file' );
              return;
          }
        $post_id = $attachment->post_parent;
        $post = $post_id ? get_post( $post_id ) : null;
        
        // Check for user-provided alt text or title from image block sidebar
        $alt_text = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
        $attachment_title = $attachment->post_title;
        
        $new_filename = $this->generate_name_from_pattern( $pattern, $original_filename, $post, $alt_text, $attachment_title );
        
        if ( $new_filename && $new_filename !== $original_filename ) {
            $new_file_path = dirname( $file_path ) . '/' . $new_filename;
            
            if ( rename( $file_path, $new_file_path ) ) {
                // Update attachment metadata
                update_attached_file( $attachment_id, $new_file_path );
                
                // Update attachment title if it was based on filename
                if ( $attachment->post_title === pathinfo( $original_filename, PATHINFO_FILENAME ) ) {
                    wp_update_post( [
                        'ID' => $attachment_id,
                        'post_title' => pathinfo( $new_filename, PATHINFO_FILENAME )
                    ] );
                }
            }
        }
        
        // Clean up transient
        delete_transient( 'simple_paste_filename_pattern_' . get_current_user_id() );
    }

    /**
     * Rename attachment file for REST API uploads (Gutenberg).
     *
     * @param WP_Post $attachment The attachment post object.
     * @param WP_REST_Request $request The REST request object.
     * @param bool $creating Whether the attachment is being created.
     */
    public function rename_rest_attachment_file( $attachment, $request, $creating ) {
        // Detect if this is from Gutenberg editor
        $editor_type = 'Gutenberg/Block Editor';
        if ( $request->get_header( 'X-WP-Nonce' ) ) {
            $editor_type = 'Gutenberg/Block Editor (REST API)';
        }
        
        error_log( 'SimplePaste Renamer: REST API rename triggered for attachment ID: ' . $attachment->ID . ', creating: ' . ( $creating ? 'yes' : 'no' ) . ' (Editor: ' . $editor_type . ')' );
        
        // Only process if creating new attachment and it's an image
        if ( ! $creating || ! wp_attachment_is_image( $attachment->ID ) ) {
            error_log( 'SimplePaste Renamer: Skipping REST rename - not creating or not an image' );
            return;
        }

        // Get filename pattern from settings
        $pattern = get_option( 'simple_paste_file_renaming_pattern', '{post_title}-{filename}' );
        error_log( 'SimplePaste Renamer: REST API using pattern: ' . $pattern );
        
        if ( empty( $pattern ) ) {
            error_log( 'SimplePaste Renamer: REST API pattern is empty, skipping rename' );
            return;
        }

        // Get the file path
        $file_path = get_attached_file( $attachment->ID );
        if ( ! $file_path || ! file_exists( $file_path ) ) {
            error_log( 'SimplePaste Renamer: REST API file path not found: ' . $file_path );
            return;
        }

        $original_filename = basename( $file_path );
        
        // Check if this is a pasted file
        $is_pasted_file = $this->is_pasted_file( $original_filename );
        error_log( 'SimplePaste Renamer: REST API is pasted file: ' . ( $is_pasted_file ? 'yes' : 'no' ) );
        
        // Only rename pasted files
        if ( ! $is_pasted_file ) {
            error_log( 'SimplePaste Renamer: Skipping REST API rename - not a pasted file' );
            return;
        }
        
        $post_id = $attachment->post_parent;
        $post = $post_id ? get_post( $post_id ) : null;
        
        error_log( 'SimplePaste Renamer: REST API context - Original: ' . $original_filename . ', Post ID: ' . $post_id . ', Title: ' . ( $post ? $post->post_title : 'N/A' ) );

        // Get alt text and title from the attachment
        $alt_text = get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true );
        $attachment_title = $attachment->post_title;

        $new_filename = $this->generate_name_from_pattern( $pattern, $original_filename, $post, $alt_text, $attachment_title );
        error_log( 'SimplePaste Renamer: REST API generated new filename: ' . $new_filename );

        if ( $new_filename && $new_filename !== $original_filename ) {
            $new_file_path = dirname( $file_path ) . '/' . $new_filename;
            error_log( 'SimplePaste Renamer: REST API attempting rename from "' . $original_filename . '" to "' . $new_filename . '"' );

            if ( rename( $file_path, $new_file_path ) ) {
                error_log( 'SimplePaste Renamer: REST API rename successful' );
                // Update attachment metadata
                update_attached_file( $attachment->ID, $new_file_path );

                // Update attachment title if it was based on filename
                if ( $attachment->post_title === pathinfo( $original_filename, PATHINFO_FILENAME ) ) {
                    wp_update_post( [
                        'ID' => $attachment->ID,
                        'post_title' => pathinfo( $new_filename, PATHINFO_FILENAME )
                    ] );
                }

                // Regenerate metadata after renaming
                wp_update_attachment_metadata( $attachment->ID, wp_generate_attachment_metadata( $attachment->ID, $new_file_path ) );
            } else {
                error_log( 'SimplePaste Renamer: REST API rename failed from "' . $original_filename . '" to "' . $new_filename . '"' );
            }
        } else {
            error_log( 'SimplePaste Renamer: REST API no rename needed or new filename is same as original' );
        }
    }

    /**
     * Handle attachment metadata updates (Alt Text changes).
     *
     * @param int $meta_id The meta ID.
     * @param int $attachment_id The attachment ID.
     * @param string $meta_key The meta key.
     * @param mixed $meta_value The meta value.
     */
    public function on_attachment_meta_updated( $meta_id, $attachment_id, $meta_key, $meta_value ) {
        // Only process alt text updates for images
        if ( $meta_key !== '_wp_attachment_image_alt' || empty( $meta_value ) ) {
            return;
        }
        
        $attachment = get_post( $attachment_id );
        if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
            return;
        }
        
        // Check if this is an image
        if ( ! wp_attachment_is_image( $attachment_id ) ) {
            return;
        }
        
        $this->rename_based_on_metadata( $attachment_id, $meta_value, '' );
    }

    /**
     * Handle attachment updates (Title changes).
     *
     * @param array $data The attachment metadata.
     * @param int $attachment_id The attachment ID.
     */
    public function on_attachment_updated( $data, $attachment_id ) {
        $attachment = get_post( $attachment_id );
        if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
            return $data;
        }
        
        // Check if this is an image
        if ( ! wp_attachment_is_image( $attachment_id ) ) {
            return $data;
        }
        
        $alt_text = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
        $this->rename_based_on_metadata( $attachment_id, $alt_text, $attachment->post_title );
        
        return $data;
    }

    /**
     * Rename file based on updated metadata.
     *
     * @param int $attachment_id The attachment ID.
     * @param string $alt_text The alt text.
     * @param string $title The title.
     */
    private function rename_based_on_metadata( $attachment_id, $alt_text, $title ) {
        $pattern = get_option( 'simple_paste_file_renaming_pattern', '{post_title}-{filename}' );
        
        if ( empty( $pattern ) || strpos( $pattern, '{filename}' ) === false ) {
            return;
        }
        
        $attachment = get_post( $attachment_id );
        $file_path = get_attached_file( $attachment_id );
        
        if ( ! $file_path || ! file_exists( $file_path ) ) {
            return;
        }
        
        $original_filename = basename( $file_path );
        $post_id = $attachment->post_parent;
        $post = $post_id ? get_post( $post_id ) : null;
        
        $new_filename = $this->generate_name_from_pattern( $pattern, $original_filename, $post, $alt_text, $title );
        
        if ( $new_filename && $new_filename !== $original_filename ) {
            $new_file_path = dirname( $file_path ) . '/' . $new_filename;
            
            if ( rename( $file_path, $new_file_path ) ) {
                // Update attachment metadata
                update_attached_file( $attachment_id, $new_file_path );
                
                // Update attachment title if it was based on filename
                if ( $attachment->post_title === pathinfo( $original_filename, PATHINFO_FILENAME ) ) {
                    wp_update_post( [
                        'ID' => $attachment_id,
                        'post_title' => pathinfo( $new_filename, PATHINFO_FILENAME )
                    ] );
                }
            }
        }
    }

    /**
     * Generates a new filename from a pattern.
     *
     * @param string $pattern The pattern.
     * @param string $original_filename The original filename.
     * @param \WP_Post|null $post The post object, if available.
     * @param string $alt_text The alt text from image block sidebar.
     * @param string $attachment_title The title from image block sidebar.
     * @return string The new filename.
     */
    private function generate_name_from_pattern( $pattern, $original_filename, $post, $alt_text = '', $attachment_title = '' ) {
        error_log( 'SimplePaste Renamer: Generating name from pattern - Original: ' . $original_filename . ', Pattern: ' . $pattern );
        
        $pathinfo = pathinfo( $original_filename );
        $original_name = $pathinfo['filename'];
        $extension = isset( $pathinfo['extension'] ) ? '.' . $pathinfo['extension'] : '';

        // Use alt text or title as filename if provided, otherwise use original filename
        $filename_source = '';
        if ( ! empty( $alt_text ) ) {
            $filename_source = $alt_text;
            error_log( 'SimplePaste Renamer: Using alt text as filename source: ' . $alt_text );
        } elseif ( ! empty( $attachment_title ) && $attachment_title !== $original_name ) {
            $filename_source = $attachment_title;
            error_log( 'SimplePaste Renamer: Using attachment title as filename source: ' . $attachment_title );
        } else {
            $filename_source = $original_name;
            error_log( 'SimplePaste Renamer: Using original filename as source: ' . $original_name );
        }

        // SEO-friendly sanitization function
        $seo_sanitize = function( $text ) {
            // Convert to lowercase
            $text = strtolower( $text );
            // Remove special characters except hyphens and underscores
            $text = preg_replace( '/[^a-z0-9\-_\s]/', '', $text );
            // Replace spaces with hyphens
            $text = preg_replace( '/\s+/', '-', $text );
            // Remove multiple consecutive hyphens
            $text = preg_replace( '/-+/', '-', $text );
            // Trim hyphens from start and end
            $text = trim( $text, '-' );
            // Limit length to 50 characters for SEO
            return substr( $text, 0, 50 );
        };

        $current_date = current_time( 'mysql' );
        $date_obj = new \DateTime( $current_date );
        
        $replacements = [
            '{post_title}'    => $post ? $seo_sanitize( $post->post_title ) : 'untitled',
            '{filename}'      => $seo_sanitize( $filename_source ),
            '{alt_text}'      => $seo_sanitize( $alt_text ),
            '{title}'         => $seo_sanitize( $attachment_title ),
            '{date}'          => $date_obj->format( 'Y-m-d' ),
            '{year}'          => $date_obj->format( 'Y' ),
            '{month}'         => $date_obj->format( 'm' ),
            '{day}'           => $date_obj->format( 'd' ),
            '{time}'          => $date_obj->format( 'H-i-s' ),
            '{timestamp}'     => $date_obj->getTimestamp(),
            '{random}'        => wp_generate_password( 8, false ),
            '{random_short}'  => wp_generate_password( 4, false ),
            '{site_name}'     => $seo_sanitize( get_bloginfo( 'name' ) ),
            '{author}'        => $post && $post->post_author ? $seo_sanitize( get_the_author_meta( 'display_name', $post->post_author ) ) : 'unknown',
            '{post_id}'       => $post ? $post->ID : '0',
            '{category}'      => $post ? $seo_sanitize( $this->get_primary_category( $post->ID ) ) : 'uncategorized',
        ];

        $new_name = str_replace(
            array_keys( $replacements ),
            array_values( $replacements ),
            $pattern
        );

        // Final SEO sanitization and adding the extension back
        $new_name = $seo_sanitize( $new_name );
        
        // Ensure filename is not empty
        if ( empty( $new_name ) ) {
            $new_name = 'image-' . wp_generate_password( 6, false );
            error_log( 'SimplePaste Renamer: Generated fallback filename: ' . $new_name );
        }
        
        $final_filename = $new_name . $extension;
        error_log( 'SimplePaste Renamer: Final generated filename: ' . $final_filename );
        
        return $final_filename;
    }

    /**
     * Get primary category for a post.
     *
     * @param int $post_id The post ID.
     * @return string The primary category name.
     */
    private function get_primary_category( $post_id ) {
        $categories = get_the_category( $post_id );
        if ( ! empty( $categories ) ) {
            return $categories[0]->name;
        }
        return 'uncategorized';
    }
    
    /**
     * Check if a filename indicates it's from a paste operation.
     *
     * @param string $filename The filename to check.
     * @return bool True if it's likely a pasted file.
     */
    private function is_pasted_file( $filename ) {
        $filename_lower = strtolower( $filename );
        
        // Common generic names from paste operations
        $generic_patterns = [
            'image.png',
            'image.jpg',
            'image.jpeg',
            'image.gif',
            'image.webp',
            'pasted-image.png',
            'pasted-image.jpg',
            'pasted-image.jpeg',
            'clipboard.png',
            'clipboard.jpg',
            'screenshot.png',
            'screenshot.jpg',
            'untitled.png',
            'untitled.jpg',
        ];
        
        // Check exact matches
        if ( in_array( $filename_lower, $generic_patterns ) ) {
            return true;
        }
        
        // Check patterns with numbers (image-1.png, image-2.jpg, etc.)
        $numbered_patterns = [
            '/^image-\d+\.(png|jpg|jpeg|gif|webp)$/i',
            '/^pasted-image-\d+\.(png|jpg|jpeg|gif|webp)$/i',
            '/^clipboard-\d+\.(png|jpg|jpeg|gif|webp)$/i',
            '/^screenshot-\d+\.(png|jpg|jpeg|gif|webp)$/i',
            '/^untitled-\d+\.(png|jpg|jpeg|gif|webp)$/i',
        ];
        
        foreach ( $numbered_patterns as $pattern ) {
            if ( preg_match( $pattern, $filename_lower ) ) {
                return true;
            }
        }
        
        return false;
    }
}
