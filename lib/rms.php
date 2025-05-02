<?php

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
        return self::getOpportunity("23");
        /*return self::post("https://api.current-rms.com/api/v1/opportunities/23/quick_allocate", [
            "stock_level_asset_number" => "IDE039",
            "quantity" => 1,
            "free_scan" => 1,
            "mark_as_prepared" => 1
        ]);*/
    }
}

RMS::init();