<?php
/**
 * Plugin Name: ListerCleaner for WP-Lister
 * Description: Modularly cleans outgoing HTML templates for WP-Lister eBay and Amazon listings based on dashboard options, featuring link whitelisting and a live testing engine.
 * Version:     1.3.1
 * Author:      AI Collaborator
 * License:     GPL2
 * Text Domain: listercleaner
 */

if (!defined('ABSPATH')) {
    exit;
}

require_filename_path();

function require_filename_path() {
    require_once plugin_dir_path(__FILE__) . 'class-listercleaner-pipeline.php';
}

class Lister_Cleaner_Core_Plugin {
    private static $instance = null;
    private $options_key = 'listercleaner_settings_array';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_init', [$this, 'init_settings']);
        add_action('admin_enqueue_scripts', [$this, 'load_assets']);

        $pipeline = Lister_Cleaner_Pipeline::get_instance();
        $current_options = get_option($this->options_key, []);
        if (!empty($current_options)) {
            add_filter('wplister_ebay_processed_description', [$pipeline, 'execute_pipeline'], 10, 2);
            add_filter('wplister_amazon_processed_description', [$pipeline, 'execute_pipeline'], 10, 2);
        }
    }

    public function register_menu() {
        add_options_page(
            esc_html__('ListerCleaner Settings', 'listercleaner'),
            esc_html__('ListerCleaner', 'listercleaner'),
            'manage_options',
            'listercleaner',
            [$this, 'render_dashboard']
        );
    }

    public function init_settings() {
        register_setting('listercleaner_settings_group', $this->options_key, [$this, 'sanitize_input_array']);
    }

    public function load_assets($hook) {
        if ('settings_page_listercleaner' !== $hook) {
            return;
        }
        wp_enqueue_script('listercleaner-admin', plugin_dir_url(__FILE__) . 'admin-script.js', ['jquery', 'wp-util'], '1.3.1', true);
        wp_localize_script('listercleaner-admin', 'ListerCleanerLoc', [
            'processing_text' => __('Processing Content...', 'listercleaner'),
            'execute_text'    => __('Execute Clean Test', 'listercleaner')
        ]);
    }

    public function sanitize_input_array($input) {
        $output = [];
        $flags  = ['strip_tags', 'strip_js_events', 'force_https', 'target_blank', 'clean_whitespace'];
        foreach ($flags as $flag) {
            $output[$flag] = isset($input[$flag]) ? 1 : 0;
        }
        if (isset($input['whitelist_textarea'])) {
            $output['whitelist_textarea'] = sanitize_textarea_field($input['whitelist_textarea']);
        }
        return $output;
    }

    public function render_dashboard() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'listercleaner'));
        }
        $saved_settings = get_option($this->options_key, []);
        $whitelist_content = isset($saved_settings['whitelist_textarea']) ? $saved_settings['whitelist_textarea'] : '';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('listercleaner_settings_group'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Active Rules Pipeline', 'listercleaner'); ?></th>
                        <td>
                            <fieldset>
                                <label><input type="checkbox" name="<?php echo esc_attr($this->options_key); ?>[strip_tags]" value="1" <?php checked(1, isset($saved_settings['strip_tags'])); ?> /> <?php esc_html_e('Strip Core Block Violation Tags (<script>, <style>, <iframe>, <form>)', 'listercleaner'); ?></label><br/><br/>
                                <label><input type="checkbox" name="<?php echo esc_attr($this->options_key); ?>[strip_js_events]" value="1" <?php checked(1, isset($saved_settings['strip_js_events'])); ?> /> <?php esc_html_e('Strip Inline JavaScript Event Actions (onclick, onload, etc.)', 'listercleaner'); ?></label><br/><br/>
                                <label><input type="checkbox" name="<?php echo esc_attr($this->options_key); ?>[force_https]" value="1" <?php checked(1, isset($saved_settings['force_https'])); ?> /> <?php esc_html_e('Enforce HTTPS String Replacements (http:// to https://)', 'listercleaner'); ?></label><br/><br/>
                                <label><input type="checkbox" name="<?php echo esc_attr($this->options_key); ?>[target_blank]" value="1" <?php checked(1, isset($saved_settings['target_blank'])); ?> /> <?php esc_html_e('Enforce target="_blank" Hyperlink Attributes', 'listercleaner'); ?></label><br/><br/>
                                <label><input type="checkbox" name="<?php echo esc_attr($this->options_key); ?>[clean_whitespace]" value="1" <?php checked(1, isset($saved_settings['clean_whitespace'])); ?> /> <?php esc_html_e('Minify Superfluous Structural Whitespace Breaks', 'listercleaner'); ?></label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="listercleaner_whitelist_field"><?php esc_html_e('Link Whitelist Exceptions', 'listercleaner'); ?></label></th>
                        <td>
                            <textarea id="listercleaner_whitelist_field" name="<?php echo esc_attr($this->options_key); ?>[whitelist_textarea]" rows="4" cols="50" class="large-text code"><?php echo esc_textarea($whitelist_content); ?></textarea>
                        </td>
                    </tr>
                </table>
                <?php submit_button(esc_html__('Save Pipeline Rules', 'listercleaner')); ?>
            </form>

            <h2><?php esc_html_e('Modern Sandbox Manual Testing Engine', 'listercleaner'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="listercleaner_test_raw_input"><?php esc_html_e('Raw Test HTML Input Layout', 'listercleaner'); ?></label></th>
                    <td>
                        <textarea id="listercleaner_test_raw_input" rows="6" class="large-text code"></textarea>
                        <p><button type="button" id="listercleaner_load_sample_btn" class="button button-link"><?php esc_html_e('Insert Complex Manual Sample Template Text', 'listercleaner'); ?></button></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="listercleaner_test_clean_output"><?php esc_html_e('Cleaned Preview Output Markup', 'listercleaner'); ?></label></th>
                    <td><textarea id="listercleaner_test_clean_output" readonly rows="6" class="large-text code" style="background:#f0f0f0;"></textarea></td>
                </tr>
            </table>
            <input type="hidden" id="listercleaner_security_token" value="<?php echo esc_attr(wp_create_nonce('listercleaner_ajax_nonce')); ?>" />
            <p class="submit"><input type="button" id="listercleaner_ajax_execute_btn" class="button button-secondary" value="<?php echo esc_attr__('Execute Clean Test', 'listercleaner'); ?>"></p>
        </div>
        <?php
    }
}

Lister_Cleaner_Core_Plugin::get_instance();

add_action('wp_ajax_listercleaner_preview_sandbox', function() {
    check_ajax_referer('listercleaner_ajax_nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Unauthorized operation access profile.', 'listercleaner'), 403);
    }
    $raw_dirty_html = isset($_POST['html']) ? wp_unslash($_POST['html']) : '';
    $processed_clean_html = Lister_Cleaner_Pipeline::get_instance()->execute_pipeline($raw_dirty_html);
    wp_send_json_success(['cleaned_html' => $processed_clean_html]);
});

