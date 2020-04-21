<?php

trait GWBackupConfig
{
    private static $extensions = array(), $_menu_tabs, $option_name, $option_value = array(), $_db_config = array();

    private function isExtensionLoaded($extension_name)
    {
        if (!isset(self::$extensions[$extension_name])) {
            self::$extensions[$extension_name] = extension_loaded($extension_name);
        }
        return self::$extensions[$extension_name];
    }

    public static function option_name()
    {
        if (!self::$option_name) {
            $prefix = 'gwbackup_';
            self::$option_name = array(
                $prefix . 'db_backup', $prefix . 'general_setting',
            );
        }
        return self::$option_name;
    }

    public static function option_tabs()
    {
        if (!self::$_menu_tabs) {
            $tab_list = array(
                array(
                    'title' => 'DB Backups', 'subtitle' => 'Backups List', 'fields' => array()
                ),
                array(
                    'title' => 'Settings', 'subtitle' => 'general settings', 'fields' => array(
                    array('name' => 'scheduled_backup', 'title' => 'Scheduled Backup', 'type' => 'checkbox'),
                    array('name' => 'schedule_type', 'title' => 'Auto Backup Schedule', 'type' => 'select', 'options' => array(
                        'hourly' => 'Hourly', 'twice_daily' => 'Twice Daily', 'daily' => 'Daily', 'weekly' => 'Weekly'
                    )),
                )
                ),
            );

            $list = array();
            foreach (self::option_name() as $key => $item) {
                $list[$item] = $tab_list[$key];
            }
            self::$_menu_tabs = $list;
        }
        return self::$_menu_tabs;

    }

    public static function boot_settings($option_name = '*', $status = 'active')
    {
        $options = self::option_name();
        if ($option_name == '*') {

        } elseif ($option_name === $options[0]) {
            new WPBoosterCompression($option_name, $status);
        } elseif ($option_name === $options[1]) {
            new WPBoosterCombined($option_name, $status);
        }
    }

    public static function get_comment($start = '/*', $end = '*/', $comment = 'gw-backup | last-modified: ', $date = 1)
    {
        $date = ($date != false) ? date('F d, Y h:i:sA') : '';
        return "$start " . $comment . " " . $date . " $end";
    }

    public static function option_value()
    {
        if (!self::$option_value) {
            foreach (self::option_name() as $item) {
                self::$option_value[$item] = get_option($item);
            }
        }
        return self::$option_value;
    }

    public static function log($message, $type = 'error')
    {
        $type = isset($type) ? $type . " :: " : $type;
        if (is_array($message)) {
            $message = json_encode($message);
        }
        $file = fopen(WPBOOSTER_LOGS . WPBOOSTER_NAME . '.txt', "a");
        echo fwrite($file, "[" . date('d-M-y h:i:s') . "] $type" . $message . "\n");
        fclose($file);
    }

    public static function db_config()
    {
        if (self::$_db_config) {
            return self::$_db_config;
        }

        $option_name = 'gwbackup_config';

        if ($db_config = get_option($option_name)) {
            self::$_db_config = $db_config;
            return self::$_db_config;
        }

        $db_config = array();
        $wp_config = @file_get_contents(get_home_path() . '/wp-config.php', true);
        if ($wp_config) {
            $keys = array(
                'DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_HOST'
            );
            foreach ($keys as $item) {
                preg_match("/'" . $item . "',\s*'(.*)?'/", $wp_config, $matches);
                $db_config[$item] = $matches[1];
            }
            if ($db_config) {
                update_option('gwbackup_config', $db_config);
                self::$_db_config = $db_config;
            }
        }
        return self::$_db_config;
    }
}