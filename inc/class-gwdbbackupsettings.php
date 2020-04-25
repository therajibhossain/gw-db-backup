<?php

use Config as conf;

class GWDBBackupSettings
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options = array();
    private static $_menu_tabs = array();

    /**
     * Start up
     */
    public function __construct()
    {
        $this->set_action_hooks();
    }

    /*setting action hooks*/
    private function set_action_hooks()
    {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'page_init'));
        add_action('wp_ajax_update_setting', array($this, 'update_setting'));
        add_filter('plugin_action_links_' . GWDB_FILE, array($this, 'settings_link'));
    }

    /*link to plugin list*/
    public function settings_link($links)
    {
        // Build and escape the URL.
        $url = conf::setting_url();
        // Create the link.
        $settings_link = "<a href='$url'>" . __('Settings') . '</a>';
        // Adds the link to the end of the array.
        array_push(
            $links,
            $settings_link
        );
        return $links;
    }

    /**
     * Add options page
     */
    public function admin_menu()
    {
        if (!self::$_menu_tabs) {
            self::$_menu_tabs = conf::option_tabs();
        }
        // This page will be under "Settings"
        add_options_page(
            'GW DB Backup',
            'GW DB Backup',
            'manage_options',
            GWDB_NAME,
            array($this, 'create_admin_page')
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        foreach (conf::option_name() as $item) {
            $this->options[$item] = get_option($item);
        }

        $notice_div = '';
        foreach (array('error', 'success') as $item) {
            ob_start();
            ?>
            <div class="<?php echo $item . ' gwb-notice-' . $item ?> updated notice is-dismissible"
                 style="display: none">
                <p><?php echo $item ?></p>
            </div>
            <?php
            $notice_div .= ob_get_clean();
        }

        $tab_links = '';
        $tab_contents = '';
        $sl = 0;
        foreach (self::$_menu_tabs as $key => $tab) {
            $display = 'none';
            $active = '';
            if ($sl === 0) {
                $active = 'active';
                $display = 'block';
            }

            if ($tab['status']) {
                $tab_links .= '<button class="gwb_tablinks ' . $active . '" id="' . $key . '">' . $tab['title'] . '</button>';
            }
            $tab_contents .= $this->set_form($key, $tab, $display, $sl);
            $sl++;
        }
        ?>
        <div class="wrap">
            <h1>GW Backup Settings</h1>
            <?php echo conf::notice_div(); ?>
            <div class="tab">
                <?php echo $tab_links ?>
            </div>
            <?php echo $tab_contents ?>
        </div>
        <?php
    }

    private function db_backup_list()
    {
        $url = wp_nonce_url(conf::setting_url());
        $backups = scandir($dir = GWDB_DIR . 'backup/');
        ?>

        <p class="submit">
            <a href="<?php echo $url ?>&action=create-backup"
               class="btn btn-success"> <span class="glyphicon glyphicon-plus-sign"></span>
                Create DB Backup</a>
        </p>


        <table class="table table-striped table-bordered table-hover display dataTable no-footer"
               id="example" role="grid" aria-describedby="example_info">
            <thead>
            <tr>
                <th>SL</th>
                <th>File</th>
                <th>Size</th>
                <th>Backup</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php
            if ($backups) {
                $sl = 0;
                foreach (array_reverse($backups) as $item) {
                    $item = ($item !== '.' && $item !== '..') ? $item : false;
                    if ($item) {
                        $sl++;
                        ?>
                        <tr>
                            <td><?php echo $sl ?></td>
                            <td><?php echo $item ?></td>
                            <td><?php echo conf::getSize($dir . $item) ?></td>
                            <td>
                                <a href="<?php echo GWDB_URL . 'backup/' . $item ?>" download
                                   style="color: #5cb85c;" class="button" title="Download Backup"><span
                                            class="glyphicon glyphicon-download-alt"></span> Download
                                </a></td>
                            <td><a title="Delete Backup"
                                   onclick="return confirm('Sure you want to delete?')"
                                   href="<?php echo $url ?>&action=delete-backup&file=<?php echo $item ?>"
                                   class="btn btn-default"><span style=""
                                                                 class="glyphicon glyphicon-trash text-danger"></span>
                                    Delete
                                </a><a> </a><a title="Restore Backup"
                                               onclick="return confirm('Sure you want to restore?')"
                                               href="<?php echo $url ?>&action=restore-backup&file=<?php echo $item ?>"
                                               class="btn btn-default"><span class="glyphicon glyphicon-refresh"
                                                                             style="color:#5cb85c"></span> Restore
                                </a><a></a></td>
                        </tr>
                        <?php
                    }
                }
            }
            ?>
            </tbody>
        </table>
        <?php
    }

    /*setting up form contents*/
    private function set_form($key, $tab, $display, $sl)
    {
        ob_start();
        ?>
        <div id="<?php echo $key ?>" class="tabcontent" style="display: <?php echo $display ?>">
            <h3><?php echo $tab['subtitle'] ?></h3>
            <hr>
            <?php
            if ($sl === 0):
                $this->db_backup_list();
            else:
                ?>
                <form method="post" action="options.php" class="ajax <?php echo $key ?>" id="<?php echo $key ?>">
                    <?php
                    $this->input_field(array('_token', 'hidden', wp_create_nonce('gwdb_nonce')));
                    settings_fields('gwdb_option_group');
                    do_settings_sections('gwdb-setting-' . $key);
                    submit_button();
                    ?>
                </form>
            <?php endif ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Register and add settings fields
     */
    public function page_init()
    {
        register_setting(
            'gwdb_option_group', // Option group
            'gwdb_option_setting' // Option name
        );

        /*adding setting menu options*/
        foreach (self::$_menu_tabs as $key => $tab) {
            $setting = 'gwdb-setting-' . $key;
            add_settings_section(
                'setting_section_id' . $setting, // ID
                '', // Title
                function ($tab) {
                }, // Callback
                $setting // Page
            );

            /*adding setting fields*/
            foreach ($tab['fields'] as $field) {
                $hr = isset($field['break']) ? "<hr>" : '';
                $name = $field['name'];

                add_settings_field(
                    $name, // ID
                    '<label for="' . $name . '">' . $field['title'] . '</label>' . $hr,
                    array($this, 'input_field'), // Callback
                    $setting, // Page
                    'setting_section_id' . $setting, // Section
                    array($name, $field['type'], '', $key, isset($field['options']) ? $field['options'] : '')
                );
            }
        }
    }

    /*input_field_callback*/
    public function input_field($arg = [])
    {
        $name = $arg[0];
        $type = $arg[1];
        $full_name = '';
        $val = '';
        if (isset($arg[3])) {
            $full_name = "$arg[3][$name]";
            $val = isset($this->options[$arg[3]][$name]) ? esc_attr($this->options[$arg[3]][$name]) : '';
        }

        if ($type === 'checkbox') {
            printf(
                '<input type="checkbox" id="' . $name . '" name="' . $full_name . '" %s />',
                $val ? 'checked' : ''
            );
        } elseif ($type === 'select') {
            $options = isset($arg[4]) ? $arg[4] : array();
            ?>
            <select id="<?php echo $name ?>" name="<?php echo $name ?>">
                <option value="" selected="selected" disabled="disabled">Choose an option</option>
                <?php
                if ($options) {
                    foreach ($options as $key => $item) {
                        printf('<option value="%1$s" %2$s>%3$s</option>', $key, selected($val, $key, false), $item);
                    }
                }
                ?></select>
            <?php
        } elseif (isset($arg[2])) {
            printf(
                '<input type="' . $type . '" name="' . $name . '" value="%s" />',
                $arg[2]
            );
        } else {
            printf(
                '<input type="text" id="' . $name . '" name="' . $full_name . '" value="%s" />',
                $val
            );
        }
    }



    /*updating all admin settings*/
    public function update_setting()
    {
        $return = ['response' => 0, 'message' => 'noting changed!'];
        $form_data = array();

        /*actually we are sanitizing this $_POST['formData'] just after few lines*/
        parse_str($_POST['formData'], $form_data);

        /*validating CSRF*/
        $token = sanitize_text_field($form_data['_token']);
        if (!isset($token) || !wp_verify_nonce($token, 'wpb_nonce')) wp_die("<br><br>YOU ARE NOT ALLOWED! ");
        $option_name = sanitize_text_field($_POST['wpb_section']);

        /*sanitizing $_POST['formData'] by option name*/
        $option_value = conf::sanitize_data($form_data[$option_name]);
        if (update_option($option_name, isset($option_value) ? $option_value : '')) {
            conf::boot_settings($option_name);
            $return = ['response' => 1, 'message' => $option_name . '--- settings updated!'];
        }
        echo json_encode($return);
        wp_die();
    }
}