<?php

include __DIR__ . '/readconfig.php';
include __DIR__ . '/getworldstate.php';
include __DIR__ . '/fissurefilter.php';

$eventids_old = array();	// イベント更新チェック用の、旧イベントIDリスト

function main() {

    global $worldstateurl;

    $sortie_text = '';
    $fissure_text   = '';
    $nicefissure_text   = '';
    $alert_text = '';

    readconfig();

    $json = getworldstate( $worldstateurl );
    if ( $json === false ) {
        // worldstate get failed
        echo "getworldstate failed" . PHP_EOL;
        return;
    }

    foreach( $json['Sorties'] as $v ) {	// ソーティ
        if ( isset( $v['_id']['$oid'] ) ) {
            $sortie_text .= parse_sortie( $v );
        }
    }

    foreach( $json['ActiveMissions'] as $v ) { // 亀裂ミッション
        $fissure_text .= parse_voidfissure( $v );

        $action = fissurefilter( $v );
        if ( $action == 'accept' ) {
            $nicefissure_text .= parse_voidfissure( $v );
        }
    }

    foreach( $json['Alerts'] as $v ) {	// アラート
        $alert_text .= parse_alert( $v );
    }

    echo $sortie_text . PHP_EOL;
    echo $fissure_text . PHP_EOL;
    echo $nicefissure_text . PHP_EOL;
    echo $alert_text . PHP_EOL;

}

main();


?>
