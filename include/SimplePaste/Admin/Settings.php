<?php

namespace SimplePaste\Admin;

use SimplePaste\Core\Core;
use SimplePaste\Core\Singleton;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Settings extends Singleton {

    const PAGE_SLUG = 'simple-paste-settings';
    const OPTION_GROUP = 'simple_paste_options';

    protected function __construct() {
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_settings_scripts' ] );
        add_action( 'admin_init', [ $this, 'migrate_writing_options' ] );
    }

    public function enqueue_settings_scripts( $hook ) {
        if ( 'settings_page_' . self::PAGE_SLUG !== $hook ) {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_style( 'simple-paste-settings', Core::instance()->get_plugin_url() . 'css/admin/simple-paste.css', [], Core::instance()->version() );
        wp_enqueue_script( 'simple-paste-settings', Core::instance()->get_plugin_url() . 'js/admin/settings.js', [ 'jquery' ], Core::instance()->version(), true );
    }

    public function add_settings_page() {
        add_options_page(
            __( 'SimplePaste Dashboard', 'simple-paste' ),
            __( 'SimplePaste', 'simple-paste' ),
            'manage_options',
            self::PAGE_SLUG,
            [ $this, 'render_settings_page' ]
        );
    }

    public function register_settings() {
        $boolean_args = [ 'type' => 'boolean', 'sanitize_callback' => [ $this, 'sanitize_boolean_callback' ] ];

        register_setting( self::OPTION_GROUP, 'simple_paste_quick_attributes', array_merge( $boolean_args, [ 'default' => true ] ) );
        register_setting( self::OPTION_GROUP, 'simple_paste_html_cleanup', array_merge( $boolean_args, [ 'default' => true ] ) );
        register_setting( self::OPTION_GROUP, 'simple_paste_smart_url', array_merge( $boolean_args, [ 'default' => true ] ) );
        register_setting( self::OPTION_GROUP, 'simple_paste_code_pasting', array_merge( $boolean_args, [ 'default' => true ] ) );
        register_setting( self::OPTION_GROUP, 'simple_paste_table_pasting', array_merge( $boolean_args, [ 'default' => true ] ) );
        register_setting( self::OPTION_GROUP, 'simple_paste_watermark_enable', array_merge( $boolean_args, [ 'default' => false ] ) );
        register_setting( self::OPTION_GROUP, 'simple_paste_force_gd_library', array_merge( $boolean_args, [ 'default' => false ] ) );

        // Classic Editor Settings
        register_setting( self::OPTION_GROUP, 'simple_paste_tinymce_enabled', array_merge( $boolean_args, [ 'default' => true ] ) );
        register_setting( self::OPTION_GROUP, 'simple_paste_tinymce', array_merge( $boolean_args, [ 'default' => true ] ) );
        
        // Image Quality and Default Filename
        register_setting( self::OPTION_GROUP, 'simple_paste_image_quality', [ 'type' => 'integer', 'default' => 90, 'sanitize_callback' => 'absint' ] );
        register_setting( self::OPTION_GROUP, 'simple_paste_default_filename', [ 'type' => 'string', 'default' => 'Pasted', 'sanitize_callback' => 'sanitize_text_field' ] );
        
        // User Profile Options
        register_setting( self::OPTION_GROUP, 'simple_paste_enable_profile', array_merge( $boolean_args, [ 'default' => true ] ) );

        register_setting( self::OPTION_GROUP, 'simple_paste_file_renaming_pattern', [ 'type' => 'string', 'default' => '{post_title}-{filename}', 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( self::OPTION_GROUP, 'simple_paste_watermark_id', [ 'type' => 'integer', 'default' => 0, 'sanitize_callback' => 'absint' ] );
        register_setting( self::OPTION_GROUP, 'simple_paste_watermark_size', [ 'type' => 'integer', 'default' => 25, 'sanitize_callback' => 'absint' ] ); // Percentage
        register_setting( self::OPTION_GROUP, 'simple_paste_watermark_opacity', [ 'type' => 'integer', 'default' => 70, 'sanitize_callback' => 'absint' ] ); // 0-100
        register_setting( self::OPTION_GROUP, 'simple_paste_watermark_position', [ 'type' => 'string', 'default' => 'bottom-right', 'sanitize_callback' => 'sanitize_text_field' ] );
    }

    public function sanitize_boolean_callback( $value ) {
        return rest_sanitize_boolean( $value );
    }

    public function migrate_writing_options() {
        // Check if migration has already been done
        if ( get_option( 'simple_paste_migration_done' ) ) {
            return;
        }

        // Get old WritingOptions settings
        $old_options = get_option( 'simple_paste', [] );
        
        if ( ! empty( $old_options ) ) {
            // Migrate TinyMCE settings
            if ( isset( $old_options['tinymce_enabled'] ) ) {
                update_option( 'simple_paste_tinymce_enabled', $old_options['tinymce_enabled'] );
            }
            if ( isset( $old_options['tinymce'] ) ) {
                update_option( 'simple_paste_tinymce', $old_options['tinymce'] );
            }
            
            // Migrate Image Quality
            if ( isset( $old_options['image_quality'] ) ) {
                update_option( 'simple_paste_image_quality', $old_options['image_quality'] );
            }
            
            // Migrate Default Filename
            if ( isset( $old_options['default_filename'] ) ) {
                update_option( 'simple_paste_default_filename', $old_options['default_filename'] );
            }
        }
        
        // Mark migration as done
        update_option( 'simple_paste_migration_done', true );
    }

    public function render_settings_page() {
        ?>
        <div class="wrap simple-paste-dashboard">
            <h1>SimplePaste Dashboard</h1>
            <div class="nav-tab-wrapper">
                <a href="#settings-tab" class="nav-tab nav-tab-active">Pengaturan</a>
                <a href="#requirements-tab" class="nav-tab">Persyaratan Minimal</a>
            </div>
            
            <div id="settings-tab" class="tab-content active">
                <form action="options.php" method="post">
                    <?php settings_fields( self::OPTION_GROUP ); ?>

                    <div class="simple-paste-card">
                        <h2>Pasting Features</h2>
                        <?php $this->render_toggle_field('simple_paste_smart_url', 'Smart URL Pasting', 'Automatically convert pasted URLs from supported services into embed blocks.'); ?>
                        <?php $this->render_toggle_field('simple_paste_table_pasting', 'Table Pasting', 'Convert pasted tables from spreadsheets or websites into proper Gutenberg Table blocks.'); ?>
                        <?php $this->render_toggle_field('simple_paste_code_pasting', 'Code Pasting', 'Automatically detect and convert pasted text that appears to be code into a Gutenberg Code block.'); ?>
                        <?php $this->render_toggle_field('simple_paste_html_cleanup', 'HTML Cleanup', 'Automatically clean messy HTML (e.g., from Word, Google Docs) when pasted.'); ?>
                    </div>

                    <div class="simple-paste-card">
                        <h2>Classic Editor</h2>
                        <?php $this->render_toggle_field('simple_paste_tinymce_enabled', 'Enable Simple Paste in TinyMCE', 'Enable Simple Paste functionality in the Classic Editor (TinyMCE).'); ?>
                        <?php $this->render_toggle_field('simple_paste_tinymce', 'Prefer pasting files', 'When enabled, prioritize pasting files over other content types in Classic Editor.'); ?>
                        <?php $this->render_range_field('simple_paste_image_quality', 'Image Quality (%)', 'Set the quality for uploaded images (0-100%).', 0, 100, 5); ?>
                        <?php $this->render_text_field('simple_paste_default_filename', 'Default filename', 'Default filename template for pasted images.'); ?>
                    </div>

                    <div class="simple-paste-card">
                        <h2>Image Features</h2>
                        <?php $this->render_toggle_field('simple_paste_quick_attributes', 'Quick Image Attributes', 'After pasting an image, the image block sidebar automatically opens, prompting for Alt Text and Title.'); ?>
                        <?php $this->render_file_renaming_field(); ?>
                    </div>

                    <div class="simple-paste-card">
                        <h2>User Options</h2>
                        <?php $this->render_toggle_field('simple_paste_enable_profile', 'User profile options', 'Allow users to manage their personal pasting options in their profile settings.'); ?>
                    </div>

                    <div class="simple-paste-card">
                        <h2>Watermarking</h2>
                        <?php $this->render_toggle_field('simple_paste_watermark_enable', 'Enable Watermark', 'Automatically apply a watermark to all uploaded images.'); ?>
                        <?php $this->render_toggle_field('simple_paste_force_gd_library', 'Force GD Library', 'Force use GD library instead of Imagick for watermarking (useful for testing GD implementation).'); ?>
                        <?php $this->render_watermark_uploader(); ?>
                        <?php $this->render_range_field('simple_paste_watermark_size', 'Watermark Size (%)', 'Adjust the size of the watermark relative to the image.', 10, 100, 5); ?>
                        <?php $this->render_range_field('simple_paste_watermark_opacity', 'Watermark Opacity (%)', 'Set the transparency level of the watermark.', 0, 100, 5); ?>
                        <?php
                        $positions = [
                            'top-left'      => 'Top Left',
                            'top-center'    => 'Top Center',
                            'top-right'     => 'Top Right',
                            'middle-left'   => 'Middle Left',
                            'middle-center' => 'Middle Center',
                            'middle-right'  => 'Middle Right',
                            'bottom-left'   => 'Bottom Left',
                            'bottom-center' => 'Bottom Center',
                            'bottom-right'  => 'Bottom Right',
                        ];
                        $this->render_select_field('simple_paste_watermark_position', 'Watermark Position', 'Choose where the watermark will appear on the image.', $positions);
                        ?>
                        <div class="watermark-preview-container">
                            <img class="base-image" src="<?php echo esc_url( Core::instance()->get_plugin_url() . 'images/placeholder.png' ); ?>" alt="Base Image">
                            <img class="watermark-overlay" src="" alt="Watermark Preview">
                        </div>
                    </div>

                    <?php submit_button('Save Changes'); ?>
                </form>
            </div>
            
            <div id="requirements-tab" class="tab-content" style="display: none;">
                <div class="simple-paste-card">
                    <h2>Persyaratan Minimal</h2>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th>Komponen</th>
                                <th>Persyaratan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>WordPress</td>
                                <td>5.0 atau lebih tinggi</td>
                                <td><?php echo version_compare(get_bloginfo('version'), '5.0', '>=') ? '<span style="color: green;">✓ Terpenuhi</span>' : '<span style="color: red;">✗ Tidak Terpenuhi</span>'; ?></td>
                            </tr>
                            <tr>
                                <td>PHP</td>
                                <td>7.4 atau lebih tinggi</td>
                                <td><?php echo version_compare(PHP_VERSION, '7.4', '>=') ? '<span style="color: green;">✓ Terpenuhi</span>' : '<span style="color: red;">✗ Tidak Terpenuhi</span>'; ?></td>
                            </tr>
                            <tr>
                                <td>GD Library</td>
                                <td>Direkomendasikan</td>
                                <td><?php echo extension_loaded('gd') ? '<span style="color: green;">✓ Tersedia</span>' : '<span style="color: orange;">⚠ Tidak Tersedia</span>'; ?></td>
                            </tr>
                            <tr>
                                <td>Imagick Library</td>
                                <td>Direkomendasikan untuk watermark opacity</td>
                                <td><?php echo extension_loaded('imagick') ? '<span style="color: green;">✓ Tersedia</span>' : '<span style="color: orange;">⚠ Tidak Tersedia</span>'; ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="simple-paste-card">
                    <h2>Informasi Fitur Watermark</h2>
                    <p>Fitur watermark memerlukan salah satu dari library pemrosesan gambar berikut:</p>
                    <ul style="list-style-type: disc; margin-left: 20px;">
                        <li><strong>Imagick Library</strong>: Mendukung pengaturan opacity watermark dan menghasilkan kualitas gambar yang lebih baik.</li>
                        <li><strong>GD Library</strong>: Mendukung transparansi watermark PNG, tetapi tidak mendukung pengaturan opacity. Watermark akan ditampilkan dengan transparansi yang sudah ada dalam file PNG.</li>
                    </ul>
                    <p><strong>Catatan:</strong> Jika Anda ingin menggunakan fitur opacity watermark, pastikan server Anda mendukung Imagick Library.</p>
                </div>
                
                <div class="simple-paste-card">
                    <h2>Informasi Sistem</h2>
                    <table class="widefat">
                        <tbody>
                            <tr>
                                <td>Versi WordPress</td>
                                <td><?php echo get_bloginfo('version'); ?></td>
                            </tr>
                            <tr>
                                <td>Versi PHP</td>
                                <td><?php echo PHP_VERSION; ?></td>
                            </tr>
                            <tr>
                                <td>Versi SimplePaste</td>
                                <td><?php echo Core::instance()->version(); ?></td>
                            </tr>
                            <tr>
                                <td>GD Library</td>
                                <td><?php echo extension_loaded('gd') ? 'Tersedia' : 'Tidak Tersedia'; ?></td>
                            </tr>
                            <tr>
                                <td>Imagick Library</td>
                                <td><?php echo extension_loaded('imagick') ? 'Tersedia' : 'Tidak Tersedia'; ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

        private function render_toggle_field($option_name, $title, $description) {
        $option = get_option($option_name);
        echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">';
        echo '<div><h3 style="font-size: 16px; color: white; margin: 0;">' . esc_html($title) . '</h3><p class="description">' . esc_html($description) . '</p></div>';
        echo '<input type="hidden" name="' . esc_attr($option_name) . '" value="0">';
        echo '<label class="switch"><input type="checkbox" name="' . esc_attr($option_name) . '" value="1" ' . checked(1, $option, false) . '><span class="slider"></span></label>';
        echo '</div>';
    }

    private function render_text_field($option_name, $title, $description) {
        $option = get_option($option_name);
        echo '<div style="margin-bottom: 16px;">';
        echo '<label for="' . esc_attr($option_name) . '">' . esc_html($title) . '</label>';
        echo '<input type="text" id="' . esc_attr($option_name) . '" name="' . esc_attr($option_name) . '" value="' . esc_attr($option) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html($description) . '</p>';
        echo '</div>';
    }

    private function render_range_field($option_name, $title, $description, $min, $max, $step) {
        $option = get_option($option_name);
        echo '<div class="range-field-wrapper" style="margin-bottom: 16px;">';
        echo '<label for="' . esc_attr($option_name) . '">' . esc_html($title) . '</label>';
        echo '<input type="range" id="' . esc_attr($option_name) . '" name="' . esc_attr($option_name) . '" min="' . esc_attr($min) . '" max="' . esc_attr($max) . '" step="' . esc_attr($step) . '" value="' . esc_attr($option) . '">';
        echo '<input type="number" value="' . esc_attr($option) . '" style="width: 60px; margin-left: 8px;">';
        echo '<p class="description">' . esc_html($description) . '</p>';
        echo '</div>';
    }

    private function render_select_field($option_name, $title, $description, $options) {
        $current_option = get_option($option_name);
        echo '<div style="margin-bottom: 16px;">';
        echo '<label for="' . esc_attr($option_name) . '">' . esc_html($title) . '</label>';
        echo '<select id="' . esc_attr($option_name) . '" name="' . esc_attr($option_name) . '">';
        foreach ($options as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($current_option, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html($description) . '</p>';
        echo '</div>';
    }

    private function render_file_renaming_field() {
        $option = get_option('simple_paste_file_renaming_pattern');
        echo '<div style="margin-bottom: 24px;">';
        echo '<label for="simple_paste_file_renaming_pattern"><strong>File Renaming Pattern (SEO-Optimized)</strong></label>';
        echo '<input type="text" id="simple_paste_file_renaming_pattern" name="simple_paste_file_renaming_pattern" value="' . esc_attr($option) . '" class="regular-text" placeholder="{post_title}-{filename}" />';
        echo '<p class="description">Customize how uploaded files are named using SEO-friendly placeholders. All placeholders are automatically sanitized for web-safe filenames.</p>';
        
        echo '<div style="margin-top: 12px; padding: 16px; background: #1e1e1e; border-radius: 6px; border-left: 4px solid #00a0d2;">';
        echo '<h4 style="margin: 0 0 12px 0; color: #00a0d2;">Available SEO Placeholders:</h4>';
        echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 13px;">';
        
        $placeholders = [
            '{post_title}' => 'Post title (SEO sanitized)',
            '{filename}' => 'Alt Text → Title → Original filename',
            '{alt_text}' => 'Image Alt Text (from sidebar)',
            '{title}' => 'Image Title (from sidebar)',
            '{date}' => 'Current date (YYYY-MM-DD)',
            '{year}' => 'Current year (YYYY)',
            '{month}' => 'Current month (MM)',
            '{day}' => 'Current day (DD)',
            '{time}' => 'Current time (HH-MM-SS)',
            '{timestamp}' => 'Unix timestamp',
            '{random}' => 'Random 8-character string',
            '{random_short}' => 'Random 4-character string',
            '{site_name}' => 'Website name (SEO sanitized)',
            '{author}' => 'Post author name (SEO sanitized)',
            '{post_id}' => 'Post ID number',
            '{category}' => 'Primary post category (SEO sanitized)'
        ];
        
        foreach ($placeholders as $placeholder => $description) {
            echo '<div style="color: #ccc;"><code style="color: #00a0d2; background: #2d2d2d; padding: 2px 6px; border-radius: 3px;">' . esc_html($placeholder) . '</code> - ' . esc_html($description) . '</div>';
        }
        
        echo '</div>';
        echo '<div style="margin-top: 12px; padding: 12px; background: #2d2d2d; border-radius: 4px;">';
        echo '<h5 style="margin: 0 0 8px 0; color: #f0f0f0;">SEO Features:</h5>';
        echo '<ul style="margin: 0; padding-left: 16px; color: #ccc; font-size: 12px;">';
        echo '<li>Automatic lowercase conversion</li>';
        echo '<li>Special characters removal (except hyphens)</li>';
        echo '<li>Spaces converted to hyphens</li>';
        echo '<li>Multiple hyphens consolidated</li>';
        echo '<li>Filename length limited to 50 characters</li>';
        echo '<li>Empty filenames auto-generated</li>';
        echo '</ul>';
        echo '</div>';
        echo '<div style="margin-top: 8px; font-size: 12px; color: #888;">';
        echo '<strong>Examples:</strong><br>';
        echo '<code>{post_title}-{filename}</code> → <em>my-blog-post-beautiful-sunset.jpg</em><br>';
        echo '<code>{year}-{month}-{category}-{alt_text}</code> → <em>2025-01-travel-mountain-landscape.jpg</em><br>';
        echo '<code>{site_name}-{date}-{random_short}</code> → <em>my-website-2025-01-15-a8b2.jpg</em>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    private function render_watermark_uploader() {
        $watermark_id = get_option('simple_paste_watermark_id');
        $image_url = $watermark_id ? wp_get_attachment_url($watermark_id) : '';

        echo '<div style="margin-top: 16px;">';
        echo '<label>Watermark Image</label>';
        echo '<div class="image-uploader-wrapper" style="display: flex; align-items: center; gap: 16px;">';
        echo '<div class="image-preview" style="width: 100px; height: 100px; background: #2d2d2d; border-radius: 4px; display: flex; align-items: center; justify-content: center; overflow: hidden;">';
        if ($image_url) {
            echo '<img src="' . esc_url($image_url) . '" style="max-width: 100%; max-height: 100%;" class="current-watermark-image">';
        }
        echo '</div>';
        echo '<button type="button" class="button-secondary upload-watermark-button">Upload Image</button>';
        echo '<input type="hidden" name="simple_paste_watermark_id" value="' . esc_attr($watermark_id) . '">';
        echo '</div>';
        echo '</div>';
    }
}
