<?php

class Algolia_Settings
{
    /**
     * Algolia_Settings constructor.
     */
    public function __construct()
    {
        add_option('algolia_application_id', '');
        add_option('algolia_search_api_key', '');
        add_option('algolia_api_key', '');
        add_option('algolia_synced_indices_ids', []);
        add_option('algolia_override_native_search', false);
        add_option('algolia_index_name_prefix', 'wp_');
        add_option('algolia_api_is_reachable', false);
    }

    /**
     * @return string
     */
    public function get_application_id()
    {
        if (! $this->is_application_id_in_config()) {
            return (string) get_option('algolia_application_id', '');
        }

        $this->assert_constant_is_non_empty_string(ALGOLIA_APPLICATION_ID, 'ALGOLIA_APPLICATION_ID');

        return ALGOLIA_APPLICATION_ID;
    }

    /**
     * @return string
     */
    public function get_search_api_key()
    {
        if (! $this->is_search_api_key_in_config()) {
            return (string) get_option('algolia_search_api_key', '');
        }

        $this->assert_constant_is_non_empty_string(ALGOLIA_SEARCH_API_KEY, 'ALGOLIA_SEARCH_API_KEY');

        return ALGOLIA_SEARCH_API_KEY;
    }

    /**
     * @return string
     */
    public function get_api_key()
    {
        if (! $this->is_api_key_in_config()) {
            return (string) get_option('algolia_api_key', '');
        }

        $this->assert_constant_is_non_empty_string(ALGOLIA_API_KEY, 'ALGOLIA_API_KEY');

        return ALGOLIA_API_KEY;
    }

    /**
     * @return array
     */
    public function get_post_types_blacklist()
    {
        $blacklist = (array) apply_filters('algolia_post_types_blacklist', ['nav_menu_item']);

        // Native WordPress.
        $blacklist[] = 'revision';

        // Native to Algolia Search plugin.
        $blacklist[] = 'algolia_task';
        $blacklist[] = 'algolia_log';

        // Native to WordPress VIP platform.
        $blacklist[] = 'kr_request_token';
        $blacklist[] = 'kr_access_token';
        $blacklist[] = 'deprecated_log';
        $blacklist[] = 'async-scan-result';
        $blacklist[] = 'scanresult';

        return array_unique($blacklist);
    }

    /**
     * @return array
     */
    public function get_synced_indices_ids()
    {
        $ids = $this->get_native_search_index_id();

        return (array) apply_filters('algolia_get_synced_indices_ids', $ids);
    }


    /**
     * @return array
     */
    public function get_taxonomies_blacklist()
    {
        return (array) apply_filters('algolia_taxonomies_blacklist', ['nav_menu', 'link_category', 'post_format']);
    }

    /**
     * @return bool
     */
    public function should_override_native_search()
    {
        return (bool) get_option('algolia_override_native_search', false);
    }

    /**
     * @return string
     */
    public function get_native_search_index_id()
    {
        return (string) apply_filters('algolia_native_search_index_id', 'searchable_posts');
    }

    /**
     * @return string
     */
    public function get_index_name_prefix()
    {
        if (! $this->is_index_name_prefix_in_config()) {
            return (string) get_option('algolia_index_name_prefix', 'wp_');
        }

        $this->assert_constant_is_non_empty_string(ALGOLIA_INDEX_NAME_PREFIX, 'ALGOLIA_INDEX_NAME_PREFIX');

        return ALGOLIA_INDEX_NAME_PREFIX;
    }

    /**
     * Makes sure that constants are non empty strings.
     * This makes sure that we fail early if the environment configuration is wrong.
     *
     * @param $value
     * @param $constant_name
     */
    protected function assert_constant_is_non_empty_string($value, $constant_name)
    {
        if (! is_string($value)) {
            throw new RuntimeException(sprintf('Constant %s in wp-config.php should be a string, %s given.', $constant_name, gettype($value)));
        }

        if (0 === mb_strlen($value)) {
            throw new RuntimeException(sprintf('Constant %s in wp-config.php cannot be empty.', $constant_name));
        }
    }

    /**
     * @return bool
     */
    public function is_application_id_in_config()
    {
        return defined('ALGOLIA_APPLICATION_ID');
    }

    /**
     * @return bool
     */
    public function is_search_api_key_in_config()
    {
        return defined('ALGOLIA_SEARCH_API_KEY');
    }

    /**
     * @return bool
     */
    public function is_api_key_in_config()
    {
        return defined('ALGOLIA_API_KEY');
    }

    /**
     * @return bool
     */
    public function is_index_name_prefix_in_config()
    {
        return defined('ALGOLIA_INDEX_NAME_PREFIX');
    }

    /**
     * @return bool
     */
    public function get_api_is_reachable()
    {
        return (bool) get_option('algolia_api_is_reachable', false);
    }

    /**
     * @param bool $flag
     */
    public function set_api_is_reachable($flag)
    {
        $value = true === $flag;
        update_option('algolia_api_is_reachable', $value);
    }
}
