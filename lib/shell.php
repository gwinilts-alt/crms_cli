<?php 
require_once("io/sh_io.php");

abstract class Shell extends BaseIO {
    protected static $instanceName = "cmd";
    private static $args;
    private static $subject;

    public static function init($argv) {
        static::$instanceName = array_shift($argv);

        $match = [];
        static::$args = [];
        static::$subject = [];

        while (sizeof($argv) > 0) {
            $harg = array_shift($argv);
        
            if (preg_match("/[-]+(.*)/", $harg, $match)) {
                if (sizeof($argv) > 0) {
                    if (substr($argv[0], 0, 1) === "-") {
                        static::$args[$match[1]] = true;
                    } else {
                        static::$args[$match[1]] = array_shift($argv);
                    }
                }
            } else {
                static::$subject[] = $harg;
            }
        }
    }

    public static function argDump() {
        self::_writeln("Instance: " . self::$instanceName);
        self::_writeln("Args: ");
        foreach (static::$args as $k => $v) {
            self::_writeln("\t", $k, ": ", $v);
        }
        self::_writeln("Sbjs: ");
        foreach (static::$subject as $k => $v) {
            self::_writeln("\t", $k, ": ", $v);
        }
    }

    public static abstract function main(): int;
    public static abstract function help(): void;

    protected static function writeln(...$objs) {
        self::_writeln(...$objs);
    }

    protected static function write($msg) {
        self::_write($msg);
    }

}

Shell::init($argv);

