<?php

trait Config
{
    private static $extensions = array(), $_menu_tabs, $option_name, $option_value = array(), $_setting_url;

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
            $prefix = 'gwdb_';
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
                    'title' => 'DB Backups', 'subtitle' => 'Backups List', 'status' => 1, 'fields' => array()
                ),
                array(
                    'title' => 'Settings', 'subtitle' => 'general settings', 'status' => 0, 'fields' => array(
                    array('name' => 'scheduled_backup', 'title' => 'Scheduled Backup', 'type' => 'checkbox'),
                    array('name' => 'schedule_type', 'title' => 'Auto Backup Schedule', 'type' => 'select', 'options' => array(
                        'hourly' => 'Hourly', 'twice_daily' => 'Twice Daily', 'daily' => 'Daily', 'weekly' => 'Weekly'
                    )
                    ),
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
        } elseif ($option_name === $options[1]) {
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
        $file = fopen(GWDB_LOGS . 'log.txt', "a");
        echo fwrite($file, "[" . date('d-M-y h:i:s') . "] $type" . $message . "\n");
        fclose($file);
    }

    public static function setting_url()
    {
        if (!isset(self::$_setting_url)) {
            self::$_setting_url = esc_url(add_query_arg(
                'page',
                GWDB_NAME,
                get_admin_url() . 'admin.php'
            ));
        }
        return self::$_setting_url;
    }

    /*sanitizing input values using sanitize_text_field()*/
    public static function sanitize_data($input)
    {
        if (is_array($input) && $input) {
            $output = array();
            foreach ($input as $key => $value) {
                $output[$key] = sanitize_text_field($value);
            }
        } else {
            $output = sanitize_text_field($input);
        }
        return $output;
    }

    public static function getSize($file)
    {
        $bytes = filesize($file);
        $s = array('b', 'Kb', 'Mb', 'Gb');
        $e = floor(log($bytes) / log(1024));
        return sprintf('%.2f ' . $s[$e], ($bytes / pow(1024, floor($e))));
    }

    public static function notice_div()
    {
        if (isset($_GET)) {
            $input = self::sanitize_data($_GET);
            if (isset($input['type']) && isset($input['message'])) {
                return '<div class="notice ' . $input['type'] . ' is-dismissible" >
                    <p>' . $input['message'] . '</p>
                </div>';
            }
        }
    }
}