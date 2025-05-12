<?php

/**
 * scanner 1: *
 * scanner 2: +
 * scanner 3: $
 */

 define("_FALLBACK_NAME", [
    "*" => "Scanner 1",
    "+" => "Scanner 2",
    "$" => "Scanner 3",
    "-" => "Scanner 4",
    "£" => "Scanner 5",
    "#" => "Scanner 6",
    "" => "Other Scanner"
 ]);

 define("_FALLBACK_CODE", [
    "CSCTL01" => "count",
    "CSCTL02" => "mark",
    "CSCTL08" => "last",
    "CSCTL09" => "last5",
    "CSCTL10" => "check"
 ]);

 define("_PLACE", [
    "most recent.",
    "second most recent.",
    "third",
    "fourth",
    "fifth most recent."
 ]);


class CountRMS extends Shell {
    public static function help(): void {
        self::writeln("Usage: ", self::$instanceName, " -count");
        self::writeln("\t -o [path_to_output_file] *");
        self::writeln("\t -a | append to output file");
        self::writeln("\t -i [path_to_input_file] | to check for colisions   OR");
        self::writeln("\t -is input file and output file are the same. (implies -a)");
        self::writeln("\t -q [bool_check_qc_data] | default: true");
        self::writeln("\t -speak [some_command] | some command in YOUR path to speak prompts aloud");
        self::writeln("\t!! args with *: required");
        self::writeln("\t!! args with | optional");
        self::writeln("\t -q true [etc] OR -q (omitted):");
        self::writeln("\t Control codes can be read through QCCheck DB. Barcodes will be checked to exist in QCCheck");
        self::writeln("\t -q false");
        self::writeln("\t if you don't use QCCheck, will not attempt to use it at all.");
    }

    private static $in = null;
    private static $out = null;
    private static $canSpeak = false;
    private static $speak = null;
    private static $lookup = null;
    private static $counts = null;
    private static $count = 0;
    private static $hasQc = false;
    private static $qcLookup = null;
    private static $qcNick = null;
    private static $qcLookupNick = null;
    private static $qcLookupPp = null;
    private static $running = true;
    private static $history = [];
    private static $import = null;

    private static function ctlCode($who, $what, $prefix) {
        if ($action = @_FALLBACK_CODE[$what]) {
            if ($action == "count") {
                self::say($who . " asked for a count.");
                if (self::$hasQc) {
                    foreach (self::$counts as $k => $v) {
                        self::$qcLookupPp->execute([$k]);
                        $msg = "Counted $v of " . strtoupper(implode(" ", str_split(self::$qcLookupPp->fetchColumn())));
                        $msg = str_replace("C F 1 2 5 A 3 P ", "1 2 5 amp three-phase ", $msg);
                        $msg = str_replace("C F 0 6 3 A 3 P", "63 amp three-phase ", $msg);
                        $msg = str_replace("C F 0 6 3 A 1 P", "63 amp single-phase ", $msg);
                        $msg = str_replace("C F 0 3 2 A 3 P", "32 amp three-phase", $msg);
                        $msg = str_replace("C F 0 3 2 A 1 P", "32 amp single-phase", $msg);
                        $msg = str_replace("C F 0 1 6 A 1 P", "16 amp single-phase", $msg);
                        for ($i = 0; $i < 105; $i += 5) {
                            $a = implode(" ", str_split(str_pad((string)$i, 3, "0", STR_PAD_LEFT))) . " M";
                            $msg = str_replace($a, $i . " metre", $msg);
                        }
                        self::say($msg);
                    }
                }
                self::say(self::$count . " items counted since last reset.");
            } else if ($action == "mark") {
                self::say($who . " reset the count.");
                self::$counts = [];
                self::$count = 0;
            } else if ($action == "last") {
                if (sizeof(self::$history) > 0) {
                    $last = self::$history[sizeof(self::$history) - 1];
                    self::say("last was " . implode(" ", str_split($last[0])) . " by " . $last[1]);
                } else {
                    self::say("No recorded scan-history.");
                }
            } else if ($action == "last5") {
                $o = 0;
                for ($i = sizeof(self::$history) - 1; $i > -1; $i--) {
                    self::say(_PLACE[$o++]);
                    self::say(implode(" ", str_split(self::$history[$i][0])) . " by " . self::$history[$i][1]);
                }
            } else if ($action == "check") {
                self::say("Scanner called " . $who . " is receiving.");
            }
            return;
        } else {
            if (self::lQcNick($prefix, $what)) {
                self::say($who . " changed to " . self::$qcNick[$prefix]);
            } else {
                self::say("Unknown control sequence.");
            }
            return;
        }
    }

