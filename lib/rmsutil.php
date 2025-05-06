<?php

define("_RMS_DATE", "Y-m-dTG:i:sz");
define("_BASE_URI", "https://api.current-rms.com/api/v1/");

class RMS {
    private static $token = null;
    private static $sdbm = null;
    private static $pageTracker = null;
    private static $mustSleep = false;
    private static $reqStart = 0;
    private static $ch = null;

    /**
     * Checks if we have been rate limited
     * @param mixed $curlInfo
     * @return bool TRUE if rateCheck caused a sleep. False otherwise.
     */
    private static function rateCheck(int $code): bool {
        $slept = false;
        if ($code == 429) {
            self::rateSleep();
            $slept = true;
        }
        return $slept;
    }

    private static function rateSleep(): void {
        if (self::$mustSleep) {
            usleep(1000 * (1500 - self::$reqStart));
        }
    }

    private static function beginStage() {
        self::$reqStart = microtime();
    }

    public static function init() {
        self::$token = trim(file_get_contents("protect/api.key"));
        self::$sdbm = trim(file_get_contents("protect/subdomain"));
        self::$pageTracker = [];
        self::$mustSleep = false;
        self::$ch = curl_init();
    }

    private static function getOpportunity($number) {
        $number = str_pad($number, 10, "0", STR_PAD_LEFT);
        return self::get("opportunities?q[number_eq]=$number");
    }

    private static function get($uri, $uparam = [], $recur = 0): array|int {
        return self::req($uri, "GET", $uparam);
    }

    /**
     * Performs an RMS api call
     * @param mixed $uri the api end point
     * @param mixed $method the HTTP action to perform
     * @param mixed $udata url params to include
     * @param mixed $data POST|PUT data to include
     * @param mixed $recur how deep are we
     * @return array|int
     */
    private static function req($action, $method = "GET", $udata = ["page_size" => 100, "page" => 1], $data = [], $recur = 0): array|int {
        if ($recur > 9) Shell::fatal("Rate limit has led to infinite recursion.", "RMS::req");
        Shell::dbg($method . " => " . $action, "RMS::req");
        self::beginStage();
        $head = ["Content-Type:application/json", "X-SUBDOMAIN:" . self::$sdbm, "X-AUTH-TOKEN:" . self::$token];

        $operand = [];
        foreach ($udata as $k => $v) {
            $operand[] = $k . "=" . ($v);
        }

        if (sizeof($operand) > 0) {
            Shell::dbg("$action?" . implode("&", $operand), "RMS::req");
            curl_setopt(self::$ch, CURLOPT_URL, _BASE_URI . "$action?" . implode("&", $operand));
        } else curl_setopt(self::$ch, CURLOPT_URL, _BASE_URI . $action);

        $valid = false;

        if ($method == "GET" || $method == "DELETE") {
            $valid = true;
        }

        if ($method == "POST" || $method == "PUT") {
            $valid = true;
            $payload = json_encode($data);
            $head[] = "Content-Type:application/json";
            curl_setopt(self::$ch, CURLOPT_POSTFIELDS, $payload);
        }

        if ($method == "PUT") {
            curl_setopt(self::$ch, CURLOPT_CUSTOMREQUEST, "PUT");
        }

        curl_setopt(self::$ch, CURLOPT_HTTPHEADER, $head);
        curl_setopt(self::$ch, CURLOPT_RETURNTRANSFER, true);

        if ($valid) {
            $response = curl_exec(self::$ch);
            $result = (int)curl_getinfo(self::$ch, CURLINFO_HTTP_CODE);

            if (self::rateCheck($result)) {
                Shell::dbg("We have been rate checked.");
                return self::req($action, $method, $udata, $data, $recur + 1);
            } else {
                if ($result == 200) {
                    return json_decode($response, true);
                } else {
                    Shell::fatal("Response Code not 200: " . $result, "RMS::productByName");
                }
            }
        }

        return 0;
        
    }

    private static function post($uri, $uparam = [], $data = []): array|int {
        return self::req($uri, "POST", $uparam, $data);
    }

    /**
     * Get the product whose name is the first match of $name
     * @param mixed $name the product we're looking for
     * @return []|null returns the matched product or null if the response code is not '200'
     */
    public static function productByName($name):array {
        $q = self::get("products", ["page" => 1, "per_page" => 100, "filtermode" => "active", "q[name_matches]" => "$name"]);

        if (sizeof($q["products"]) > 0) return $q["products"][0];

        return [];
    }

    /**
     * 
     * Get at most $size assets whose pid is $pid in blocks of $size with $page being the block number.
     * @param mixed $pid the product id
     * @param mixed $size the amount of assets to get (<= 100)
     * @param mixed $page the block number to get
     * @return array|null Returns an array of assets if there are assets for this page. Returns an empty array if there are no more assets. Returns false if the underlying request has a code other than '200'.
     */
    private static function assetsByPID($pid, $size = 100, $page = 1): array|null {
        $q = self::get("products/$pid/stock_levels", ["per_page" => $size, "page" => $page]);
        
        if (sizeof($q["stock_levels"]) > 0) return $q["stock_levels"];

        return [];
    }

    /**
     * Next page of assets associated with product whose id is $pid
     * @param mixed $pid the RMS_id of the product
     * @return []|bool Returns TRUE if there are more assets to fetch. Returns an array if the asset list is complete. Returns false on failure.
     * 
     * If this function gets rate limited it will sleep for < 1000ms
     */
    private static function nextPageOfAssets($pid): array|bool {
        Shell::dbg($pid, "nextPageOfAssets");
        if (@self::$pageTracker[$pid]) {
            self::$pageTracker[$pid]["last_page"]++;
            $next = self::getPageOfAssets($pid, self::$pageTracker[$pid]["last_page"]);

            if (sizeof($next) > 0) {
                self::$pageTracker[$pid]["list"] = array_merge(self::$pageTracker[$pid]["list"], $next);
                return self::nextPageOfAssets($pid);
            } else {
                $fullPage = self::$pageTracker[$pid]["list"];
                self::$pageTracker[$pid] = null;
                return $fullPage;
            }
        } else {
            self::$pageTracker[$pid] = [
                "last_page" => 0,
                "list" => self::getPageOfAssets($pid, 0)
            ];

            return self::nextPageOfAssets($pid);
        }
    }

    public static function allAssetsByPID($pid) {
        return self::nextPageOfAssets($pid);
    }

    private static function getPageOfAssets($pid, $page): array {
        return self::assetsByPID($pid, 100, $page);
    }

    public static function test() {
        $cf5 = self::productByNane("CF016A1P005M");

        self::assetsByPID("jim");

    }
}

RMS::init();