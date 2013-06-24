<?php

/**
 * Description of Core
 *
 * @author barbarka
 */
class Core {
    /** @var mixed[] Description */
    private static $config;
    
    /**
     * autoloads class from file
     * @param String $classname
     */
    public static function tryAutoload($classname) {
        if (!class_exists($classname, false)) {
            $filename = __DIR__."/". $classname . ".php";
            if (file_exists($filename))
                require_once($filename);
            
            $filename = __DIR__."/../libs/". $classname . ".php";
            if (file_exists($filename))
                require_once($filename);
        }
    }
    
    /**
     * initializes CDNi core
     * @param mixed[] $config
     */
    public static function init($config) {
        self::$config = $config;
        
        if (isset($config['libDir'])) set_include_path(get_include_path().PATH_SEPARATOR.$config['libDir']);
    }
}

spl_autoload_register(__NAMESPACE__.'\Core::tryAutoload');

?>
