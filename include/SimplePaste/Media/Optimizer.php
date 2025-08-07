<?php

namespace SimplePaste\Media;

use SimplePaste\Core\Singleton;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Handles image optimization and watermarking after upload.
 */
class Optimizer extends Singleton {

    /**
     * Cached watermark resources to avoid reloading
     */
    private static $watermark_cache = [];

    /**
     * Maximum file size for watermark processing (50MB)
     */
    private const MAX_WATERMARK_FILE_SIZE = 52428800;

    /**
     * Constructor.
     */
    protected function __construct() {
        // Hook for traditional uploads (TinyMCE, media uploader)
        add_filter( 'wp_handle_upload', [ $this, 'process_uploaded_image' ], 10, 2 );

        // Hook for REST API uploads (Gutenberg)
        add_action( 'rest_insert_attachment', [ $this, 'process_rest_uploaded_image' ], 10, 3 );
    }

    /**
     * Main processing function for uploaded images.
     */
    public function process_uploaded_image( $upload, $context = null ) {
        // Detect editor type
        $editor_type = 'Unknown';
        if ( isset( $_POST['action'] ) && $_POST['action'] === 'upload-attachment' ) {
            $editor_type = 'Media Library/Upload';
        } elseif ( isset( $_POST['html-upload'] ) ) {
            $editor_type = 'Classic Editor';
        } elseif ( wp_doing_ajax() && isset( $_POST['async-upload'] ) ) {
            $editor_type = 'TinyMCE/Classic';
        } elseif ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            $editor_type = 'AJAX Upload';
        }

        error_log( 'SimplePaste Optimizer: Starting optimization for uploaded file (Editor: ' . $editor_type . ')' );
        // Validate input parameters
        if ( ! is_array( $upload ) || ! isset( $upload['type'] ) || ! isset( $upload['file'] ) ) {
            error_log( 'SimplePaste Optimizer: Invalid upload parameter structure' );
            return $upload;
        }

        if ( strpos( $upload['type'], 'image/' ) !== 0 ) {
            return $upload;
        }

        $supported_types = [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/avif',
            'image/gif',
            'image/bmp',
            'image/tiff'
        ];
        if ( ! in_array( $upload['type'], $supported_types, true ) ) {
            return $upload;
        }

        $file_path = $upload['file'];

        // Validate file path
        if ( ! file_exists( $file_path ) ) {
            error_log( 'SimplePaste Optimizer: File does not exist: ' . $file_path );
            return $upload;
        }

        // Step 1: Optimize the image.
        try {
            if ( extension_loaded( 'imagick' ) ) {
                $this->optimize_with_imagick( $file_path, $upload['type'] );
            } elseif ( extension_loaded( 'gd' ) ) {
                $this->optimize_with_gd( $file_path, $upload['type'] );
            }
        } catch ( \Exception $e ) {
            error_log( 'SimplePaste Optimizer: Image optimization failed: ' . $e->getMessage() );
        }

        // Step 2: Apply watermark if enabled.
        if ( get_option( 'simple_paste_watermark_enable' ) ) {
            try {
                $this->apply_watermark( $file_path, $upload['type'] );
            } catch ( \Exception $e ) {
                error_log( 'SimplePaste Optimizer: Watermark application failed: ' . $e->getMessage() );
            }
        }

