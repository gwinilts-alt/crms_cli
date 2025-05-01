<?php

class QC {
    public static function ppMatch($what) {
        $x = QCDB::prepare("SELECT Pp_Stock_Code FROM ProdPattern WHERE Pp_Stock_Code LIKE ?");
        $x->execute([$what]);

        return $x->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function siMatch($what) {
        $x = QCDB::prepare("SELECT Si_Stock_Item FROM StkItem WHERE Si_Stock_Item LIKE ?");
        $x->execute([$what]);

        return $x->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function siInfo($what, $which = "%") {
        $x = QCDB::prepare("SELECT Si_ID, Si_Stock_Item, Si_Stock_Code, Si_SerialNo, Si_AssetStatus, Si_Create, Si_Amend FROM StkItem WHERE Si_Stock_Item LIKE ? AND Si_Stock_Code LIKE ?");
        $x->execute([$what, $which]);

        return $x->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function rhList($what, $which, $after, $before, $date = false, $code = false) {
        $st = QCDB::prepare("SELECT DISTINCT Rh_Stock_Item, Rh_Stock_Code, Rh_Test_Date
            FROM ResultH
            WHERE Rh_Test_Date < ? AND Rh_Test_Date > ? AND Rh_Stock_Code LIKE ? AND Rh_Test_Result LIKE 'PASS' AND Rh_Stock_Item LIKE ?
            ORDER BY Rh_Test_Date DESC");

        $st->execute([$before->format(_SQL_DATE), $after->format(_SQL_DATE), $which, $what]);

        $out = [];

        while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
            $o = [$r["Rh_Stock_Item"]];
            if ($code) $o[] = $r["Rh_Stock_Code"];
            if ($date) $o[] = $r["Rh_Test_Date"];
            $out[] = implode(", ", $o);
        }

        return $out;
    }
}