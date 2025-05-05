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
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type:application/json", "X-SUBDOMAIN:" . self::$sdbm, "X-AUTH-TOKEN:" . self::$token]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
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
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type:application/json", "X-SUBDOMAIN:" . self::$sdbm, "X-AUTH-TOKEN:" . self::$token]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
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

    // Synchronizing RMS with QCCheck would involve deleting or ammending items that are in RMS to reflect the state of QCCheck
    // Editing key information in RMS is not allowed.
    // A succesful sync with RMS will gaurantee:
    //  Items that do not exist in QCCheck will not exist in RMS
    //  Items that have changed in QCCheck will be updated in RMS
    //   

    // the sync workflow would look like this:
    
    /**
     * 1: get the Pp list from QCCheck: QC::ppQuery($includeTemplate = false)
     * Foreach member try to get a list of assets from RMS
     * If there is a list:
     *  For each list item find out if there is a QC item with the same asset number
     *  if the Stock code is different, add to the alterations list
     *  If there is no correlation, add to the removals list
     * 
     * Work sleep loop:
     *      fetch page of 100 assets
     *      if http code 429 (rate limit) sleep for 1500 - rq_t ms
     *      iterate
     *      loop
     * 
     *      
     *      
     * 
     * If there is no list:
     *  Generate a new Product and NB the Stock_code
     *  Associate all further items with this code
     *  Later add all the items
     * 
     * 
     * This should exclude colisions?
     */

     /**
      * Fetching a Stock Level list is rate limited.
      * Up to 100 items per page
      * 60 pages per minute
      * so 6000 items per minute if there is a big list

      * You can fetch a stock-check list, but it will not contain items not counted during the stock check.


      * Or there could be an RMS server that can perform up to some number of requests by looking after a queue
      * It is possible to retrieve a list of item, counted or uncounted, using stock_levels
      * It would group
      */

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