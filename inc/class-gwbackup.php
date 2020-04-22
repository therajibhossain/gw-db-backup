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
        $options = conf::option_name();
        /*adding/removing encoding from .htaccess by GWBackupCompression*/
        conf::boot_settings($options[0], $status);
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
                        default:
                            break;
                    }
                }

                wp_redirect(conf::setting_url());
            }
        }
    }

    private function create_backup()
    {
        ini_set("max_execution_time", "4000");
        ini_set("max_input_time", "4000");
        ini_set('memory_limit', '900M');
        set_time_limit(0);

        $db = conf::db_config();
        // Get connection object and set the charset
        $conn = mysqli_connect($db['DB_HOST'], $db['DB_USER'], $db['DB_PASSWORD'], $db['DB_NAME']);
        $conn->set_charset("utf8");

        if (!$conn) {
            error_log('DB could not connect');
            return false;
        }

        // Get All Table Names From the Database
        $tables = array();
        $sql = "SHOW TABLES";
        $result = mysqli_query($conn, $sql);

        while ($row = mysqli_fetch_row($result)) {
            $tables[] = $row[0];
        }

        if ($tables) {
            $sqlScript = "";
            foreach ($tables as $table) {

                // Prepare SQLscript for creating table structure
                $query = "SHOW CREATE TABLE $table";
                $result = mysqli_query($conn, $query);
                $row = mysqli_fetch_row($result);

                $sqlScript .= "\n\n" . $row[1] . ";\n\n";

                $query = "SELECT * FROM $table";
                $result = mysqli_query($conn, $query);

                $columnCount = mysqli_num_fields($result);

                // Prepare SQLscript for dumping data for each table
                for ($i = 0; $i < $columnCount; $i++) {
                    while ($row = mysqli_fetch_row($result)) {
                        $sqlScript .= "INSERT INTO $table VALUES(";
                        for ($j = 0; $j < $columnCount; $j++) {
                            $row[$j] = $row[$j];

                            if (isset($row[$j])) {
                                $sqlScript .= '"' . $row[$j] . '"';
                            } else {
                                $sqlScript .= '""';
                            }
                            if ($j < ($columnCount - 1)) {
                                $sqlScript .= ',';
                            }
                        }
                        $sqlScript .= ");\n";
                    }
                }

                $sqlScript .= "\n";
            }
            if (!empty($sqlScript)) {
                // Save the SQL script to a backup file
                $file = GWBACKUP_DIR . 'backup/' . $db['DB_NAME'] . "__" . date('Y-m-d h-ia') . ".sql";
                if (file_put_contents($file, $sqlScript)) {
                    $this->delete_backup('', 10);
                }
            }
        }
        return;
    }

    private function delete_backup($file_name = '', $count = null)
    {
        $backup_dir = GWBACKUP_DIR . 'backup/';
        if (isset($file_name) && file_exists($file = $backup_dir . $file_name)) {
            unlink($file);
        }

        if (!isset($file_name) && isset($count)) {
            $backups = scandir($backup_dir, '');
            if (count($backups) > $count) {
                foreach ($backups as $item) {
                    if (isset($input['file']) && file_exists($file = $backup_dir . $item)) {
                        unlink($file);
                    }
                }
            }
        }
        return;
    }

}