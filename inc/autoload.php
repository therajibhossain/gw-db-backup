<?php
/**
 * Auto loading our classes
 */

spl_autoload_register('gwdb_autoload');
function gwdb_autoload($class_name)
{
    require_once 'config.php';
    if (false !== strpos($class_name, 'GWDBBackup')) {
        $dirSep = DIRECTORY_SEPARATOR;
        $parts = explode('\\', $class_name);
        $class = 'class-' . strtolower(array_pop($parts));
        $folders = strtolower(implode($dirSep, $parts));
        if (file_exists($classpath = dirname(__FILE__) . $dirSep . $folders . $dirSep . $class . '.php')) {
            require_once($classpath);
        } else {
            wp_die('The ' . $class_name . ' does not exist');
        }
    }
}