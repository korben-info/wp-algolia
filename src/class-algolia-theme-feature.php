<?php

class Algolia_Theme_Feature
{
    public function __construct()
    {
        add_filter('wp_head', [$this, 'load_config']);
    }

    public function load_config()
    {
        $plugin = Algolia_Helper::get_instance();

        // TODO: get_theme_support(Algolia_Helper::NAME);

        $settings = $plugin->get_settings();
        $config = [
            'debug'              => defined('WP_DEBUG') && WP_DEBUG,
            'application_id'     => $settings->get_application_id(),
            'search_api_key'     => $settings->get_search_api_key(),
            // TODO: 'powered_by_enabled' => $settings->is_powered_by_enabled(),
            'query'              => isset($_GET['s']) ? wp_unslash($_GET['s'])  : '',
            'indices'            => [],
        ];

        // Inject all the indices into the config to ease instantsearch.js integrations.
        $indices = $plugin->get_indices(['enabled' => true]);
        foreach ($indices as $index) {
            $config['indices'][$index->get_id()] = $index->to_array();
        }

        // Give developers a last chance to alter the configuration.
        $config = (array) apply_filters('algolia_config', $config);

        echo '<script type="text/javascript">var algolia = ' . wp_json_encode($config) . ';</script>';
    }
}
