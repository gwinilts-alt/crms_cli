<?php

class QC {
    
    private static $lQCHasItem = null;
    private static $lQCHasItemPP = null;

    public static function ppMatch($what, $template = false) {
        if ($template) {
            $x = QCDB::prepare("SELECT Pp_Stock_Code FROM ProdPattern WHERE Pp_Stock_Code LIKE ?");
        } else {
            $x = QCDB::prepare("SELECT Pp_Stock_Code FROM ProdPattern WHERE Pp_Stock_Code LIKE ? AND Pp_Stock_Code NOT LIKE '[_]%'");
        }
        $x->execute([$what]);

        return $x->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function getPP($what) {
        $x = QCDB::prepare("SELECT * FROM ProdPattern WHERE Pp_Stock_Code LIKE ? AND Pp_Stock_Code NOT LIKE '[_]%'");

        $x->execute([$what]);

        return $x->fetch(PDO::FETCH_ASSOC);
    }

    public static function addSi($barcode, $pp) {
        $x = QCDB::prepare("INSERT INTO StkItem (
            Si_Stock_Code,
            Si_Stock_CodeID,
            Si_AssetStatus,
            Si_Create,
            Si_Amend,
            Si_Description,
            Si_Stock_Item
        ) VALUES (?, ?, 'I', GETDATE(), GETDATE(), ?, ?)");

        $x->execute([$pp["Pp_Stock_Code"], $pp["Pp_ID"], $pp["Pp_Description"], $barcode]);
    }

    public static function hasItem(string $asset): bool {
        self::$lQCHasItem->execute([$asset]);
        return self::$lQCHasItem->fetch(PDO::FETCH_COLUMN) > 0;
    }

    public static function hasItemPP(string $asset, string $code): bool {
        self::$lQCHasItemPP->execute([$asset, $code]);
        return self::$lQCHasItemPP->fetch(PDO::FETCH_COLUMN) > 0;
    }

    public static function init() {
        self::$lQCHasItem = QCDB::prepare("SELECT COUNT(Si_ID) FROM StkItem WHERE Si_Stock_Item LIKE ? AND Si_AssetStatus LIKE 'I'");
        self::$lQCHasItemPP = QCDB::prepare("SELECT COUNT(Si_ID) FROM StkItem WHERE Si_Stock_Item LIKE ? AND Si_AssetStatus LIKE 'I' AND Si_Stock_Code = ?");
    }

    //1: get the Pp list from QCCheck: QC::ppQuery($includeTemplate = false)


    /**
     * Return a list of ProductPattern in the form [Pp_ID => Pp_]
     * @param bool $includeTemplate templates are included if $includeTemplate is true (false ignores anything beginning with an underscore)
     * @return array
     */
    public static function ppList(bool $includeTemplate = false) {
        if ($includeTemplate === TRUE) {
            $q = QCDB::getQuery("SELECT Pp_Stock_Code FROM ProdPattern");
        } else {
            $q = QCDB::getQuery("SELECT Pp_Stock_Code FROM ProdPattern WHERE Pp_Stock_Code NOT LIKE '[_]%'");
        }
        return $q->fetchAll(PDO::FETCH_COLUMN);
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

    public static function siICount($which) {
        $q = QCDB::prepare("SELECT COUNT(Si_ID) FROM StkItem WHERE Si_Stock_Code LIKE ? AND Si_AssetStatus LIKE 'I'");
        $q->execute([$which]);

        return $q->fetch(PDO::FETCH_COLUMN);
    }

    public static function results6m($what = "%") {
        $then = new DateTime("- 6 months");
        $st = QCDB::prepare("SELECT DISTINCT Rh_Stock_Item FROM ResultH WHERE Rh_Test_Date > ? AND Rh_Test_Result LIKE 'PASS' AND Rh_Stock_Code LIKE ?");
        $st->execute([$then->format(_SQL_DATE), $what]);

        return $st->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function results1y($what = "%") {
        $then = new DateTime("- 1 year");
        $st = QCDB::prepare("SELECT DISTINCT Rh_Stock_Item FROM ResultH WHERE Rh_Test_Date > ? AND Rh_Test_Result LIKE 'PASS' AND Rh_Stock_Code LIKE ?");
        $st->execute([$then->format(_SQL_DATE), $what]);

        return $st->fetchAll(PDO::FETCH_COLUMN);
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

QC::init();