    private static function say($what, $echo = true) {
        if ($echo) self::writeln("Notice: ", $what);
        if (!self::$canSpeak) return;
        $cmd = self::$speak . " " . escapeshellarg($what);
        `$cmd`;
    }

    private static function count($who, $what) {
        $now = new DateTime();
        if (@self::$lookup[$what]) {
            $diff = $now->diff(self::$lookup[$what][0]);
            $hdiff = "";
            if ($diff->d > 0) $hdiff .= $diff->d . " days, ";
            if ($diff->h > 0) $hdiff .= $diff->h . " hours, ";
            if ($diff->i > 0) $hdiff .= $diff->i . " minutes, ";
            if ($diff->s > 0) $hdiff .= $diff->s . " seconds ";



            self::say("$who dooplikit. " . implode(" ", str_split($what)) . " scanned " . $hdiff . " ago by " . self::$lookup[$what][1], false);
            self::writeln("Warn: " . $what . " already scanned by " . $who . " " . $hdiff . " ago. \t\t " . implode(" ", str_split($what)));
        } else {
            if (self::$hasQc) {
                self::$qcLookup->execute([$what]);
                $data = self::$qcLookup->fetchAll(PDO::FETCH_ASSOC);
                if (sizeof($data) == 1) {
                    if (self::hasArg("import")) {
                        if (self::arg("import") != $data[0]["ppp"]) {
                            self::say("Expected " . self::arg("import") . " but $what was " . $data[0]["ppp"] . ".\t NOT COUNTED");
                            return;
                        }
                    }
                    self::writeln($who, " scanned ", $data[0]["code"], " :: ", $data[0]["ppp"]);
                    fwrite(self::$out, $data[0]["code"] . "|" . $now->format(_FILE_DATE) . "|" . $who . "\n");
                    self::$lookup[$what] = [$now, $who];
                    self::$history[] = [$what, $who];
                    if (@self::$counts[$data[0]["pp"]]) {
                        self::$counts[$data[0]["pp"]]++;
                    } else {
                        self::$counts[$data[0]["pp"]] = 1;
                    }
                    self::$count++;
                    self::say(self::$count, false);
                    if (sizeof(self::$history) > 5) array_shift(self::$history);
                } else {
                    if (self::$import) {
                        QC::addSi($what, self::$import);
                        self::say($what . " imported as " . self::$import["Pp_Stock_Code"]);
                    } else {
                        self::say(implode(" ", str_split($what)) . " could not be found in QCCheck.");
                    }
                    //
                }
            }
        }
    }

    private static function scanLoop(): int {
        `stty -echo`;
        while (self::$running) {
            $next = trim(fgets(STDIN));
            $nick = "Unknown";
            $prefix = "";

            foreach (_FALLBACK_NAME as $k => $v) {
                if (strpos($next, $k, 0) !== false) {
                    if (@self::$qcNick[$k]) {
                        $nick = self::$qcNick[$k];
                    } else {
                        $nick = $v;
                    }
                    $prefix = $k;
                    if ($prefix != "") $next = substr($next, 1);
                    break;
                }
            }

            if (preg_match("/CSCTL[0-9][0-9]/", $next)) {
                self::ctlCode($nick, $next, $prefix);
            } else {
                self::count($nick, $next);
            }
        }

        return 0;
    }

