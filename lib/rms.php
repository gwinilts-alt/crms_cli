<?php

define("_RMS_DATE", "Y-m-dTG:i:sz");

class RMS {
    private static $token = null;
    private static $sdbm = null;

    public static function init() {
        self::$token = trim(file_get_contents("protect/api.key"));
        self::$sdbm = trim(file_get_contents("protect/subdomain"));
    }

    private static function getOpportunity($number) {
        $number = str_pad($number, 10, "0", STR_PAD_LEFT);
        return self::get("https://api.current-rms.com/api/v1/opportunities?q[number_eq]=$number");
    }

    private static function get($url) {
        $ch = curl_init($url);
        curl_setopt( $ch, CURLOPT_HTTPHEADER, ["Content-Type:application/json", "X-SUBDOMAIN:" . self::$sdbm, "X-AUTH-TOKEN:" . self::$token]);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        $result = curl_exec($ch);
        return [
            "response" => json_decode($result, true),
            "info" => curl_getinfo($ch)
        ];
    }

    public static function productByNane($name) {
        $q = self::get("https://api.current-rms.com/api/v1/products?page=1&per_page=20&filtermode=active&q[name_matches]=$name");

        var_dump($q);

        if ($q["info"]["http_code"] == 200) {
            if (sizeof($q["response"]["products"]) > 0) return $q["response"]["products"][0];
        }

        return null;
    }

    public static function assetsByPID($id, $size = 100, $page = 1) {
        $q = self::get("https://api.current-rms.com/api/v1/products/$id/stock_levels?page=$page&per_page=$size");
        
        var_dump($q);
    }

    private static function post($url, $data = [], $headers = []) {
        $ch = curl_init($url);

        $payload = json_encode( $data );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, ["Content-Type:application/json", "X-SUBDOMAIN:" . self::$sdbm, "X-AUTH-TOKEN:" . self::$token]);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        $result = curl_exec($ch);
        return [
            "response" => json_decode($result, true),
            "info" => curl_getinfo($ch)
        ];
    }

    public static function test() {
        $cf5 = self::productByNane("CF016A1P005M");

        self::assetsByPID($cf5["id"]);

    }

    public static function sync() {
        $now = new DateTime();
        // create a stock check
        $q = self::post("https://api.current-rms.com/api/v1/stock_checks", [
            "store_id" => 1,
            "stock_check_at" => $now->format(_RMS_DATE),
            "subject" => "generated stock check"
        ]);

        var_dump($q);
    }
}

RMS::init();