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
}