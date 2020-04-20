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
    }

    public function admin_scripts()
    {
        $file = GWBACKUP_NAME . '-admin';
        wp_enqueue_style($file, GWBACKUP_STYLES . "/$file.css");
        wp_enqueue_script($file, GWBACKUP_SCRIPTS . "/$file.js", array('jquery'));
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

}