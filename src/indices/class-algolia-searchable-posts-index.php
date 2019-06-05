<?php

final class Algolia_Searchable_Posts_Index extends Algolia_Posts_Index_Abstract
{
    /**
     * @var string
     */
    protected $id = 'searchable_post';

    /**
     * @var string
     */
    protected $contains_only = 'posts';

    /**
     * @var array
     */
    private $post_types;

    /**
     * @param array $post_types
     */
    public function __construct(array $post_types)
    {
        $this->post_types = $post_types;
    }

    /**
     * @param mixed $item
     *
     * @return bool
     */
    public function supports($item)
    {
        return $item instanceof WP_Post && in_array($item->post_type, $this->post_types, true);
    }

    /**
     * @return string The name displayed in the admin UI.
     */
    public function get_admin_name()
    {
        return __('All posts', 'algolia');
    }

    /**
     * @param WP_Post $post
     *
     * @return bool
     */
    protected function should_index_post(WP_Post $post)
    {
        $should_index = 'publish' === $post->post_status && empty($post->post_password);

        return (bool) apply_filters('algolia_should_index_searchable_post', $should_index, $post);
    }

    /**
     * @param WP_Post $post
     *
     * @return string
     */
    protected function get_post_label_attributes(WP_Post $post)
    {
        $post_type = get_post_type_object($post->post_type);
        if (null === $post_type) {
            throw new RuntimeException('Unable to fetch the post type information.');
        }
        return $post_type->labels->name;
    }

    /**
     * @return array
     */
    protected function get_settings()
    {
        $settings = [
            'attributesToIndex'     => [
                'unordered(post_title)',
                'unordered(taxonomies)',
                'unordered(content)',
            ],
            'customRanking'         => [
                'desc(is_sticky)',
                'desc(post_date)',
                'asc(record_index)',
            ],
            'attributeForDistinct'  => 'post_id',
            'distinct'              => true,
            'attributesForFaceting' => [
                'taxonomies',
                'taxonomies_hierarchical',
                'post_author.display_name',
                'post_type_label',
            ],
            'attributesToSnippet'   => [
                'post_title:30',
                'content:30',
            ],
            'snippetEllipsisText'   => '…',
        ];

        $settings = (array) apply_filters('algolia_searchable_posts_index_settings', $settings);

        return $settings;
    }

    /**
     * @return array
     */
    protected function get_synonyms()
    {
        $synonyms = (array) apply_filters('algolia_searchable_posts_index_synonyms', []);

        return $synonyms;
    }

    /**
     * @param WP_Post $post
     * @param array   $records
     */
    protected function update_post_records(WP_Post $post, array $records)
    {
        parent::update_post_records($post, $records);

        do_action('algolia_searchable_posts_index_post_updated', $post, $records);
        do_action('algolia_searchable_posts_index_post_' . $post->post_type . '_updated', $post, $records);
    }

    /**
     * @return string
     */
    public function get_id()
    {
        return "{$this->id}s";
    }

    /**
     * @return int
     */
    protected function get_re_index_items_count()
    {
        $query = new WP_Query([
            'post_type'              => $this->post_types,
            'post_status'            => 'any', // Let the `should_index` take care of the filtering.
            'suppress_filters'       => true,
            'cache_results'          => false,
            'lazy_load_term_meta'    => false,
            'update_post_term_cache' => false,
        ]);

        return (int) $query->found_posts;
    }

    /**
     * @param int $page
     * @param int $batch_size
     *
     * @return array
     */
    protected function get_items($page, $batch_size)
    {
        $query = new WP_Query([
            'post_type'              => $this->post_types,
            'posts_per_page'         => $batch_size,
            'post_status'            => 'any',
            'order'                  => 'ASC',
            'orderby'                => 'ID',
            'paged'                  => $page,
            'suppress_filters'       => true,
            'cache_results'          => false,
            'lazy_load_term_meta'    => false,
            'update_post_term_cache' => false,
        ]);

        return $query->posts;
    }
}