    private static function qcInit() {
        if (!class_exists("qcdb")) {
            self::writeln("!! Cannot use QCDB: class not loaded");
            return 3;
        }
        self::$hasQc = true;
        self::$qcLookup = QCDB::prepare("SELECT Si_Stock_Item AS code, Si_Stock_CodeID AS pp, Si_Stock_Code AS ppp
        FROM StkItem WHERE Si_Stock_Item LIKE ?");
        self::$qcLookupNick = QCDB::prepare("SELECT Si_Stock_Item, Si_SerialNo, Si_Stock_Code FROM StkItem
        WHERE Si_Stock_Code LIKE 'TERASCAN' AND Si_Stock_Item LIKE 'CSCTL[0-9][0-9]' AND Si_Stock_Item LIKE ?");
        self::$qcLookupPp = QCDB::prepare("SELECT Pp_Stock_Code FROM ProdPattern WHERE Pp_ID = ?");
        self::$qcNick = [];
    }

    private static function lQcNick($prefix, $ctlCode) {
        self::$qcLookupNick->execute([$ctlCode]);
        $nick = self::$qcLookupNick->fetchColumn(1);
        if (preg_match("/Nick (.*)/", $nick)) {
            self::$qcNick[$prefix] = explode(" ", $nick)[1];
            return true;
        }

        return false;
    }

    public static function main(): int {
        if (!self::hasArg("o")) {
            self::help();
            return 1;
        }

        $oa = "w";

        if (self::hasArg("is")) {
            if (!file_exists(self::arg("o")) || !is_readable(self::arg("o")) || is_dir(self::arg("o")) || !is_writable(self::arg("o"))) {
                self::writeln("!! Input/Output '" . self::arg("o"). "' does not exist, is not readable/writable or is a directory.");
                return 1;
            }
            if (self::hasArg("i")) {
                self::writeln("Cannot have both -is and -i");
                return 1;
            }
            self::$in = fopen(self::arg("o"), "r");
            self::$out = fopen(self::arg("o"), "a");
        } else {
            if (!is_writeable(self::arg("o")) || is_dir(self::arg("o"))) {
                self::writeln("!! Output '" . self::arg("o"). "' does not exist, is not writable or is a directory.");
                return 1;
            }
            if (self::hasArg("a")) {
                self::$out = fopen(self::arg("o"), "a");
            } else {
                self::$out = fopen(self::arg("o"), "w");
            }
        }

        if (self::hasArg("import")) {
            self::$import = QC::getPP(self::arg("import"));
        }

        if (self::hasArg("i")) {
            if (!file_exists(self::arg("i")) || !is_readable(self::arg("i")) || is_dir(self::arg("i"))) {
                self::writeln("!! Input '" . self::arg("i") . "' does not exist, is not readable or is a directory.");
                return 1;
            }
            self::$in = fopen(self::arg("i"), "r");
        }

        if (self::hasArg("speak")) {
            $test = "which " . self::arg("speak");
            $fn = `$test`;
            self::$canSpeak = true;
            self::$speak = self::arg("speak");
        }

        if (!self::hasArg("q") || self::arg("q") === true) {
            self::qcInit();
        }

        if (self::$out) {
            self::$lookup = [];
            if (self::$in) {
                $in_pos = 0;
                while ($next = fgets(self::$in)) {
                    $in_pos++;
                    $d = explode("|", trim($next));

                    if (sizeof($d) >= 1) {
                        self::$lookup[$d[0]] = [];
                        if (sizeof($d) > 1) {
                            self::$lookup[$d[0]][] = DateTime::createFromFormat(_FILE_DATE, @$d[1]);
                        } else {
                            self::$lookup[$d[0]][] = new DateTime("now");
                        }
                    } else {
                        self::writeln("!! Error in input on line ", $in_pos, ":");
                        self::writeln("\tExpected format: [asset_number]|[", _FILE_DATE, "]|[nickname]");
                        return 3;
                    }

                    if (sizeof($d) > 2) {
                        if (sizeof($d) > 3) {
                            self::writeln("!! Error in input on line ", $in_pos, ":");
                            self::writeln("\tExpected at most 3 elements, got ", sizeof($d));
                            return 3;
                        }
                        self::$lookup[$d[0]][] = $d[2];
                    } else {
                        self::$lookup[$d[0]][] = "Unknown";
                    }
                }

                fclose(self::$in);
            }

            self::$counts = [];
            self::$count = 0;
            self::$running = true;

            return self::scanLoop();

        } else {
            self::writeln("!! Output fp broken.");
            return 2;
        }
    }
}