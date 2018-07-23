<?php
/*
 * Клас предназначен для получения и обработки информации со стороннего ресурса
 */
class URL {
    private $ch = null;
    public function __construct() {
        $this->ch = curl_init();
        //$cookie = tempnam ("/tmp", "CURLCOOKIE");
        curl_setopt( $this->ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1" );
        curl_setopt( $this->ch, CURLOPT_COOKIEJAR, null/*$cookie*/ );
        curl_setopt( $this->ch, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt( $this->ch, CURLOPT_ENCODING, "" );
        curl_setopt( $this->ch, CURLOPT_HEADER, 0 );
        curl_setopt( $this->ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $this->ch, CURLOPT_AUTOREFERER, true );
        curl_setopt( $this->ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
        curl_setopt( $this->ch, CURLOPT_MAXREDIRS, 10 );
        curl_setopt( $this->ch, CURLINFO_HEADER_OUT, false );
        
    }
    
    public function __destruct() {
        curl_close ( $this->ch );
    }
    
    public function setOption($const, $value) {
        curl_setopt( $this->ch, $const, $value );
    }
    
    public function getURLContent( $url,  $javascript_loop = 0, $timeout = 5 ) {
        $url = str_replace( "&amp;", "&", urldecode(trim($url)) );

        curl_setopt( $this->ch, CURLOPT_URL, $url );
        curl_setopt( $this->ch, CURLOPT_CONNECTTIMEOUT, $timeout );
        curl_setopt( $this->ch, CURLOPT_TIMEOUT, $timeout );
        $content = curl_exec( $this->ch );
        $response = curl_getinfo( $this->ch );
        $cookies = curl_getinfo( $this->ch, CURLINFO_COOKIELIST );

        if ($response['http_code'] == 301 || $response['http_code'] == 302) {
            ini_set("user_agent", "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1");

            if ( $headers = get_headers($response['url']) ) {
                foreach( $headers as $value ) {
                    if ( substr( strtolower($value), 0, 9 ) == "location:" )
                        return get_url( trim( substr( $value, 9, strlen($value) ) ) );
                }
            }
        }

        if (    ( preg_match("/>[[:space:]]+window\.location\.replace\('(.*)'\)/i", $content, $value) || preg_match("/>[[:space:]]+window\.location\=\"(.*)\"/i", $content, $value) ) && $javascript_loop < 5) {
            return get_url( $value[1], $javascript_loop+1 );
        } else {
            return array( $content, $response, $cookies );
        }
    }
}

//function get_fcontent( $url,  $javascript_loop = 0, $timeout = 5 ) {
//    $url = str_replace( "&amp;", "&", urldecode(trim($url)) );
//
//    //$cookie = tempnam ("/tmp", "CURLCOOKIE");
//    $this->ch = curl_init();
//    curl_setopt( $this->ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1" );
//    curl_setopt( $this->ch, CURLOPT_URL, $url );
//    curl_setopt( $this->ch, CURLOPT_COOKIEJAR, null/*$cookie*/ );
//    curl_setopt( $this->ch, CURLOPT_FOLLOWLOCATION, true );
//    curl_setopt( $this->ch, CURLOPT_ENCODING, "" );
//    curl_setopt( $this->ch, CURLOPT_RETURNTRANSFER, true );
//    curl_setopt( $this->ch, CURLOPT_AUTOREFERER, true );
//    curl_setopt( $this->ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
//    curl_setopt( $this->ch, CURLOPT_CONNECTTIMEOUT, $timeout );
//    curl_setopt( $this->ch, CURLOPT_TIMEOUT, $timeout );
//    curl_setopt( $this->ch, CURLOPT_MAXREDIRS, 10 );
//    $content = curl_exec( $this->ch );
//    $response = curl_getinfo( $this->ch );
//    curl_close ( $this->ch );
//
//    if ($response['http_code'] == 301 || $response['http_code'] == 302) {
//        ini_set("user_agent", "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1");
//
//        if ( $headers = get_headers($response['url']) ) {
//            foreach( $headers as $value ) {
//                if ( substr( strtolower($value), 0, 9 ) == "location:" )
//                    return get_url( trim( substr( $value, 9, strlen($value) ) ) );
//            }
//        }
//    }
//
//    if (    ( preg_match("/>[[:space:]]+window\.location\.replace\('(.*)'\)/i", $content, $value) || preg_match("/>[[:space:]]+window\.location\=\"(.*)\"/i", $content, $value) ) && $javascript_loop < 5) {
//        return get_url( $value[1], $javascript_loop+1 );
//    } else {
//        return array( $content, $response );
//    }
//}
?>