<?php
if (!defined('ABSPATH')) {
    exit;
}

class Lister_Cleaner_Pipeline {
    private static $instance = null;
    private $options_key = 'listercleaner_settings_array';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function execute_pipeline($description, $product_id = 0) {
        if (empty($description)) {
            return $description;
        }

        $options = get_option($this->options_key, []);

        if (!empty($options['strip_tags'])) {
            $description = preg_replace([
                '/<=?script[^>]*?>.*?<\/script>/is', 
                '/<=?style[^>]*?>.*?<\/style>/is', 
                '/<=?iframe[^>]*?>.*?<\/iframe>/is', 
                '/<=?form[^>]*?>.*?<\/form>/is'
            ], '', $description);
        }

        if (!empty($options['strip_js_events'])) {
            $description = preg_replace([
                '/\s+on[a-zA-Z]+\s*=\s*["\'][^"\']*["\']/i',
                '/\s+on[a-zA-Z]+\s*=\s*[^>\s]+/i'
            ], '', $description);
        }

        if (!empty($options['force_https'])) {
            $description = str_replace('http://', 'https://', $description);
        }

        if (!empty($options['target_blank'])) {
            $whitelist_str = isset($options['whitelist_textarea']) ? $options['whitelist_textarea'] : '';
            $whitelist_arr = array_filter(array_map('trim', explode("\n", strtolower($whitelist_str))));

            $description = preg_replace_callback('/<a\s+([^>]*?)>/i', function($matches) use ($whitelist_arr) {
                $attributes = $matches[1];
                if (preg_match('/href\s*=\s*["\']([^"\']+)["\']/i', $attributes, $url_match)) {
                    $target_url = strtolower($url_match[1]);
                    foreach ($whitelist_arr as $domain) {
                        if (!empty($domain) && strpos($target_url, $domain) !== false) {
                            return '<a ' . $attributes . '>';
                        }
                    }
                }
                if (strpos($attributes, 'target=') === false) {
                    return '<a ' . $attributes . ' target="_blank">';
                } else {
                    return '<a ' . preg_replace('/target\s*=\s*["\'][^"\']*["\']/i', 'target="_blank"', $attributes) . '>';
                }
            }, $description);
        }

        if (!empty($options['clean_whitespace'])) {
            $description = preg_replace('/(\r\n|\r|\n){3,}/', "\n\n", $description);
        }

        return trim($description);
    }
}

