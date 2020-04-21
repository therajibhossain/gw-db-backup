<?php

use GWBackupConfig as conf;

class GWBackupSetting
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
        add_filter('plugin_action_links_' . GWBACKUP_FILE, array($this, 'settings_link'));
    }

    /*link to plugin list*/
    public function settings_link($links)
    {
        // Build and escape the URL.
        $url = esc_url(add_query_arg(
            'page',
            GWBACKUP_NAME,
            get_admin_url() . 'admin.php'
        ));
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
            'GW Backup',
            'GW Backup Settings',
            'manage_options',
            'gw-backup',
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

            $tab_links .= '<button class="gwb_tablinks ' . $active . '" id="' . $key . '">' . $tab['title'] . '</button>';
            $tab_contents .= $this->set_form($key, $tab, $display, $sl);
            $sl++;
        }
        ?>
        <div class="wrap">
            <h1>GW Backup Settings</h1>
            <?php echo $notice_div ?>
            <div class="tab">
                <?php echo $tab_links ?>
            </div>
            <?php echo $tab_contents ?>
        </div>
        <?php
    }

    private function db_backup_list()
    {
        ?>

        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
        <p class="submit"><a
                    href="http://localhost/wpme/wp-admin/tools.php?page=wp-database-backup&amp;action=createdbbackup&amp;_wpnonce=51abe35a96"
                    id="create_backup" class="btn btn-primary"> <span class="glyphicon glyphicon-plus-sign"></span>
                Create DB Backup</a></p>


        <table class="table table-striped table-bordered table-hover display dataTable no-footer"
               id="example" role="grid" aria-describedby="example_info">
            <thead>
            <tr>
                <th>SL</th>
                <th>Date</th>
                <th>File</th>
                <th>Size</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>1</td>
                <td>date</td>
                <td>
                    <a href="http://localhost/wpme/wp-content/uploads/db-backup/WP_Me_2020_04_20_1587392389_72c7e24_wpdb.zip"
                       style="color: #21759B;"><span class="glyphicon glyphicon-download-alt"></span>
                        Download</a></td>

                <td>kdfj</td>

                <td><a title="Remove Database Backup"
                       onclick="return confirm('Are you sure you want to delete database backup?')"
                       href="http://localhost/wpme/wp-admin/tools.php?page=wp-database-backup&amp;action=removebackup&amp;_wpnonce=51abe35a96&amp;index=0"
                       class="btn btn-default"><span style="color:red"
                                                     class="glyphicon glyphicon-trash"></span> Remove
                    </a><a> </a><a title="Restore Database Backup"
                                   onclick="return confirm('Are you sure you want to restore database backup?')"
                                   href="http://localhost/wpme/wp-admin/tools.php?page=wp-database-backup&amp;action=restorebackup&amp;_wpnonce=51abe35a96&amp;index=0"
                                   class="btn btn-default"><span class="glyphicon glyphicon-refresh"
                                                                 style="color:blue"></span> Restore
                    </a><a></a></td>
            </tr>

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
                    $this->input_field(array('_token', 'hidden', wp_create_nonce('gwb_nonce')));
                    settings_fields('gwb_option_group');
                    do_settings_sections('gwb-setting-' . $key);
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
            'gwb_option_group', // Option group
            'gwb_option_setting' // Option name
        );

        /*adding setting menu options*/
        foreach (self::$_menu_tabs as $key => $tab) {
            $setting = 'gwb-setting-' . $key;
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
                <option value="" selected="selected" disabled="disabled">Choose an option;</option>
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
        parse_str($_POST['formData'], $form_data);

        /*validating CSRF*/
        $token = $form_data['_token'];
        if (!isset($token) || !wp_verify_nonce($token, 'gwb_nonce')) wp_die("<br><br>YOU ARE NOT ALLOWED! ");
        $option_name = $_POST['gwb_section'];

        if (update_option($option_name, isset($form_data[$option_name]) ? $form_data[$option_name] : '')) {
            conf::boot_settings($option_name);
            $return = ['response' => 1, 'message' => $option_name . '--- settings updated!'];
        }
        echo json_encode($return);
        wp_die();
    }
}