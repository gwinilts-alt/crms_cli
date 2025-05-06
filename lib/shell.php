<?php 
require_once("io/sh_io.php");

abstract class Shell extends BaseIO {
    protected static $instanceName = "cmd";
    private static $args;
    private static $subject;
    private static $_dbg = true;

    public final static function init($argv) {
        self::$instanceName = array_shift($argv);

        $match = [];
        self::$args = [];
        self::$subject = [];


        while (sizeof($argv) > 0) {
            $harg = array_shift($argv);
        
            if (preg_match("/[-]+(.*)/", $harg, $match)) {
                if (sizeof($argv) > 0) {
                    if (substr($argv[0], 0, 1) === "-") {
                        self::$args[$match[1]] = true;
                    } else {
                        self::$args[$match[1]] = array_shift($argv);
                    }
                } else {
                    self::$args[$match[1]] = true;
                }
            } else {
                self::$subject[] = $harg;
            }
        }
    }

    public final static function argDump() {
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

    public final static function hasArg(string $name): bool {
        return isset(static::$args[$name]);
    }

    public final static function arg(string $name) {
        return @static::$args[$name];
    }

    public static abstract function main(): int;
    public static abstract function help(): void;

    protected static function writeln(...$objs) {
        self::_writeln(...$objs);
    }

    protected static function write($msg) {
        self::_write($msg);
    }

    public final static function fatal($msg, $where = "Unknown") {
        self::_writeln("!!!! Fatal error in ", $where, ".");
        self::_writeln("\t", $msg);
        die(217);
    }

    public final static function dbg($msg, $where = "Unknown") {
        if (self::$_dbg !== true) return;
        self::_writeln("?? ", $where, ": ", $msg);
    }

    public final static function abort($msg) {
        self::writeln("!!!!! ", $msg);
        self::writeln("Aborted.");
        die(217);
    } 

}

Shell::init($argv);

