<?php

class SiXfer extends Shell {
    public static function help(): void {
        self::writeln("Usage: ", self::$instanceName, " -sixfer");
        // i is massive.csv
        // no count file

        // need a ignore.lst
        // need to only match [0-9a-zA-Z]+
    }

    public static function main(): int {
        if (self::hasArg("i") && ($in = fopen(self::arg("i"), 'r'))) {
            $big = [];
            $h = explode(',', trim(fgets($in)));
            $now = new DateTime();
            while (($line = fgets($in)) !== false) {
                $d = explode(",", trim($line));
                $big[$d[4]] = $d;
            }

            if (self::hasArg("new") && ($new = fopen(self::arg("new"), 'w')) && self::hasArg("upd") && ($upd = fopen(self::arg("upd"), 'w'))) {
                $st = QCDB::getQuery("SELECT * FROM StkItem WHERE Si_AssetStatus LIKE 'I' AND Si_Stock_Code NOT LIKE '[_]%' AND Si_Stock_Code NOT LIKE 'EP[1-9 -]%' AND Si_Stock_Code NOT LIKE '' AND Si_Stock_Code NOT LIKE 'TD%' AND Si_Stock_Code NOT LIKE '% %' AND Si_Stock_Code NOT LIKE 'TERASCAN'");
                fwrite($upd, implode(",", $h) . "\r\n");
                array_shift($h);
                fwrite($new, implode(",", $h) . "\r\n");

                while ($item = $st->fetch(PDO::FETCH_ASSOC)) {
                    if (@$big[$item["Si_Stock_Item"]]) {
                        $big[$item["Si_Stock_Item"]][1] = $item["Si_Stock_Code"];
                        $big[$item["Si_Stock_Item"]][5] = $item["Si_SerialNo"];
                        fwrite($upd, implode(",", $big[$item["Si_Stock_Item"]]) . "\r\n");
                    } else {
                        fwrite($new, implode(",", [
                            $item["Si_Stock_Code"],
                            "Default",
                            "Rental",
                            $item["Si_Stock_Item"],
                            $item["Si_SerialNo"],
                            "",
                            $now->format(_SQL_DATE),
                            1,
                            1,
                            1,
                            "",
                            "",
                            ""
                        ]) . "\r\n");
                    }
                }
                return 0;
            } 
        }


        return 1;
    }

}