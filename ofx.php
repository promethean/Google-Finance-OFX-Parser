<?php

/**
 * Parse Google Finance OFX Exports
 * Returns a JSON encoded string which you can do whatever you like with
 *
 **/
class Google_Finance_OFX_Parser
{

    private $ofx;

    /**
     * Constructor
     * @param string $ofx OFX file from Google finance
     */
    public function __construct($ofx)
    {
        $this->ofx = $ofx;
    }

    /**
     * Date Helper for the class
     * @param  string $input date string in the format 20120908
     * @return string        date in the format "Y-m-d"
     */
    private function format_date($input)
    {
        $date = DateTime::createFromFormat ( "Ymd" , $input);
        return (string) $date->format('Y-m-d');
    }

    /**
     * Parses the OFX  and returns JSON object
     * @return string JSON object
     */
    public function parse() {

        $portfolio = new StdClass;
        $portfolio->txns = array();
        $portfolio->cash_txns = array();
        $portfolio->stocks = array();

        // Parse Portfolio
        $lines = explode("\n", $this->ofx);
        $txn = null;
        foreach ($lines as $line) {

            if (strstr($line, '<CURDEF>')) {
                $portfolio->currency = substr($line, 8);
            }elseif (strstr($line, '<ACCTID>')) {
                $portfolio->name = substr($line, 8);
            }

            // New Stock transaction.
            if ($line == "<BUYSTOCK>" OR $line == "<SELLSTOCK>") {

                $txn = new stdClass();

                // Init data attributes, so nothing is undefined.
                $txn->date = null;
                $txn->ticker = null;
                $txn->shares = null;
                $txn->price = null;
                $txn->commission = null;
                $txn->memo = null;
                $txn->total = null;
                $txn->type = null;
            }

            if (strstr($line, "<DTTRADE>") !== false) {
                $txn->date = $this->format_date(substr($line, 9, -10));
            }
            elseif (strstr($line, "<UNIQUEID>") !== false) {
                $txn->ticker = (substr($line, 10));
            }
            elseif (strstr($line, "<UNITS>") !== false) {
                $txn->shares = floatval(substr($line, 7));
            }
            elseif (strstr($line, "<UNITPRICE>") !== false) {
                $txn->price = floatval(substr($line, 11));
            }
            elseif (strstr($line, "<COMMISSION>") !== false) {
                $txn->commission = substr($line, 12);
            }
            elseif (strstr($line, "<TOTAL>") !== false) {
                $txn->total = substr($line, 7);
            }
            elseif (strstr($line, "<MEMO>") !== false) {
                $txn->memo = substr($line, 6);
            }
            elseif (strstr($line, "<SELLTYPE>") !== false) {
                $txn->type = 'SELL';
            }
            elseif (strstr($line, "<BUYTYPE>") !== false) {
                $txn->type = 'BUY';
            }

            // End of Stock transaction.
            if ($line == "</BUYSTOCK>" OR $line == "</SELLSTOCK>") {
                $portfolio->txns[]= $txn;
            }

            // Open new Cash transaction.
            if ($line == "<INVBANKTRAN>") {
                $cash = new StdClass;
            }elseif (strstr($line, "<DTPOSTED>") !== false) {
                $cash->date = $this->format_date(substr($line, 10, -10));
            }elseif (strstr($line, "<TRNAMT>") !== false) {
                $cash->total = (substr($line, 8));
            }elseif($line == "</INVBANKTRAN>") {
                $portfolio->cash_txns[]= $cash;
            }

            // Open new Stock
            if ($line == "<STOCKINFO>") {
                $stock = new StdClass;
                $stock->ticker = null;
                $stock->name = null;
            }elseif (strstr($line, "<TICKER>") !== false) {
                $stock->ticker = (substr($line, 8));
            }elseif (strstr($line, "<SECNAME>") !== false) {
                $stock->name = (substr($line, 9));
            }elseif($line == "</STOCKINFO>") {
                $portfolio->stocks[]= $stock;
            }

        }

        return json_encode($portfolio);
    }


} // END class OFX
