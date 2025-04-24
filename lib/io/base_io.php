<?php

class BaseIO {
    private static $out = null;
    private static $level = 0;
    private static $lLevel = 0;

    private static $handlers = [];

    public const L_WARN = 0;
    public const L_INFO = 1;
    public const L_NOTICE = 2;
    public const L_DEBUG = 3;

    public const EOL = "\n";
    public const TIME_FORMAT = "y.m.d H:i:s";

    protected static function _write($msg = "") {
        if (self::$out === null) return;

        fwrite(self::$out, $msg);
    }

    protected static function _writeln(...$msgs) {
        if (self::$out === null) return;
        foreach ($msgs as $msg) {
            self::_write($msg);
        }
        self::_write(self::EOL);
    }

    protected static function logify($msg) {
        if (is_array($msg)) return self::arrInfo($msg);
        if (is_object($msg)) return self::objInfo($msg);
        if (is_string($msg)) return "\"$msg\"";

        return gettype($msg) . " [" . $msg . "]";
    }
    public static function put($msg, $level = self::L_NOTICE, $ctx = " Notice: ") {
        if ($level > self::$level) return;
        $time = new DateTime("now");
        $out = "[" . $time->format(self::TIME_FORMAT) . "]" . $ctx;

        if (is_array($msg)) {
            $out .= self::arrInfo($msg);
        } else if (is_object($msg)) {
            $out .= self::objInfo($msg);
        } else if (is_string($msg)) {
            $out .= "\"$msg\"";
        } else if (is_bool($msg)) {
            if ($msg) {
                $$out .= "bool [true]";
            } else {
                $out .= "bool [false]";
            }
        } else {
            $out .= gettype($msg) . " [" . $msg . "]";
        }
        self::_writeln($out);
    }

    public static function mark(string $msg) {
        if (self::$out === null) return;
        self::_writeln("\n\n" . $msg . "\n");
    }

    private static function objInfo(object $obj, $depth = 0) {
        $t = "";
        for ($i = 0; $i < $depth; $i++) {
            $t .= "  ";
        }
        $out = "";
        if (isset(self::$handlers[get_class($obj)])) {
            $out .= self::$handlers[get_class($obj)]($obj, $depth) . "\n";
        } else {
            $out .= " Object (" . get_class($obj) . ")\n";
        }
        return $out;
    }

    protected static function arrInfo(array $arr, $depth = 0) {
        $t = "";
        for ($i = 0; $i < $depth; $i++) {
            $t .= "  ";
        }
        $out = "Array [\n";
        foreach ($arr as $k => $v) {
            $out .= "$t  '$k': ";
            if (is_array($v)) {
                $out .= self::arrInfo($v, $depth + 1) . "\n";
            } else if (is_object($v)) {
               $out .= self::objInfo($v);
            } else if (is_string($v)) {
                $out .= " String \"" . $v . "\"\n";
            } else if (is_bool($v)) {
                $out .= $v ?"bool[true]":"bool[false]";
            } else {
                $out .= " " . gettype($v) . "[" . $v . "]\n";
            }
        }
        return $out . "$t]";
    }

    public static function addObjHandler(string $className, callable $handler) {
        self::$handlers[$className] = $handler;
    }

    public static function debug($msg, $ctx = "Debug") {
        self::put($msg, self::L_DEBUG, " $ctx: ");
    }

    public static function getDebugLogger($ctx) {
        return function($msf) use ($ctx) {
            Log::debug($msf, $ctx);
        };
    }

    public static function open($fn) {
        self::$out = fopen($fn,"a");
    }

    public static function setDebug() {
        self::$level = self::L_DEBUG;
    }
}