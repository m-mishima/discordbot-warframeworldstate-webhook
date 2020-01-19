<?php

include __DIR__ . '/readconfig.php';
include __DIR__ . '/getworldstate.php';
include __DIR__ . '/fissurefilter.php';
include __DIR__ . '/invasionfilter.php';
include __DIR__ . '/sendmessageviawebhook.php';

$eventids_old = array();	// イベント更新チェック用の、旧イベントIDリスト

function main() {

    global $worldstateurl, $eventids_old;
    global $webhookurl_sortie, $webhookurl_fissure, $webhookurl_nicefissure, $webhookurl_sentientship, $webhookurl_devel;
    $eventids_new = array();

    $sortie_text = '';
    $fissure_text   = '';
    $nicefissure_text   = '';
    $alert_text = '';
    $baro_text = '';
    $nightwave_text = '';
    $invasions_text = '';
    $sentientship_text = '';

    readconfig();

    $json = getworldstate( $worldstateurl );
    if ( $json === false ) {
        // worldstate get failed
        echo "getworldstate failed" . PHP_EOL;
        return;
    }

    foreach( $json['Sorties'] as $v ) {	// ソーティ
        if ( isset( $v['_id']['$oid'] ) ) {
            $update_check_hash = 'sortie' . $v['_id']['$oid'];
            if ( !isset( $eventids_old[ $update_check_hash ] ) ) {
                $sortie_text .= parse_sortie( $v );
            }
            $eventids_new[ $update_check_hash ] = 'checked';
        }
    }

    foreach( $json['ActiveMissions'] as $v ) { // 亀裂ミッション
        $update_check_hash = 'fissure' . hash( 'sha256', json_encode( $v ) );
        if ( isset( $v['_id']['$oid'] ) ) {
            if ( !isset( $eventids_old[ $update_check_hash ] ) ) {
                $fissure_text .= parse_voidfissure( $v );

                $action = fissurefilter( $v );
                if ( $action == 'accept' ) {
                    $nicefissure_text .= parse_voidfissure( $v );
                }
            }
            $eventids_new[ $update_check_hash ] = 'checked';
        }
    }

    foreach( $json['Alerts'] as $v ) { // アラート
        $update_check_hash = 'alert' . hash( 'sha256', json_encode( $v ) );
        if ( isset( $v['_id']['$oid'] ) ) {
            if ( !isset( $eventids_old[ $update_check_hash ] ) ) {
                $alert_text .= parse_alert( $v );
            }
            $eventids_new[ $update_check_hash ] = 'checked';
        }
    }

    foreach( $json['VoidTraders'] as $v ) {	// バロ吉
        $update_check_hash = 'baro' . hash( 'sha256', json_encode( $v ) );
        if ( isset( $v['_id']['$oid'] ) ) {
            if ( !isset( $eventids_old[ $update_check_hash ] ) ) {
                $baro_text .= parse_baro( $v );
            }
            $eventids_new[ $update_check_hash ] = 'checked';
        }
    }

    if ( isset( $json['SeasonInfo'] ) ) {   // NightWave
        $update_check_hash = 'nightwave' . hash( 'sha256', json_encode( $json['SeasonInfo'] ) );
        if ( !isset( $eventids_old[ $update_check_hash ] ) ) {
            $nightwave_text .= parse_nightwave( $json['SeasonInfo'] );
        }
        $eventids_new[ $update_check_hash ] = 'checked';
    }

    foreach( $json['Invasions'] as $v ) {	// 侵略
        if ( isset( $v['_id']['$oid'] ) ) {
            $update_check_hash = 'invasion' . $v['_id']['$oid'];
            if ( !isset( $eventids_old[ $update_check_hash ] ) ) {
                // 侵略ミッションは、フィルター条件を満たしたもののみ表示
                if ( invasionfilter( $v ) == 'accept' ) {
                    $invasions_text .= parse_invasion( $v );
                }
            }
            $eventids_new[ $update_check_hash ] = 'checked';
        }
    }

    if ( isset( $json['Tmp'] ) ) {   // sentient ship
        $update_check_hash = 'sentientship' . hash( 'sha256', json_encode( $json['Tmp'] ) );
        if ( !isset( $eventids_old[ $update_check_hash ] ) ) {
            $sentientship_text .= parse_sentientship( $json['Tmp'] );
        }
        $eventids_new[ $update_check_hash ] = 'checked';
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
    if ( $sentientship_text != '' ) {
        sendmessageviawebhook( $webhookurl_sentientship, $sentientship_text );
        echo $sentientship_text . PHP_EOL;
    }

    if ( $alert_text != '' ) {
        sendmessageviawebhook( $webhookurl_devel, $alert_text );
        echo $alert_text . PHP_EOL;
    }
    if ( $baro_text != '' ) {
        sendmessageviawebhook( $webhookurl_devel, $baro_text );
        echo $baro_text . PHP_EOL;
    }
    if ( $nightwave_text != '' ) {
        sendmessageviawebhook( $webhookurl_devel, $nightwave_text );
        echo $nightwave_text . PHP_EOL;
    }
    if ( $invasions_text != '' ) {
        sendmessageviawebhook( $webhookurl_devel, $invasions_text );
        echo $invasions_text . PHP_EOL;
    }

}

while(1) {
    
    set_time_limit( 30 );	// phpの実行時間の制限。再設定するとカウンタがリセットされるので、ウォッチドッグとして動作する
    main();

    sleep(60);
}


?>
