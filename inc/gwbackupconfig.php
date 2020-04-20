<?php

trait GWBackupConfig
{
    private static $extensions = array(), $_menu_tabs, $option_name, $option_value = array();

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
                $prefix . 'general_setting', $prefix . 'db_backup'
            );
        }
        return self::$option_name;
    }

    public static function option_tabs()
    {
        if (!self::$_menu_tabs) {
            $tab_list = array(
                array(
                    'title' => 'Settings', 'subtitle' => 'general settings', 'fields' => array(
                    array('name' => 'scheduled_backup', 'title' => 'Scheduled Backup', 'type' => 'checkbox'),
                    array('name' => 'schedule_type', 'title' => 'Auto Backup Schedule', 'type' => 'select'),
                    array('name' => 'num_backup', 'title' => 'Number of backups will be stored', 'type' => 'select'),
                )
                ),
                array(
                    'title' => 'DB Backups', 'subtitle' => 'Backups List', 'fields' => array()
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
}