        return $upload;
    }

    /**
     * Process uploaded images from REST API (Gutenberg).
     */
    public function process_rest_uploaded_image( $attachment, $request, $creating ) {
        // Detect editor type
        $editor_type = 'Gutenberg/Block Editor (REST API)';
        if ( $request->get_header( 'User-Agent' ) && strpos( $request->get_header( 'User-Agent' ), 'wp-json' ) !== false ) {
            $editor_type = 'Gutenberg/Block Editor (API)';
        }

        error_log( 'SimplePaste Optimizer: REST API optimization triggered for attachment ID: ' . $attachment->ID . ', creating: ' . ( $creating ? 'yes' : 'no' ) . ' (Editor: ' . $editor_type . ')' );

        // Only process if creating new attachment and it's an image
        if ( ! $creating || ! wp_attachment_is_image( $attachment->ID ) ) {
            return;
        }

        $file_path = get_attached_file( $attachment->ID );
        if ( ! $file_path || ! file_exists( $file_path ) ) {
            return;
        }

        $mime_type = get_post_mime_type( $attachment->ID );
        if ( ! $mime_type || strpos( $mime_type, 'image/' ) !== 0 ) {
            return;
        }

        $supported_types = [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/avif',
            'image/gif',
            'image/bmp',
            'image/tiff'
        ];
        if ( ! in_array( $mime_type, $supported_types, true ) ) {
            return;
        }

        // Step 1: Optimize the image.
        try {
            if ( extension_loaded( 'imagick' ) ) {
                $this->optimize_with_imagick( $file_path, $mime_type );
            } elseif ( extension_loaded( 'gd' ) ) {
                $this->optimize_with_gd( $file_path, $mime_type );
            }
        } catch ( \Exception $e ) {
            error_log( 'SimplePaste Optimizer: Image optimization failed for REST upload: ' . $e->getMessage() );
        }

        // Step 2: Apply watermark if enabled.
        if ( get_option( 'simple_paste_watermark_enable' ) ) {
            try {
                $this->apply_watermark( $file_path, $mime_type );
            } catch ( \Exception $e ) {
                error_log( 'SimplePaste Optimizer: Watermark application failed for REST upload: ' . $e->getMessage() );
            }
        }

        // Regenerate metadata after processing
        wp_update_attachment_metadata( $attachment->ID, wp_generate_attachment_metadata( $attachment->ID, $file_path ) );
    }

    /**
     * Optimizes an image using the Imagick library.
     */
    private function optimize_with_imagick( $file_path, $mime_type ) {
        try {
            $image = new \Imagick( $file_path );

            // Set compression quality for lossy formats
            switch ( $mime_type ) {
                case 'image/jpeg':
                    $image->setImageCompressionQuality( 82 );
                    break;
                case 'image/webp':
                    $image->setImageCompressionQuality( 85 );
                    break;
                case 'image/avif':
                    $image->setImageCompressionQuality( 80 );
                    break;
            }

            $image->stripImage();
            $image->writeImage( $file_path );
            $image->clear();
            $image->destroy();
        } catch ( \Exception $e ) {
            error_log( 'SimplePaste Optimizer: Imagick optimization failed for ' . $file_path . ': ' . $e->getMessage() );
            throw $e;
        }
    }

    /**
     * Optimizes an image using the GD library.
     */
    private function optimize_with_gd( $file_path, $mime_type ) {
        switch ( $mime_type ) {
            case 'image/jpeg':
                $image = \imagecreatefromjpeg( $file_path );
                if ( $image ) {
                    if ( ! \imagejpeg( $image, $file_path, 82 ) ) {
                        error_log( 'SimplePaste Optimizer: Failed to save optimized JPEG: ' . $file_path );
                    }
                    \imagedestroy( $image );
                } else {
                    error_log( 'SimplePaste Optimizer: Failed to create JPEG image from: ' . $file_path );
                }
                break;

            case 'image/png':
                $image = \imagecreatefrompng( $file_path );
                if ( $image ) {
                    if ( ! \imagepng( $image, $file_path, 9 ) ) {
                        error_log( 'SimplePaste Optimizer: Failed to save optimized PNG: ' . $file_path );
                    }
                    \imagedestroy( $image );
                } else {
                    error_log( 'SimplePaste Optimizer: Failed to create PNG image from: ' . $file_path );
                }
                break;

            case 'image/webp':
                if ( function_exists( '\imagecreatefromwebp' ) && function_exists( '\imagewebp' ) ) {
                    $image = \imagecreatefromwebp( $file_path );
                    if ( $image ) {
                        if ( ! \imagewebp( $image, $file_path, 85 ) ) {
                            error_log( 'SimplePaste Optimizer: Failed to save optimized WebP: ' . $file_path );
                        }
                        \imagedestroy( $image );
                    } else {
                        error_log( 'SimplePaste Optimizer: Failed to create WebP image from: ' . $file_path );
                    }
                } else {
                    error_log( 'SimplePaste Optimizer: WebP support not available in GD library' );
                }
                break;

            case 'image/gif':
                $image = \imagecreatefromgif( $file_path );
                if ( $image ) {
                    if ( ! \imagegif( $image, $file_path ) ) {
                        error_log( 'SimplePaste Optimizer: Failed to save optimized GIF: ' . $file_path );
                    }
                    \imagedestroy( $image );
                } else {
                    error_log( 'SimplePaste Optimizer: Failed to create GIF image from: ' . $file_path );
                }
                break;

            case 'image/bmp':
                if ( function_exists( '\imagecreatefrombmp' ) && function_exists( '\imagebmp' ) ) {
                    $image = \imagecreatefrombmp( $file_path );
                    if ( $image ) {
                        if ( ! \imagebmp( $image, $file_path ) ) {
                            error_log( 'SimplePaste Optimizer: Failed to save optimized BMP: ' . $file_path );
                        }
                        \imagedestroy( $image );
                    } else {
                        error_log( 'SimplePaste Optimizer: Failed to create BMP image from: ' . $file_path );
                    }
                } else {
                    error_log( 'SimplePaste Optimizer: BMP support not available in GD library' );
                }
                break;

            default:
                error_log( 'SimplePaste Optimizer: Unsupported image type for GD optimization: ' . $mime_type );
                break;
        }
    }

    /**
     * Applies a watermark to the image.
     */
    private function apply_watermark( $file_path, $mime_type ) {
        error_log( 'SimplePaste Optimizer: Starting watermark process for: ' . basename( $file_path ) );

        $start_time = microtime( true );
        $initial_memory = memory_get_usage();

        // Check file size before processing
        if ( filesize( $file_path ) > self::MAX_WATERMARK_FILE_SIZE ) {
            error_log( 'SimplePaste Optimizer: File too large for watermark processing: ' . $file_path . ' (' . size_format( filesize( $file_path ) ) . ')' );
            return;
        }

        $watermark_id = get_option( 'simple_paste_watermark_id' );
        if ( ! $watermark_id ) {
            error_log( 'SimplePaste Optimizer: No watermark ID configured' );
            return;
        }

        $watermark_path = get_attached_file( $watermark_id );
        if ( ! $watermark_path || ! file_exists( $watermark_path ) ) {
            error_log( 'SimplePaste Optimizer: Watermark file not found: ' . $watermark_path );
            return;
        }

        error_log( 'SimplePaste Optimizer: Watermark file found: ' . basename( $watermark_path ) . ', target size: ' . filesize( $file_path ) . ' bytes' );

        // Raise memory limit if needed
        wp_raise_memory_limit( 'image' );

        $watermark_size = get_option( 'simple_paste_watermark_size', 25 ); // Percentage
        $watermark_opacity = get_option( 'simple_paste_watermark_opacity', 70 ); // 0-100
        $watermark_position = get_option( 'simple_paste_watermark_position', 'bottom-right' );

        // Check if force GD library is enabled
        $force_gd = get_option( 'simple_paste_force_gd_library', false );

        try {
            if ( $force_gd && extension_loaded( 'gd' ) ) {
                error_log( 'SimplePaste Optimizer: Using GD for watermarking (forced) with opacity: ' . $watermark_opacity . '%' );
                $this->watermark_with_gd( $file_path, $watermark_path, $mime_type, $watermark_size, $watermark_opacity, $watermark_position );
            } elseif ( ! $force_gd && extension_loaded( 'imagick' ) ) {
                error_log( 'SimplePaste Optimizer: Using Imagick for watermarking with opacity: ' . $watermark_opacity . '%' );
                $this->watermark_with_imagick( $file_path, $watermark_path, $watermark_size, $watermark_opacity, $watermark_position );
            } elseif ( extension_loaded( 'gd' ) ) {
                error_log( 'SimplePaste Optimizer: Using GD for watermarking (fallback) with opacity: ' . $watermark_opacity . '%' );
                $this->watermark_with_gd( $file_path, $watermark_path, $mime_type, $watermark_size, $watermark_opacity, $watermark_position );
            } else {
                error_log( 'SimplePaste Optimizer: No image processing library available (neither Imagick nor GD)' );
            }
        } finally {
            // Log performance metrics
            $execution_time = microtime( true ) - $start_time;
            $memory_used = memory_get_usage() - $initial_memory;

            if ( $execution_time > 5.0 || $memory_used > 50 * 1024 * 1024 ) { // Log if > 5 seconds or > 50MB
                error_log( sprintf(
                    'SimplePaste Optimizer: Watermark processing took %.2fs and used %s memory for file: %s',
                    $execution_time,
                    size_format( $memory_used ),
                    basename( $file_path )
                ) );
            }
        }
    }

    private function watermark_with_imagick( $image_path, $watermark_path, $size, $opacity, $position ) {
        error_log( 'SimplePaste Optimizer: Starting Imagick watermarking - Size: ' . $size . '%, Opacity: ' . $opacity . '%, Position: ' . $position );

        try {
            $image = new \Imagick( $image_path );
            $watermark = new \Imagick( $watermark_path );

            error_log( 'SimplePaste Optimizer: Imagick objects created successfully' );

            // Apply size
            $original_watermark_width = $watermark->getImageWidth();
            $original_watermark_height = $watermark->getImageHeight();
            $new_watermark_width = (int) ($image->getImageWidth() * ( $size / 100 ));
            $new_watermark_height = (int) ( $original_watermark_height * ( $new_watermark_width / $original_watermark_width ) );
            $watermark->scaleImage( $new_watermark_width, $new_watermark_height );

            // IMPROVED SOLUTION: Enhanced alpha channel and opacity handling
            if ( $opacity < 100 ) {
                error_log( 'SimplePaste Optimizer: Applying opacity ' . $opacity . '% to watermark with enhanced alpha handling' );

                if (method_exists($watermark, 'evaluateImage')) {
                    // Ensure watermark has alpha channel
                    if (!$watermark->getImageAlphaChannel()) {
                        error_log( 'SimplePaste Optimizer: Setting alpha channel to OPAQUE for watermark' );
                        $watermark->setImageAlphaChannel(\Imagick::ALPHACHANNEL_OPAQUE);
                    }

                    // Apply opacity to all channels (more reliable)
                    error_log( 'SimplePaste Optimizer: Using evaluateImage with CHANNEL_ALL for enhanced opacity' );
                    $watermark->evaluateImage( \Imagick::EVALUATE_MULTIPLY, $opacity / 100, \Imagick::CHANNEL_ALL );

                    // Alternative: If above doesn't work, use composite blend
                    // $watermark->setImageOpacity( $opacity / 100 );
                } else {
                    // Fallback for older versions
                    error_log( 'SimplePaste Optimizer: Using setImageOpacity fallback for older Imagick versions' );
                    $watermark->setImageOpacity( $opacity / 100 );
                }
            } else {
                error_log( 'SimplePaste Optimizer: Using full opacity (100%), no opacity adjustment needed' );
            }

            // Calculate position
            list( $x, $y ) = $this->calculate_watermark_position(
                $image->getImageWidth(), $image->getImageHeight(),
                $watermark->getImageWidth(), $watermark->getImageHeight(),
                $position
            );

            $image->compositeImage( $watermark, \Imagick::COMPOSITE_OVER, $x, $y );

            $image->writeImage( $image_path );
            $image->clear();
            $image->destroy();
            $watermark->clear();
            $watermark->destroy();

            error_log( 'SimplePaste Optimizer: Imagick watermarking completed successfully with enhanced alpha handling' );
        } catch ( \Exception $e ) {
            error_log( 'SimplePaste Optimizer: Imagick watermark failed: ' . $e->getMessage() );
        }
    }

    private function watermark_with_gd( $image_path, $watermark_path, $mime_type, $size, $opacity, $position ) {
        error_log( 'SimplePaste Optimizer: Starting GD watermarking - MIME: ' . $mime_type . ', Size: ' . $size . '%, Opacity: ' . $opacity . '%, Position: ' . $position );

        // Use cached watermark if available
        $cache_key = $watermark_path . '_' . $size;

        $image = ( 'image/png' === $mime_type ) ? \imagecreatefrompng( $image_path ) : \imagecreatefromjpeg( $image_path );
        if ( ! $image ) {
            error_log( 'SimplePaste Optimizer: Failed to create image resource from: ' . $image_path );
            return;
        }

        error_log( 'SimplePaste Optimizer: Main image resource created successfully' );

        // Load or get cached watermark
        if ( ! isset( self::$watermark_cache[ $cache_key ] ) ) {
            $watermark_info = \getimagesize( $watermark_path );
            if ( ! $watermark_info ) {
                \imagedestroy( $image );
                return;
            }

            $watermark_mime = $watermark_info['mime'];
            $watermark = null;

            switch ( $watermark_mime ) {
                case 'image/png':
                    $watermark = \imagecreatefrompng( $watermark_path );
                    break;
                case 'image/jpeg':
                    $watermark = \imagecreatefromjpeg( $watermark_path );
                    break;
                case 'image/gif':
                    $watermark = \imagecreatefromgif( $watermark_path );
                    break;
                default:
                    \imagedestroy( $image );
                    return; // Unsupported watermark format
            }

            if ( ! $watermark ) {
                \imagedestroy( $image );
                return;
            }

            // Cache the watermark info
            self::$watermark_cache[ $cache_key ] = [
                'resource' => $watermark,
                'mime' => $watermark_mime,
                'width' => \imagesx( $watermark ),
                'height' => \imagesy( $watermark )
            ];
        }

        $cached_watermark = self::$watermark_cache[ $cache_key ];
        $watermark = $cached_watermark['resource'];
        $watermark_mime = $cached_watermark['mime'];

        // Ensure alpha channel is preserved for PNG images
        if ( 'image/png' === $mime_type ) {
            \imagealphablending( $image, true );
            \imagesavealpha( $image, true );
        }

        // Ensure alpha channel is preserved for watermark (PNG only)
        if ( 'image/png' === $watermark_mime ) {
            \imagealphablending( $watermark, true );
            \imagesavealpha( $watermark, true );
        }

        // Apply size using cached dimensions
        $original_watermark_width = $cached_watermark['width'];
        $original_watermark_height = $cached_watermark['height'];
        $new_watermark_width = (int) (\imagesx( $image ) * ( $size / 100 ));
        $new_watermark_height = (int) ( $original_watermark_height * ( $new_watermark_width / $original_watermark_width ) );

        // Check if we need to resize (avoid unnecessary resizing)
        if ( $new_watermark_width === $original_watermark_width && $new_watermark_height === $original_watermark_height ) {
            $resized_watermark = $watermark; // Use original if no resize needed
        } else {
            $resized_watermark = \imagecreatetruecolor( $new_watermark_width, $new_watermark_height );
            if ( ! $resized_watermark ) {
                \imagedestroy( $image );
                return;
            }

            // Configure transparency only for PNG watermarks
            if ( 'image/png' === $watermark_mime ) {
                \imagealphablending( $resized_watermark, false );
                \imagesavealpha( $resized_watermark, true );

                // Set transparent background for the resized watermark
                $transparent = \imagecolorallocatealpha( $resized_watermark, 0, 0, 0, 127 );
                \imagefill( $resized_watermark, 0, 0, $transparent );
            }

            \imagecopyresampled( $resized_watermark, $watermark, 0, 0, 0, 0, $new_watermark_width, $new_watermark_height, $original_watermark_width, $original_watermark_height );
        }

        // Calculate position
        list( $x, $y ) = $this->calculate_watermark_position(
            \imagesx( $image ), \imagesy( $image ),
            \imagesx( $resized_watermark ), \imagesy( $resized_watermark ),
            $position
        );

        // Apply watermark with opacity handling yang lebih robust
        if ( $opacity < 100 ) {
            error_log( 'SimplePaste Optimizer: Applying GD opacity ' . $opacity . '% using pixel-level processing' );
            $this->apply_watermark_opacity_gd( $resized_watermark, $opacity );
        } else {
            error_log( 'SimplePaste Optimizer: Using full opacity (100%), no GD opacity adjustment needed' );
        }

        // Copy watermark dengan alpha blending yang proper
        \imagealphablending( $image, true );
        \imagesavealpha( $image, true );

        // Gunakan imagecopymerge untuk opacity atau imagecopy untuk full opacity
        if ( $opacity < 100 && function_exists( '\imagecopymerge' ) ) {
                error_log( 'SimplePaste Optimizer: Using \\imagecopymerge for opacity blending' );
                // Untuk opacity, gunakan imagecopymerge dengan alpha blending
            \imagecopymerge( $image, $resized_watermark, $x, $y, 0, 0, \imagesx( $resized_watermark ), \imagesy( $resized_watermark ), $opacity );
        } else {
            error_log( 'SimplePaste Optimizer: Using imagecopy for full opacity or fallback' );
            // Untuk full opacity atau fallback, gunakan imagecopy
            \imagecopy( $image, $resized_watermark, $x, $y, 0, 0, \imagesx( $resized_watermark ), \imagesy( $resized_watermark ) );
        }

        // Save the image
        $save_success = false;
        if ( 'image/png' === $mime_type ) {
            $save_success = \imagepng( $image, $image_path );
        } else {
            $save_success = \imagejpeg( $image, $image_path );
        }

        if ( ! $save_success ) {
            error_log( 'SimplePaste Optimizer: Failed to save watermarked image: ' . $image_path );
        } else {
            error_log( 'SimplePaste Optimizer: GD watermarking completed successfully' );
        }

        // Cleanup resources (but don't destroy cached watermark)
        \imagedestroy( $image );
        if ( $resized_watermark !== $watermark ) {
            \imagedestroy( $resized_watermark );
        }
    }

    /**
     * Applies opacity to a GD image resource while preserving the alpha channel.
     * This is a robust replacement for imagecopymerge() for transparent images.
     *
     * @param resource $image   The GD image resource to modify. Passed by reference.
     * @param int      $opacity The opacity level from 0 (transparent) to 100 (opaque).
     */
    private function apply_watermark_opacity_gd( &$image, $opacity ) {
        if ( ! \imageistruecolor( $image ) ) {
            \imagepalettetotruecolor( $image );
        }

        $width = \imagesx( $image );
        $height = \imagesy( $image );

        \imagealphablending( $image, false );
        \imagesavealpha( $image, true );

        for ( $x = 0; $x < $width; $x++ ) {
            for ( $y = 0; $y < $height; $y++ ) {
                $color = \imagecolorat( $image, $x, $y );
                $alpha = ( $color >> 24 ) & 0x7F;

                // If pixel is already fully transparent, skip it
                if ( $alpha == 127 ) {
                    continue;
                }

                // Calculate new alpha based on existing alpha and desired opacity
                $new_alpha = (int) ( 127 - ( ( 127 - $alpha ) * ( $opacity / 100 ) ) );

                // Get original RGB
                $red = ( $color >> 16 ) & 0xFF;
                $green = ( $color >> 8 ) & 0xFF;
                $blue = $color & 0xFF;

                // Set the new color with the new alpha
                $new_color = \imagecolorallocatealpha( $image, $red, $green, $blue, $new_alpha );
                \imagesetpixel( $image, $x, $y, $new_color );
            }
        }
    }

    /**
     * Calculates the X and Y coordinates for the watermark based on position.
     *
     * @param int $image_width Width of the base image.
     * @param int $image_height Height of the base image.
     * @param int $watermark_width Width of the watermark image.
     * @param int $watermark_height Height of the watermark image.
     * @param string $position The desired position (e.g., 'top-left', 'bottom-right').
     * @return array An array containing [x, y] coordinates.
     */
    private function calculate_watermark_position( $image_width, $image_height, $watermark_width, $watermark_height, $position ) {
        $x = 0;
        $y = 0;
        $margin = 20; // Small margin from edges

        switch ( $position ) {
            case 'top-left':
                $x = $margin;
                $y = $margin;
                break;
            case 'top-center':
                $x = ( $image_width - $watermark_width ) / 2;
                $y = $margin;
                break;
            case 'top-right':
                $x = $image_width - $watermark_width - $margin;
                $y = $margin;
                break;
            case 'middle-left':
                $x = $margin;
                $y = ( $image_height - $watermark_height ) / 2;
                break;
            case 'middle-center':
                $x = ( $image_width - $watermark_width ) / 2;
                $y = ( $image_height - $watermark_height ) / 2;
                break;
            case 'middle-right':
                $x = $image_width - $watermark_width - $margin;
                $y = ( $image_height - $watermark_height ) / 2;
                break;
            case 'bottom-left':
                $x = $margin;
                $y = $image_height - $watermark_height - $margin;
                break;
            case 'bottom-center':
                $x = ( $image_width - $watermark_width ) / 2;
                $y = $image_height - $watermark_height - $margin;
                break;
            case 'bottom-right':
            default:
                $x = $image_width - $watermark_width - $margin;
                $y = $image_height - $watermark_height - $margin;
                break;
        }

        return [ (int) $x, (int) $y ];
    }

    /**
     * Clear watermark cache to free memory
     */
    public static function clear_watermark_cache() {
        foreach ( self::$watermark_cache as $cache_item ) {
            if ( is_resource( $cache_item['resource'] ) ) {
                \imagedestroy( $cache_item['resource'] );
            }
        }
        self::$watermark_cache = [];
    }

    /**
     * Get current memory usage info
     */
    private function get_memory_info() {
        return [
            'current' => memory_get_usage(),
            'peak' => memory_get_peak_usage(),
            'limit' => ini_get( 'memory_limit' )
        ];
    }
    }
