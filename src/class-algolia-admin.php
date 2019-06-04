<?php

class Algolia_Admin
{
    /**
     * @var Algolia_Helper
     */
    private $plugin;

    /**
     * @param Algolia_Helper $plugin
     */
    public function __construct(Algolia_Helper $plugin)
    {
        $this->plugin = $plugin;

        add_action('wp_ajax_algolia_re_index', [$this, 're_index']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);

        if ($plugin->get_api()->is_reachable()) {
            add_action('admin_notices', [$this, 'display_reindexing_notices']);
        }

        new Algolia_Admin_Page($plugin);

        add_action('admin_notices', [$this, 'display_unmet_requirements_notices']);
    }

    /**
     * Enqueue scripts.
     */
    public function enqueue_scripts()
    {
        wp_enqueue_script(
            'algolia-admin-reindex-button',
            plugin_dir_url(__DIR__) . 'assets/js/reindex-button.js',
            ['jquery'],
            ALGOLIA_VERSION
        );
    }

    /**
     * Displays an error notice for every unmet requirement.
     */
    public function display_unmet_requirements_notices()
    {
        if (! extension_loaded('mbstring')) {
            echo '<div class="error notice">
					  <p>' . esc_html__('Algolia Search requires the "mbstring" PHP extension to be enabled. Please contact your hosting provider.', 'algolia') . '</p>
				  </div>';
        } elseif (! function_exists('mb_ereg_replace')) {
            echo '<div class="error notice">
					  <p>' . esc_html__('Algolia needs "mbregex" NOT to be disabled. Please contact your hosting provider.', 'algolia') . '</p>
				  </div>';
        }

        $this->w3tc_notice();
    }

    /**
     * Display notice to help users adding 'algolia_' as an ignored query string to the db caching configuration.
     */
    public function w3tc_notice()
    {
        if (! function_exists('w3tc_pgcache_flush') || ! function_exists('w3_instance')) {
            return;
        }

        $config   = w3_instance('W3_Config');
        $enabled  = $config->get_integer('dbcache.enabled');
        $settings = array_map('trim', $config->get_array('dbcache.reject.sql'));

        if ($enabled && ! in_array('algolia_', $settings)) {
            /* translators: placeholder contains the URL to the caching plugin's config page. */
            $message = sprintf(__('In order for <strong>database caching</strong> to work with Algolia you must add <code>algolia_</code> to the "Ignored Query Stems" option in W3 Total Cache settings <a href="%s">here</a>.', 'algolia'), esc_url(admin_url('admin.php?page=w3tc_dbcache')));
            ?>
            <div class="error">
                <p><?php echo wp_kses_post($message); ?></p>
            </div>
            <?php
        }
    }

    public function display_reindexing_notices()
    {
        $indices = $this->plugin->get_indices([
            'enabled' => true,
        ]);
        foreach ($indices as $index) {
            if ($index->exists()) {
                continue;
            }

            ?>
      <div class="error">
        <p>For Algolia search to work properly, you need to index: <strong><?php echo esc_html($index->get_admin_name()); ?></strong></p>
        <p><button class="algolia-reindex-button button button-primary" data-index="<?php echo esc_attr($index->get_id()); ?>">Index now</button></p>
      </div>
            <?php
        }
    }

    public function re_index()
    {
        // check_admin_referer(Algolia_Helper::NAME);

        try {
            if (! isset($_POST['index_id'])) {
                throw new RuntimeException('Index ID should be provided.');
            }
            $index_id = (string) $_POST['index_id'];

            if (! isset($_POST['p'])) {
                throw new RuntimeException('Page should be provided.');
            }
            $page = (int) $_POST['p'];

            $index = $this->plugin->get_index($index_id);
            if (null === $index) {
                throw new RuntimeException(sprintf('Index named %s does not exist.', $index_id));
            }

            $total_pages = $index->get_re_index_max_num_pages();

            ob_start();
            if ($page <= $total_pages || 0 === $total_pages) {
                $index->re_index($page);
            }
            ob_end_clean();

            $response = [
                'totalPagesCount' => $total_pages,
                'finished'        => $page >= $total_pages,
            ];

            wp_send_json($response);
        } catch (\Exception $exception) {
            echo esc_html($exception->getMessage());
            throw $exception;
        }
    }
}
