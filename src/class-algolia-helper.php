<?php

class Algolia_Helper
{
    const NAME = 'algolia';

    /**
     * @var Algolia_Helper
     */
    private static $instance;

    /**
     * @var Algolia_API
     */
    protected $api;

    /**
     * @var Algolia_Settings
     */
    private $settings;

    /**
     * @var array
     */
    private $indices;

    /**
     * @var array
     */
    private $changes_watchers;

    /**
     * @var Algolia_Compatibility
     */
    private $compatibility;

    /**
     * @return Algolia_Helper
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new Algolia_Helper();
        }

        return self::$instance;
    }

    /**
     * Loads the plugin.
     */
    private function __construct()
    {
        $this->settings = new Algolia_Settings();

        $this->api = new Algolia_API($this->settings);

        $this->compatibility = new Algolia_Compatibility();

        add_action('init', [$this, 'load'], 20);
    }


    public function load()
    {
        if ($this->api->is_reachable()) {
            $this->load_indices();
            $this->override_wordpress_search();
        }

        // Load admin or public part of the plugin.
        if (is_admin()) {
            new Algolia_Admin($this);
        }

        // Load theme feature support
        if (current_theme_supports($this::NAME)) {
            new Algolia_Theme_Feature();
        }
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_name()
    {
        return self::NAME;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
        return ALGOLIA_VERSION;
    }

    /**
     * @return Algolia_API
     */
    public function get_api()
    {
        return $this->api;
    }

    /**
     * @return Algolia_Settings
     */
    public function get_settings()
    {
        return $this->settings;
    }

    /**
     * Replaces native WordPress search results by Algolia ranked results.
     */
    private function override_wordpress_search()
    {
        // Do not override native search if the feature is not enabled.
        if (! $this->settings->should_override_native_search()) {
            return;
        }

        $index_id = $this->settings->get_native_search_index_id();
        $index    = $this->get_index($index_id);

        if (null == $index) {
            return;
        }

        new Algolia_Search($index);
    }

    /**
     * @return array
     */
    public function load_indices()
    {
        $synced_indices_ids = $this->settings->get_synced_indices_ids();

        $client            = $this->get_api()->get_client();
        $index_name_prefix = $this->settings->get_index_name_prefix();

        // Add a searchable posts index.
        $searchable_post_types = get_post_types([
            'exclude_from_search' => false,
        ], 'names');
        $searchable_post_types = (array) apply_filters('algolia_searchable_post_types', $searchable_post_types);
        $this->indices[]       = new Algolia_Searchable_Posts_Index($searchable_post_types);

        // Add one posts index per post type.
        $post_types = get_post_types();

        $post_types_blacklist = $this->settings->get_post_types_blacklist();
        foreach ($post_types as $post_type) {
            // Skip blacklisted post types.
            if (in_array($post_type, $post_types_blacklist, true)) {
                continue;
            }

            $this->indices[] = new Algolia_Posts_Index($post_type);
        }

        // Add one terms index per taxonomy.
        $taxonomies           = get_taxonomies();
        $taxonomies_blacklist = $this->settings->get_taxonomies_blacklist();
        foreach ($taxonomies as $taxonomy) {
            // Skip blacklisted post types.
            if (in_array($taxonomy, $taxonomies_blacklist, true)) {
                continue;
            }

            $this->indices[] = new Algolia_Terms_Index($taxonomy);
        }

        // Add the users index.
        $this->indices[] = new Algolia_Users_Index();

        // Allow developers to filter the indices.
        $this->indices = (array) apply_filters('algolia_indices', $this->indices);

        foreach ($this->indices as $index) {
            $index->set_name_prefix($index_name_prefix);
            $index->set_client($client);

            if (in_array($index->get_id(), $synced_indices_ids)) {
                $index->set_enabled(true);

                if ($index->contains_only('posts')) {
                    $this->changes_watchers[] = new Algolia_Post_Changes_Watcher($index);
                } elseif ($index->contains_only('terms')) {
                    $this->changes_watchers[] = new Algolia_Term_Changes_Watcher($index);
                } elseif ($index->contains_only('users')) {
                    $this->changes_watchers[] = new Algolia_User_Changes_Watcher($index);
                }
            }
        }

        $this->changes_watchers = (array) apply_filters('algolia_changes_watchers', $this->changes_watchers);

        foreach ($this->changes_watchers as $watcher) {
            $watcher->watch();
        }
    }

    /**
     * @param array $args
     *
     * @return Algolia_Index[]
     */
    public function get_indices(array $args = [])
    {
        if (empty($args)) {
            return $this->indices;
        }

        $indices = $this->indices;

        if (empty($indices)) {
            return [];
        }

        if (isset($args['enabled']) && true === $args['enabled']) {
            $indices = array_filter(
                $indices,
                function (Algolia_Index $index) {
                    return $index->is_enabled();
                }
            );
        }

        if (isset($args['contains'])) {
            $contains = (string) $args['contains'];
            $indices  = array_filter(
                $indices,
                function (Algolia_Index $index) use ($contains) {
                    return $index->contains_only($contains);
                }
            );
        }

        return $indices;
    }

    /**
     * @param string $index_id
     *
     * @return Algolia_Index|null
     */
    public function get_index($index_id)
    {
        foreach ($this->indices as $index) {
            if ($index_id === $index->get_id()) {
                return $index;
            }
        }

        return null;
    }
}
