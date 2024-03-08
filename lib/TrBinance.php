<?php

namespace Tulpar;

use Illuminate\Support\Facades\Http;

class TrBinance extends Tulpar
{
    const HOST='https://www.trbinance.com/v1/';
    const APIKEY='e7A5A41A69CdfA48Df940762BA3eC2abtLCB6d0NoC0cQxf2FQwBK3QHiZ8bAuZb';
    const APISECRET='f628d092c62F23582dB44aEe2050f2dBtaj4PArb8b8LT7aeY8Ljsh46rwn66jnN';
    const TIMEOFFSET = 0;
    public  $d = "";
    public static function tradingPairs($quoteAsset, $offset=0, $limit=1000){
        $url = self::HOST . 'market/trading-pairs?quoteAsset='.$quoteAsset.'&offset='.$offset.'&limit='.$limit;
        $response = Http::get($url);
        $result = $response->json();
        if(isset($result['data']) && isset($result['data']['list'])){
            self::_ReturnSucces('Başarılı', '', $result['data']['list']) ;
        }
        return self::_ReturnError('Başarısız');
    }
    public static function order($symbol, $side, $type, $quantity=0, $price=0, $flags = []){

        /**
         * side 0 Buy
         * side 1 Sell
         * ***************
         * type 1 quantity, price
         * type 2 quantity (sell) or quoteOrderQty (buy)
         * type 4 quantity, price, stopPrice
         * type 6 quantity, price, stopPrice
         **/

        $result['status'] = 0;
        $result['message'] = '';
        $method = 'POST';
        if (gettype($price) !== "string") {
            $price = number_format($price, 8, '.', '');
        }

        if (is_numeric($quantity) === false) {
            $result['message'] =  "warning: quantity expected numeric got " . gettype($quantity) . PHP_EOL;
        }

        if (is_string($price) === false) {
            $result['message'] =  "warning: price expected string got " . gettype($price) . PHP_EOL;
        }

        $params = [
            "symbol" => $symbol,
        ];

        if($side=='BUY'){
            $params["side"] = 0;
        } elseif($side=='SELL'){
            $params["side"] = 1;
        } elseif($side=='ALL'){
            unset($params["side"]);
        }

        if ( $type === "LIMIT" ) {
            $params["price"] = $price;
            $params["type"] = 1;
            $params['quantity'] = $quantity;
        }elseif ( $type === "MARKET" ) {
            $params["type"] = 2;
            if($side=='SELL'){
                $params['quantity'] = $quantity;
            } elseif($side='ALL'){
                $params['quoteOrderQty'] = $quantity;
            }
        } elseif ( $type === "OPEN" ){
            $params["type"] = 1;
            unset($params["symbol"]);
            if(isset($flags['startTime']) && !empty($flags['startTime'])) $params["startTime"] = $flags['startTime'];
            if(isset($flags['endTime']) && !empty($flags['endTime'])) $params["endTime"] = $flags['endTime'];
            $method = 'GET';
        } elseif ( $type === "COMPLATED" ){
            $params["type"] = 2;
            unset($params["symbol"]);
            if(isset($flags['startTime']) && !empty($flags['startTime'])) $params["startTime"] = $flags['startTime'];
            if(isset($flags['endTime']) && !empty($flags['endTime'])) $params["endTime"] = $flags['endTime'];
            $method = 'GET';
        } elseif ( $type === "ALL" ){
            $params["type"] = -1;
            unset($params["symbol"]);
            if(isset($flags['startTime']) && !empty($flags['startTime'])) $params["startTime"] = $flags['startTime'];
            if(isset($flags['endTime']) && !empty($flags['endTime'])) $params["endTime"] = $flags['endTime'];
            $method = 'GET';
        }
        if(isset($flags['limit']) && !empty($flags['limit'])){
            $params['limit'] = max(11, (int)$flags['limit']);
        }
        if(isset($flags["direct"]) && !empty($flags['direct'])){
            $params["direct"] = $flags["direct"];
        }

        $url= "open/v1/orders";
        $request =  self::httpRequest($url, $params, $method);

        if($request['status']){
            if(isset($request['response']) && isset($request['response']['data']) ){
                if(isset($request['response']['data']['list'])){
                    foreach ($request['response']['data']['list'] as $list_key=>$item ){
                        $date = new \DateTime();
                        $date->setTimeZone(new \DateTimeZone('Europe/Istanbul'));
                        $date->setTimestamp($item['createTime']/1000);
                        $item['trade_at'] = $date->format('Y-m-d H:i:s');
                        $request['response']['data']['list'][$list_key] = $item;
                    }
                }
            }
        }

        return $request;
    }
    private static function httpRequest(string $url, array $params = [], $method = "GET"){
        try {
            $result = ['status'=>0, 'message'=>'', 'response'=>[], 'debugs'=>[], 'response'=>[]];
            if (function_exists('curl_init') === false) {
                throw new \Exception("Sorry cURL is not installed!");
            }
            $ts = (microtime(true) * 1000) + self::TIMEOFFSET;
            $params['timestamp'] = number_format($ts, 0, '.', '');

            $curl = curl_init();
            $headers = [
                'Cache-Control: no-cache',
                'X-MBX-APIKEY: ' . self::APIKEY,
            ];
            /**
             * $endpoint = $base . $url;
             * $params['signature'] = $signature; // signature needs to be inside BODY
             * $query = http_build_query($params, '', '&'); // rebuilding query
             */
            $query = http_build_query($params, '', '&');
            $signature = hash_hmac('sha256', $query, SELF::APISECRET);

            if($method=='GET'){
                $url = self::HOST . $url . '?' . $query.'&signature=' . $signature ;
            } elseif($method=='POST'){
                $url = self::HOST . $url  ;
                $params['signature'] = $signature;
                $query = http_build_query($params, '', '&'); // rebuilding query

                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
            }


            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_VERBOSE, self::isDebug());
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_HEADER, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 60);
            curl_setopt($curl, CURLOPT_FRESH_CONNECT, TRUE);
            // set user defined curl opts last for overriding

            $result['debugs']['Method'] = $method;
            $result['debugs']['Url'] = $url;
            $result['debugs']['Params'] = print_r($params, 1);

            if(self::isGo()){
                $output = curl_exec($curl);
                $result['debugs']['Response'] = $output;
            } else{
                $result['message'] = 'Trbinance Go modu false';
                $result['response'] = [];
                return $result;
            }

            // Check if any error occurred
            if (curl_errno($curl) > 0) {
                $result['debugs']['Curl Error'] = curl_error($curl);
                // throw new \Exception('Curl error: ' . curl_error($curl));
            }

            $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
            $header = substr($output, 0, $header_size);
            $output = substr($output, $header_size);

            curl_close($curl);
            $json = json_decode($output, true);

            $result['status'] = 1;
            $result['message'] = 'Api bağlantısı başarılı';
            $result['response'] = $json;

            /*$this->lastRequest = [
                'url' => $url,
                'method' => $method,
                'params' => $params,
                'header' => $header
            ];*/
        } catch (\Exception $ex){
            $result['debugs']['Curl Error']['Exception'] = $ex->getMessage();
            $result = [
                'status'=>0,
                'message'=>'Api bağlantısı hatalı: '.$ex->getMessage(),
                'response'=>[]
            ];
        }
        return $result;
    }
    static function isGo(){
        return true;
    }
    static function isDebug(){
        return true;
    }
}
