<?php
/*Core class*/
if (!defined('ABSPATH')) {
    exit();
}

use GWBackupConfig as conf;

class GWBackup
{
    /*version string*/
    protected $version = null;
    /*filepath string*/
    protected $filepath = null;
    private $_backup_dir = GWBACKUP_DIR . 'backup/';

    /**
     * GWBackup constructor.
     * @param $version
     * @param $filepath
     */
    public function __construct($version, $filepath)
    {
        $this->version = $version;
        $this->filepath = $filepath;
        $this->init_hooks();
    }

    private function init_hooks()
    {
        register_activation_hook($this->filepath, array($this, 'plugin_activate')); //activate hook
        register_deactivation_hook($this->filepath, array($this, 'plugin_deactivate')); //deactivate hook
        register_uninstall_hook($this->filepath, 'GWBackup::plugin_uninstall'); //deactivate hook

        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        new GWBackupSetting();
        add_action('admin_init', array($this, 'execution'));
    }

    public function admin_scripts()
    {
        $file = GWBACKUP_NAME . '-admin';
        wp_enqueue_style($file, GWBACKUP_STYLES . "$file.css");
        wp_enqueue_script($file, GWBACKUP_SCRIPTS . "$file.js", array('jquery'));
    }


    public function plugin_activate()
    {
        $this->do_actions('active');
    }

    public function plugin_deactivate()
    {
        $this->do_actions('de-active');
    }

    public static function plugin_uninstall()
    {
        foreach (conf::option_name() as $item) {
            if (get_option($item) != false) {
                delete_option($item);
            }
        }
        $prefix = 'GWBackup';
        $other_options = array(
            $prefix . "_enqueued_scripts",
            $prefix . "_enqueued_styles",
            $prefix . "_src_combine_js",
            $prefix . "_src_combine_css",
        );
        foreach ($other_options as $item) {
            if (get_option($item) != false) {
                delete_option($item);
            }
        }
    }

    /*active/ de-active callback*/
    private function do_actions($status)
    {
//        $options = conf::option_name();
//        /*adding/removing encoding from .htaccess by GWBackupCompression*/
//        conf::boot_settings($options[0], $status);
    }

    public function execution()
    {
        if (is_admin() && current_user_can('manage_options')) {
            if (isset($_GET['action'])) {
                $input = conf::sanitize_data($_GET);
                if (isset($input['_wpnonce']) && wp_verify_nonce($input['_wpnonce'])) {
                    switch ($input['action']) {
                        case 'create-backup':
                            $this->create_backup();
                            break;
                        case 'delete-backup':
                            $this->delete_backup($input['file']);
                            break;
                        case 'restore-backup':
                            $this->restore_backup($input['file']);
                        default:
                            break;
                    }
                }

                wp_redirect(conf::setting_url());
            }
        }
    }

    public function general_admin_notice()
    {
        global $pagenow;
        if ($pagenow == 'options-general.php') {
            echo '<div class="notice notice-warning is-dismissible">
             <p>This notice appears on the settings page.</p>
         </div>';
        }
    }

    private function restore_backup($file_name)
    {
        $db = conf::db_config();
        $conn = @mysqli_connect($db['DB_HOST'], $db['DB_USER'], $db['DB_PASSWORD']);
        if ($conn) {
            $this->set_ini();
            $db_name = $db['DB_NAME'];
            /*select db */
            if (!mysqli_select_db($conn, $db_name)) {
                $sql = "CREATE DATABASE IF NOT EXISTS `{$db_name}`";
                mysqli_query($sql, $conn);
                mysqli_select_db($conn, $db_name);
            }

            /* removing tables */
            $tables = array();

            if ($result = mysqli_query($conn, "SHOW TABLES FROM `{$db_name}`")) {
                while ($row = mysqli_fetch_row($result)) {
                    $tables[] = $row[0];
                }
                if (count($tables) > 0) {
                    foreach ($tables as $table) {
                        mysqli_query($conn, "DROP TABLE `{$db_name}`.{$table}");
                    }

                    /*restoring db */
                    if ($file_name) {
                        if ($file_name && file_exists($file = $this->_backup_dir . $file_name)) {
                            $content = @file_get_contents($file, true);
                            $sql = explode(";\n", $content);

                            for ($i = 0; $i < count($sql); $i++) {
                                mysqli_query($conn, $sql[$i]);
                            }
                            /*removing backup file*/
                            @unlink($file);
                        }
                    }
                }
            }
        }
        return;
    }

    private function set_ini()
    {
        ini_set("max_execution_time", "4000");
        ini_set("max_input_time", "4000");
        ini_set('memory_limit', '900M');
        set_time_limit(0);
        return;
    }

    private function create_backup()
    {
        global $wpdb;
        $tables = $wpdb->get_col("SHOW TABLES");
        $sqlScript = '';
        if ($tables) {
            $this->set_ini();
            foreach ($tables as $table) {
                $result = $wpdb->get_results("SELECT * FROM {$table}", ARRAY_N);
                $row1 = $wpdb->get_row("SHOW CREATE TABLE {$table}", ARRAY_N);
                $sqlScript .= "\n\n" . $row1[1] . ";\n\n";
                //$columnCount = count($result[0]);
                for ($i = 0; $i < count($result); $i++) {
                    $row = $result[$i];
                    $sqlScript .= "INSERT INTO {$table} VALUES(";
                    for ($j = 0; $j < count($result[0]); $j++) {
                        $row[$j] = $wpdb->_real_escape($row[$j]);
                        $sqlScript .= (isset($row[$j])) ? '"' . $row[$j] . '"' : '""';
                        if ($j < (count($result[0]) - 1)) {
                            $sqlScript .= ',';
                        }
                    }
                    $sqlScript .= ");\n";
                }
                $sqlScript .= "\n";
            }
            if (!empty($sqlScript)) {
                // Save the SQL script to a backup file
                $db = conf::db_config();
                // Get connection object and set the charset
                $file = GWBACKUP_DIR . 'backup/' . $db['DB_NAME'] . "__" . date('Y-m-d h-i-s') . ".sql";
                file_put_contents($file, $sqlScript);
                $this->delete_backup('', 10);
            }
        }
        return;
    }

    private function delete_backup($file_name = null, $limit = null)
    {
        $backup_dir = GWBACKUP_DIR . 'backup/';
        if ($file_name && file_exists($file = $backup_dir . $file_name)) @unlink($file);

        if ($limit) {
            $backups = scandir($backup_dir);
            if (count($backups) > $limit) {
                $backups = array_diff($backups, array_slice($backups, -$limit));
                if ($backups) {
                    foreach ($backups as $item) if (file_exists($file = $backup_dir . $item)) @unlink($file);
                }
            }
        }
        return;
    }

}