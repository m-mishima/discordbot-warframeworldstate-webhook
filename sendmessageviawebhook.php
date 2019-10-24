<?php

function sendmessageviawebhook( $webhookurl, $text ) {

    $ret = true;

    $sendform = array(
        'content' => $text
    );
    $json = json_encode( $sendform );

    $httpheader = array(
        'Content-Type: application/json'
    );

    foreach( $webhookurl as $v ) {
        $curl = curl_init();

        curl_setopt_array( $curl, array(
            CURLOPT_URL => $v,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FAILONERROR => false,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => $httpheader,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json
        ) );

        $res = curl_exec( $curl );
        $httpresultcode = curl_getinfo( $curl, CURLINFO_RESPONSE_CODE );

        curl_close( $curl );

        if ( $res === false ) $ret = false;	// curl自体のエラー
        if ( $httpresultcode != 200 ) $ret = false;	// http 404, 403 etc

    }

    return $ret;
}
