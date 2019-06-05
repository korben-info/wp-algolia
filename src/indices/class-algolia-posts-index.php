<?php

final class Algolia_Posts_Index extends Algolia_Posts_Index_Abstract
{
    /**
     * @var string
     */
    protected $contains_only = 'posts';

    /**
     * @var string
     */
    private $post_type;

    /**
     * @param string $post_type
     */
    public function __construct($post_type)
    {
        $this->post_type = (string) $post_type;
    }

    /**
     * @param mixed $item
     *
     * @return bool
     */
    public function supports($item)
    {
        return $item instanceof WP_Post && $item->post_type === $this->post_type;
    }

    /**
     * @return string The name displayed in the admin UI.
     */
    public function get_admin_name()
    {
        $post_type = get_post_type_object($this->post_type);

        return null === $post_type ? $this->post_type : $post_type->labels->name;
    }

    /**
     * @param WP_Post $post
     *
     * @return bool
     */
    protected function should_index_post(WP_Post $post)
    {
        $post_status = $post->post_status;

        if ('inherit' === $post_status) {
            $parent_post = ( $post->post_parent ) ? get_post($post->post_parent) : null;
            if (null !== $parent_post) {
                $post_status = $parent_post->post_status;
            } else {
                $post_status = 'publish';
            }
        }

        $should_index = 'publish' === $post_status && empty($post->post_password);

        return (bool) apply_filters('algolia_should_index_post', $should_index, $post);
    }

    /**
     * @param WP_Post $post
     *
     * @return string
     */
    protected function get_post_label_attributes(WP_Post $post)
    {
        return $this->get_admin_name();
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
            ],
            'attributesToSnippet'   => [
                'post_title:30',
                'content:30',
            ],
            'snippetEllipsisText'   => '…',
        ];

        $settings = (array) apply_filters('algolia_posts_index_settings', $settings, $this->post_type);
        $settings = (array) apply_filters('algolia_posts_' . $this->post_type . '_index_settings', $settings);

        return $settings;
    }

    /**
     * @return array
     */
    protected function get_synonyms()
    {
        $synonyms = (array) apply_filters('algolia_posts_index_synonyms', [], $this->post_type);
        $synonyms = (array) apply_filters('algolia_posts_' . $this->post_type . '_index_synonyms', $synonyms);

        return $synonyms;
    }

    /**
     * @param WP_Post $post
     * @param array   $records
     */
    protected function update_post_records(WP_Post $post, array $records)
    {
        parent::update_post_records($post, $records);

        do_action('algolia_posts_index_post_updated', $post, $records);
        do_action('algolia_posts_index_post_' . $post->post_type . '_updated', $post, $records);
    }

    /**
     * @return string
     */
    public function get_id()
    {
        return "{$this->id}_{$this->post_type}";
    }

    /**
     * @return int
     */
    protected function get_re_index_items_count()
    {
        $query = new WP_Query([
            'post_type'        => $this->post_type,
            'post_status'      => 'any', // Let the `should_index` take care of the filtering.
            'suppress_filters' => true,
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
            'post_type'        => $this->post_type,
            'posts_per_page'   => $batch_size,
            'post_status'      => 'any',
            'order'            => 'ASC',
            'orderby'          => 'ID',
            'paged'            => $page,
            'suppress_filters' => true,
        ]);

        return $query->posts;
    }
}
