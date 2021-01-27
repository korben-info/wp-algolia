<?php

class Algolia_Admin_Page
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

        add_action('admin_menu', function () {
            add_options_page(
                'Algolia',
                'Algolia',
                'manage_options',
                Algolia_Helper::NAME,
                [$this, 'show_options']
            );
        });
        add_action('admin_init', [$this, 'add_settings']);
    }

    public function show_options()
    {
        ?>
        <div class="wrap">
            <h1>
                <?= esc_html(get_admin_page_title()); ?>
                <button type="button" class="algolia-reindex-button button button-primary" data-index="searchable_posts">
                    <?php esc_html_e('Re-index', 'algolia'); ?>
                </button>
            </h1>
            <form method="post" action="options.php">
                <?php
                settings_fields(Algolia_Helper::NAME);
                do_settings_sections(Algolia_Helper::NAME);
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function add_settings()
    {
        add_settings_section(
            'default',
            null,
            [$this, 'print_section_settings'],
            Algolia_Helper::NAME
        );

        $options = [
            'application_id' => esc_html__('Application ID', 'algolia'),
            'search_api_key' => esc_html__('Search-only API key', 'algolia'),
            'api_key' => esc_html__('Admin API key', 'algolia'),
            'index_name_prefix' => esc_html__('Index name prefix', 'algolia'),
            'override_native_search' => esc_html__('Search results', 'algolia')
        ];

        array_walk($options, function ($description, $option) {
            add_settings_field(
                "algolia_{$option}",
                $description,
                [$this, "{$option}_callback"],
                Algolia_Helper::NAME
            );
            register_setting(Algolia_Helper::NAME, "algolia_{$option}", [$this, "sanitize_{$option}"]);
        });
    }

    public function application_id_callback()
    {

        $settings      = $this->plugin->get_settings();
        $setting       = $settings->get_application_id();
        $disabled_html = $settings->is_application_id_in_config() ? ' disabled' : '';
        ?>
        <input type="text" name="algolia_application_id" class="regular-text" value="<?= esc_attr($setting); ?>" <?= esc_html($disabled_html); ?>/>
        <p class="description" id="home-description"><?php esc_html_e('Your Algolia Application ID.', 'algolia'); ?></p>
        <?php
    }

    public function search_api_key_callback()
    {
        $settings      = $this->plugin->get_settings();
        $setting       = $settings->get_search_api_key();
        $disabled_html = $settings->is_search_api_key_in_config() ? ' disabled' : '';

        ?>
        <input type="text" name="algolia_search_api_key" class="regular-text" value="<?= esc_attr($setting); ?>" <?= esc_html($disabled_html); ?>/>
        <p class="description" id="home-description"><?php esc_html_e('Your Algolia Search-only API key (public).', 'algolia'); ?></p>
        <?php
    }

    public function api_key_callback()
    {
        $settings      = $this->plugin->get_settings();
        $setting       = $settings->get_api_key();
        $disabled_html = $settings->is_api_key_in_config() ? ' disabled' : '';
        ?>
        <input type="password" name="algolia_api_key" class="regular-text" value="<?= esc_attr($setting); ?>" <?= esc_html($disabled_html); ?>/>
        <p class="description" id="home-description"><?php esc_html_e('Your Algolia ADMIN API key (kept private).', 'algolia'); ?></p>
        <?php
    }

    public function index_name_prefix_callback()
    {
        $settings          = $this->plugin->get_settings();
        $index_name_prefix = $settings->get_index_name_prefix();
        $disabled_html     = $settings->is_index_name_prefix_in_config() ? ' disabled' : '';
        ?>
        <input type="text" name="algolia_index_name_prefix" value="<?= esc_attr($index_name_prefix); ?>" <?= esc_html($disabled_html); ?>/>
        <p class="description" id="home-description"><?php esc_html_e('This prefix will be prepended to your index names.', 'algolia'); ?></p>
        <?php
    }

    public function override_native_search_callback()
    {
        $value = $this->plugin->get_settings()->should_override_native_search();
        ?>
<div class="input-radio">
    <label>
        <input type="checkbox" name="algolia_override_native_search"<?php if (true === $value) :
            ?> checked<?php
                                                                    endif; ?>>
        <?php esc_html_e('Use Algolia in the backend', 'algolia'); ?>
    </label>
    <p class="description" id="home-description">
        With this option WordPress search will be powered by Algolia behind the scenes.<br>
        This will allow your search results to be typo tolerant.<br>
        <b>This option does not support filtering and displaying instant search results but has the advantage to play nicely with any theme.</b>
    </div>
</div>
        <?php
    }

    public function sanitize_application_id($value)
    {
        if ($this->plugin->get_settings()->is_application_id_in_config()) {
            $value = $this->plugin->get_settings()->get_application_id();
        }
        $value = sanitize_text_field($value);

        if (empty($value)) {
            add_settings_error(
                Algolia_Helper::NAME,
                'empty',
                esc_html__('Application ID should not be empty.', 'algolia')
            );
        }

        return $value;
    }

    public function sanitize_search_api_key($value)
    {
        if ($this->plugin->get_settings()->is_search_api_key_in_config()) {
            $value = $this->plugin->get_settings()->get_search_api_key();
        }
        $value = sanitize_text_field($value);

        if (empty($value)) {
            add_settings_error(
                Algolia_Helper::NAME,
                'empty',
                esc_html__('Search-only API key should not be empty.', 'algolia')
            );
        }

        return $value;
    }

    public function sanitize_api_key($value)
    {
        if ($this->plugin->get_settings()->is_api_key_in_config()) {
            $value = $this->plugin->get_settings()->get_api_key();
        }
        $value = sanitize_text_field($value);

        if (empty($value)) {
            add_settings_error(
                Algolia_Helper::NAME,
                'empty',
                esc_html__('API key should not be empty', 'algolia')
            );
        }
        $errors = get_settings_errors(Algolia_Helper::NAME);
        if (! empty($errors)) {
            return $value;
        }

        $settings = $this->plugin->get_settings();

        $valid_credentials = true;
        try {
            Algolia_API::assert_valid_credentials($settings->get_application_id(), $value);
        } catch (Exception $exception) {
            $valid_credentials = false;
            add_settings_error(
                Algolia_Helper::NAME,
                'login_exception',
                $exception->getMessage()
            );
        }

        if (! $valid_credentials) {
            add_settings_error(
                Algolia_Helper::NAME,
                'no_connection',
                esc_html__(
                    'We were unable to authenticate you against the Algolia servers with the provided information. Please ensure that you used an the Admin API key and a valid Application ID.',
                    'algolia'
                )
            );
            $settings->set_api_is_reachable(false);
        } else {
            if (! Algolia_API::is_valid_search_api_key($settings->get_application_id(), $settings->get_search_api_key())) {
                add_settings_error(
                    Algolia_Helper::NAME,
                    'wrong_search_API_key',
                    esc_html__(
                        'It looks like your search API key is wrong. Ensure that the key you entered has only the search capability and nothing else. Also ensure that the key has no limited time validity.',
                        'algolia'
                    )
                );
                $settings->set_api_is_reachable(false);
            } else {
                add_settings_error(
                    Algolia_Helper::NAME,
                    'connection_success',
                    esc_html__('We succesfully managed to connect to the Algolia servers with the provided information. Your search API key has also been checked and is OK.', 'algolia'),
                    'updated'
                );
                $settings->set_api_is_reachable(true);
            }
        }

        return $value;
    }

    /**
     * @param $index_name_prefix
     *
     * @return string
     */
    public function is_valid_index_name_prefix($index_name_prefix)
    {
        $to_validate = str_replace('_', '', $index_name_prefix);

        return ctype_alnum($to_validate);
    }

    /**
     * @param $value
     *
     * @return array
     */
    public function sanitize_index_name_prefix($value)
    {
        if ($this->plugin->get_settings()->is_index_name_prefix_in_config()) {
            $value = $this->plugin->get_settings()->get_index_name_prefix();
        }

        if ($this->is_valid_index_name_prefix($value)) {
            return $value;
        }

        add_settings_error(
            Algolia_Helper::NAME,
            'wrong_prefix',
            esc_html__('Indices prefix can only contain alphanumeric characters and underscores.', 'algolia')
        );

        $value = get_option('algolia_index_name_prefix');

        return $this->is_valid_index_name_prefix($value) ? $value : 'wp_';
    }

    /**
     * @param $value
     *
     * @return bool
     */
    public function sanitize_override_native_search($value)
    {
        return (true === $value || 'on' === $value);
    }

    /**
     * Prints the section text.
     */
    public function print_section_settings()
    {
        echo '<div class="card"><h2 class="title">Account</h2>';
        /* translators: the placeholder contains the URL to Algolia's dashboard. */
        echo '<p>' . wp_kses_post(sprintf(
            __('You can find your Algolia credentials in the <a href="%s">"API Keys" section of your Algolia dashboard</a>.', 'algolia'),
            'https://www.algolia.com/api-keys?utm_medium=extension&utm_source=WordPress&utm_campaign=admin'
        )) . '</p>';
        echo '<p>' . esc_html__('Once you provide your Algolia Application ID and API key, this plugin will be able to securely communicate with Algolia servers.', 'algolia') . ' ' . esc_html__('We ensure your information is correct by testing them against the Algolia servers upon save.', 'algolia') . '</p>';
        /* translators: the placeholder contains the URL to Algolia's website. */
        echo '<p>' . wp_kses_post(sprintf(
            __('No Algolia account yet? <a href="%s">Follow this link</a> to create one for free in a couple of minutes!.', 'algolia'),
            'https://www.algolia.com/users/sign_up?utm_medium=extension&utm_source=WordPress&utm_campaign=admin'
        )) . '</p>';
        echo '</div>';
    }
}
