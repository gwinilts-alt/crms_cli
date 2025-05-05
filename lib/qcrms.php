<?php

class QCRMS {

    /**
     * Sync RMS to the current state of QCCheck
     * @return int some status code:
     * 0 is a successful sync
     * > 9 indicates a failure with QC
     * > 19 indicates a failure with RMS
     * 77 indicates there is no change
     *
     *
     * Synchronizing RMS with QCCheck would involve deleting or ammending items that are in RMS to reflect the state of QCCheck
     * Editing key information in RMS is not allowed. i.e a change in RMS will not be affected in QC. There will be no writes to QC.
     * A succesful sync with RMS will gaurantee:
     *  Items that do not exist in QCCheck will not exist in RMS
     *  Items that have changed in QCCheck will be updated in RMS
     *   
     *
     * the sync workflow would look like this:
     *
     *
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
    public static function sync(): int {

    }
}