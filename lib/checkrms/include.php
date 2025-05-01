<?php

define("_HEAD", [
    "id",
    "asset_number",
    "name",
    "stock_type",
    "location",
    "quantity_held",
    "quantity_booked",
    "quantity_available",
    "quantity_counted"
]);

class CheckRMS extends Shell {
    public static function help(): void {
        self::writeln("Usage: ", self::$instanceName, " -check");
        self::writeln("\t -i [path_to_count_file] *");
        self::writeln("\t -set [path_to_stock_check] *");
        self::writeln("\t -o [path_to_output] *");
        self::writeln("\t -s [path_to_skip_file] |");
    }

    private static $in = null;
    private static $rms = null;
    private static $out = null;
    private static $cLookup = null;
    private static $ignore = null;

    public static function main(): int {
        if (!self::hasArg("i")) {
            self::help();
            return 1;
        }

        if (!self::hasArg("set")) {
            self::help();
            return 1;
        }

        if (!file_exists(self::arg("i")) || !is_readable(self::arg("i")) || is_dir(self::arg("i"))) {
            self::writeln("!! Input count file '", self::arg("i"), " does not exist, is not readable, or is a directory.");
        }

        if (!file_exists(self::arg("set")) || !is_readable(self::arg("set")) || is_dir(self::arg("set"))) {
            self::writeln("!! Input rms file '", self::arg("set"), " does not exist, is not readable, or is a directory.");
        }

        if (is_dir(self::arg("o")) || !is_writable(self::arg("o"))) {
            self::writeln("!! Output check file '", self::arg("o"), " is not writable, or is a directory.");
        }

        self::$ignore = [];

        if (self::hasArg("s")) {
            if (file_exists(self::arg("s")) && is_readable(self::arg("s"))) {
                $ifp = fopen(self::arg("s"), "r");

                while ($ig = fgets($ifp)) {
                    foreach (QC::ppMatch(trim($ig)) as $v) {
                        self::$ignore[$v] = true;
                    }
                }
            }
        }

        var_dump(self::$ignore);

        self::$in = fopen(self::arg("i"), "r");
        self::$rms = fopen(self::arg("set"), "r");
        self::$out = fopen(self::arg("o"), "w");

        if (self::$in) {
            self::$cLookup = [];

            while ($line = fgets(self::$in)) {
                $d = explode("|", trim($line));

                $lookup[$d[0]] = true;
            }

            if (self::$out && self::$rms) {
                fwrite(self::$out, trim(fgets(self::$rms)) . "\r\n");

                while ($line = fgets(self::$rms)) {
                    $d = explode(",", trim($line));

                    if (@self::$ignore[$d[2]] || @$lookup[$d[1]]) {
                        $d[sizeof($d) - 1] = "1.0";
                        fwrite(self::$out, implode(",", $d) . "\r\n");
                    } else {
                        $d[sizeof($d) - 1] = "0";
                        fwrite(self::$out, implode(",", $d) . "\r\n");
                    }
                }

                return 0;

            } else {
                self::writeln("!! Broken pipe (-rms or -o)");
                return 2;
            }
        } else {
            self::writeln("!! Broken pipe (-i)");
            return 2;
        }

        return 0;
    }
}