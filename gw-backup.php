<?php
/**
 * Copyright (C) Getweb, Inc. Ltd. All Rights Reserved
 *
 * Plugin Name: GW Backup
 * Plugin URI: https://getwebinc.com/plugins/gw-backup
 * Description: gw-backup optimizes your site to make it faster & efficient
 * Version: 1.0.0
 * Author: Rajib Hossain, Team Getweb, Inc.
 * Author URI: https://getwebinc.com
 * Text Domain: gw-backup
 * Domain Path: /languages
 * License: GPL v2 or later
 * WP requires at least: 5.0.0
 *
 * GW is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * GWBackup is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GWBackup. If not, see {License URI}.
 */

if (!defined('ABSPATH')) die('Direct access not allowed');
define('GWBACKUP_DIR', plugin_dir_path(__FILE__));
require_once GWBACKUP_DIR . '/inc/autoload.php';

/*plugin environment variables*/
define('GWBACKUP_VERSION', '1.0.0');
define('GWBACKUP_NAME', 'gw-backup');
define('GWBACKUP_FILE', plugin_basename(__FILE__));
define('GWBACKUP_URL', plugins_url('gw-backup/'));
define('GWBACKUP_STYLES', GWBACKUP_URL . 'css/');
define('GWBACKUP_SCRIPTS', GWBACKUP_URL . 'js/');
define('GWBACKUP_LOGS', GWBACKUP_DIR . 'logs/');


if (PHP_VERSION < 5.6) {
    function incompatible_notice()
    {
        echo '<div class="error"><p>' . 'GWBackup requires at least PHP 5.6. Please upgrade PHP. The Plugin has been deactivated.' . '</p></div>';
    }

    add_action('admin_notices', 'incompatible_notice');
    GWBackup()->plugin_deactivate();
    return;
}


function GWBackup()
{
    static $GWBackup = null;
    if (null === $GWBackup) {
        $GWBackup = new GWBackup(GWBACKUP_VERSION, GWBACKUP_FILE);
    }
    return $GWBackup;
}

if (is_admin()) {
    GWBackup();
}