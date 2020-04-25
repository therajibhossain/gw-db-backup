<?php
/**
 * Copyright (C) Getweb, Inc. Ltd. All Rights Reserved
 *
 * Plugin Name: GW Database Backup
 * Plugin URI: https://getwebinc.com/plugins/gw-db-backup
 * Description: GW DB Backup manages backup & restoring of your database efficiently
 * Version: 1.0.0
 * Author: Rajib Hossain, Team Getweb, Inc.
 * Author URI: https://getwebinc.com
 * Text Domain: gw-db-backup
 * Domain Path: /languages
 * License: GPL v2 or later
 * WP requires at least: 5.0.0
 *
 * GWDB is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * GW DB Backup is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GW DB Backup. If not, see {License URI}.
 */

if (!defined('ABSPATH')) die('Direct access not allowed');
define('GWDB_DIR', plugin_dir_path(__FILE__));
require_once GWDB_DIR . '/inc/autoload.php';

/*plugin environment variables*/
define('GWDB_VERSION', '1.0.0');
define('GWDB_NAME', 'gw-db-backup');
define('GWDB_FILE', plugin_basename(__FILE__));
define('GWDB_URL', plugins_url('gw-db-backup/'));
define('GWDB_STYLES', GWDB_URL . 'css/');
define('GWDB_SCRIPTS', GWDB_URL . 'js/');
define('GWDB_LOGS', GWDB_DIR . 'logs/');


if (PHP_VERSION < 5.6) {
    add_action('admin_notices', function () {
        Config::notice_div('error', 'GWDBBackup requires at least PHP 5.6. Please upgrade PHP. The Plugin has been deactivated.');
    });
    init()->plugin_deactivate();
    return;
}


function init()
{
    static $Plugin = null;
    if (null === $Plugin) {
        $Plugin = new GWDBBackup(GWDB_VERSION, GWDB_FILE);
    }
    return $Plugin;
}

if (is_admin()) {
    init();
}