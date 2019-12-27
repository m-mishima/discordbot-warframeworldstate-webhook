<?php

include __DIR__ . '/readconfig.php';
include __DIR__ . '/getworldstate.php';
include __DIR__ . '/fissurefilter.php';
include __DIR__ . '/sendmessageviawebhook.php';

$eventids_old = array();	// イベント更新チェック用の、旧イベントIDリスト

function main() {

    global $worldstateurl, $eventids_old;
    global $webhookurl_sortie, $webhookurl_fissure, $webhookurl_nicefissure, $webhookurl_devel;
    $eventids_new = array();

    $sortie_text = '';
    $fissure_text   = '';
    $nicefissure_text   = '';
    $alert_text = '';
    $baro_text = '';

    readconfig();

    $json = getworldstate( $worldstateurl );
    if ( $json === false ) {
        // worldstate get failed
        echo "getworldstate failed" . PHP_EOL;
        return;
    }

    foreach( $json['Sorties'] as $v ) {	// ソーティ
        if ( isset( $v['_id']['$oid'] ) ) {
            $oid = $v['_id']['$oid'];
            if ( !isset( $eventids_old[ $oid ] ) ) {
                $sortie_text .= parse_sortie( $v );
            }
            $eventids_new[ $oid ] = 'checked';
        }
    }

    foreach( $json['ActiveMissions'] as $v ) { // 亀裂ミッション
        if ( isset( $v['_id']['$oid'] ) ) {
            $oid = $v['_id']['$oid'];
            if ( !isset( $eventids_old[ $oid ] ) ) {
                $fissure_text .= parse_voidfissure( $v );

                $action = fissurefilter( $v );
                if ( $action == 'accept' ) {
                    $nicefissure_text .= parse_voidfissure( $v );
                }
            }
            $eventids_new[ $oid ] = 'checked';
        }
    }

    foreach( $json['Alerts'] as $v ) { // アラート
        if ( isset( $v['_id']['$oid'] ) ) {
            $oid = $v['_id']['$oid'];
            if ( !isset( $eventids_old[ $oid ] ) ) {
                $alert_text .= parse_alert( $v );
            }
            $eventids_new[ $oid ] = 'checked';
        }
    }

    foreach( $json['VoidTraders'] as $v ) {	// バロ吉
        if ( isset( $v['_id']['$oid'] ) ) {
            $oid = $v['_id']['$oid'];
            if ( !isset( $eventids_old[ $oid ] ) ) {
                $baro_text .= parse_baro( $v );
            }
            $eventids_new[ $oid ] = 'checked';
        }
    }

    $eventids_old = $eventids_new;

    if ( $sortie_text != '' ) {
        sendmessageviawebhook( $webhookurl_sortie, $sortie_text );
        echo $sortie_text . PHP_EOL;
    }
    if ( $fissure_text != '' ) {
        sendmessageviawebhook( $webhookurl_fissure, $fissure_text );
        echo $fissure_text . PHP_EOL;
    }
    if ( $nicefissure_text != '' ) {
        sendmessageviawebhook( $webhookurl_nicefissure, $nicefissure_text );
        echo $nicefissure_text . PHP_EOL;
    }

    if ( $alert_text != '' ) {
        sendmessageviawebhook( $webhookurl_devel, $alert_text );
        echo $alert_text . PHP_EOL;
    }
    if ( $baro_text != '' ) {
        sendmessageviawebhook( $webhookurl_devel, $baro_text );
        echo $baro_text . PHP_EOL;
    }

}

while(1) {
    
    set_time_limit( 30 );	// phpの実行時間の制限。再設定するとカウンタがリセットされるので、ウォッチドッグとして動作する
    main();

    sleep(60);
}


?>
