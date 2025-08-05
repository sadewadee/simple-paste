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

        register_setting( self::OPTION_GROUP, 'simple_paste_file_renaming_pattern', [ 'type' => 'string', 'default' => '{post_title}-{filename}', 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( self::OPTION_GROUP, 'simple_paste_watermark_id', [ 'type' => 'integer', 'default' => 0, 'sanitize_callback' => 'absint' ] );
        register_setting( self::OPTION_GROUP, 'simple_paste_watermark_size', [ 'type' => 'integer', 'default' => 25, 'sanitize_callback' => 'absint' ] ); // Percentage
        register_setting( self::OPTION_GROUP, 'simple_paste_watermark_opacity', [ 'type' => 'integer', 'default' => 70, 'sanitize_callback' => 'absint' ] ); // 0-100
        register_setting( self::OPTION_GROUP, 'simple_paste_watermark_position', [ 'type' => 'string', 'default' => 'bottom-right', 'sanitize_callback' => 'sanitize_text_field' ] );
    }

    public function sanitize_boolean_callback( $value ) {
        return rest_sanitize_boolean( $value );
    }

    public function render_settings_page() {
        ?>
        <div class="wrap simple-paste-dashboard">
            <h1>SimplePaste Dashboard</h1>
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
                    <h2>Image Features</h2>
                     <?php $this->render_toggle_field('simple_paste_quick_attributes', 'Quick Image Attributes', 'After pasting an image, the image block sidebar automatically opens, prompting for Alt Text and Title.'); ?>
                    <?php $this->render_text_field('simple_paste_file_renaming_pattern', 'File Renaming Pattern', 'Customize how uploaded files are named. Use placeholders like {post_title}, {filename}, {date}, etc.'); ?>
                </div>

                <div class="simple-paste-card">
                    <h2>Watermarking</h2>
                    <?php $this->render_toggle_field('simple_paste_watermark_enable', 'Enable Watermark', 'Automatically apply a watermark to all uploaded images.'); ?>
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
