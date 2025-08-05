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
     * Constructor.
     */
    protected function __construct() {
        add_filter( 'wp_handle_upload', [ $this, 'process_uploaded_image' ], 10, 2 );
    }

    /**
     * Main processing function for uploaded images.
     */
    public function process_uploaded_image( $upload, $context ) {
        if ( strpos( $upload['type'], 'image/' ) !== 0 ) {
            return $upload;
        }

        $supported_types = [ 'image/jpeg', 'image/png' ];
        if ( ! in_array( $upload['type'], $supported_types, true ) ) {
            return $upload;
        }

        $file_path = $upload['file'];

        // Step 1: Optimize the image.
        if ( extension_loaded( 'imagick' ) ) {
            $this->optimize_with_imagick( $file_path, $upload['type'] );
        } elseif ( extension_loaded( 'gd' ) ) {
            $this->optimize_with_gd( $file_path, $upload['type'] );
        }

        // Step 2: Apply watermark if enabled.
        if ( get_option( 'simple_paste_watermark_enable' ) ) {
            $this->apply_watermark( $file_path, $upload['type'] );
        }

        return $upload;
    }

    /**
     * Optimizes an image using the Imagick library.
     */
    private function optimize_with_imagick( $file_path, $mime_type ) {
        try {
            $image = new \Imagick( $file_path );
            if ( 'image/jpeg' === $mime_type ) {
                $image->setImageCompressionQuality( 82 );
            }
            $image->stripImage();
            $image->writeImage( $file_path );
            $image->clear();
            $image->destroy();
        } catch ( \Exception $e ) {}
    }

    /**
     * Optimizes an image using the GD library.
     */
    private function optimize_with_gd( $file_path, $mime_type ) {
        if ( 'image/jpeg' === $mime_type ) {
            $image = imagecreatefromjpeg( $file_path );
            if ( $image ) {
                imagejpeg( $image, $file_path, 82 );
                imagedestroy( $image );
            }
        } elseif ( 'image/png' === $mime_type ) {
            $image = imagecreatefrompng( $file_path );
            if ( $image ) {
                imagepng( $image, $file_path, 9 );
                imagedestroy( $image );
            }
        }
    }

    /**
     * Applies a watermark to the image.
     */
    private function apply_watermark( $file_path, $mime_type ) {
        $watermark_id = get_option( 'simple_paste_watermark_id' );
        if ( ! $watermark_id ) {
            return;
        }

        $watermark_path = get_attached_file( $watermark_id );
        if ( ! $watermark_path || ! file_exists( $watermark_path ) ) {
            return;
        }

        $watermark_size = get_option( 'simple_paste_watermark_size', 25 ); // Percentage
        $watermark_opacity = get_option( 'simple_paste_watermark_opacity', 70 ); // 0-100
        $watermark_position = get_option( 'simple_paste_watermark_position', 'bottom-right' );

        if ( extension_loaded( 'imagick' ) ) {
            $this->watermark_with_imagick( $file_path, $watermark_path, $watermark_size, $watermark_opacity, $watermark_position );
        } elseif ( extension_loaded( 'gd' ) ) {
            $this->watermark_with_gd( $file_path, $watermark_path, $mime_type, $watermark_size, $watermark_opacity, $watermark_position );
        }
    }

    private function watermark_with_imagick( $image_path, $watermark_path, $size, $opacity, $position ) {
        try {
            $image = new \Imagick( $image_path );
            $watermark = new \Imagick( $watermark_path );

            // Apply size
            $original_watermark_width = $watermark->getImageWidth();
            $original_watermark_height = $watermark->getImageHeight();
            $new_watermark_width = $image->getImageWidth() * ( $size / 100 );
            $new_watermark_height = (int) ( $original_watermark_height * ( $new_watermark_width / $original_watermark_width ) );
            $watermark->scaleImage( $new_watermark_width, $new_watermark_height );

            // Apply opacity with fallback for older Imagick versions
            if (method_exists($watermark, 'evaluateImage')) {
                $watermark->evaluateImage( \Imagick::EVALUATE_MULTIPLY, $opacity / 100, \Imagick::CHANNEL_ALPHA );
            } else {
                /** @noinspection PhpDeprecationInspection */
                $watermark->setImageOpacity( $opacity / 100 );
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
        } catch ( \Exception $e ) {}
    }

    private function watermark_with_gd( $image_path, $watermark_path, $mime_type, $size, $opacity, $position ) {
        $image = ( 'image/png' === $mime_type ) ? imagecreatefrompng( $image_path ) : imagecreatefromjpeg( $image_path );
        $watermark = imagecreatefrompng( $watermark_path );

        if ( ! $image || ! $watermark ) {
            return;
        }

        // Apply size
        $original_watermark_width = imagesx( $watermark );
        $original_watermark_height = imagesy( $watermark );
        $new_watermark_width = imagesx( $image ) * ( $size / 100 );
        $new_watermark_height = (int) ( $original_watermark_height * ( $new_watermark_width / $original_watermark_width ) );

        $resized_watermark = imagecreatetruecolor( $new_watermark_width, $new_watermark_height );
        imagealphablending( $resized_watermark, false );
        imagesavealpha( $resized_watermark, true );
        imagecopyresampled( $resized_watermark, $watermark, 0, 0, 0, 0, $new_watermark_width, $new_watermark_height, $original_watermark_width, $original_watermark_height );

        // Apply opacity (GD requires a different approach for alpha blending)
        // This is a simplified approach, for more accurate alpha blending, each pixel would need to be processed.
        // For now, we'll use imagecopymerge with a transparency level.
        $opacity_level = 100 - $opacity; // imagecopymerge uses 0 (opaque) to 100 (transparent)

        // Calculate position
        list( $x, $y ) = $this->calculate_watermark_position(
            imagesx( $image ), imagesy( $image ),
            imagesx( $resized_watermark ), imagesy( $resized_watermark ),
            $position
        );

        imagecopymerge( $image, $resized_watermark, $x, $y, 0, 0, imagesx( $resized_watermark ), imagesy( $resized_watermark ), $opacity_level );

        if ( 'image/png' === $mime_type ) {
            imagepng( $image, $image_path );
        } else {
            imagejpeg( $image, $image_path );
        }

        imagedestroy( $image );
        imagedestroy( $watermark );
        imagedestroy( $resized_watermark );
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
    }
