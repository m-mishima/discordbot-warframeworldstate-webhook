<?php

function getworldstate( $url ) {

    $curl = curl_init();

    curl_setopt_array( $curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FAILONERROR => false
    ) );

    $res = curl_exec( $curl );
    $httpresultcode = curl_getinfo( $curl, CURLINFO_RESPONSE_CODE );

    curl_close( $curl );

    if ( $res === false ) return false;	// curl自体のエラー
    if ( $httpresultcode != 200 ) return false;	// http 404, 403 etc

    $arr = json_decode( $res, true );

    return $arr;
}


function parse_voidfissure( $activemission ) {
    global $regionlist, $solnodelist, $missiontypelist, $voidrelictierlist, $timezone;

    date_default_timezone_set( $timezone );

    $retstr = '';

    $region   = $activemission['Region'];
    $datefrom = $activemission['Activation']['$date']['$numberLong'];
    $dateend  = $activemission['Expiry']['$date']['$numberLong'];
    $node     = $activemission['Node'];
    $mission  = $activemission['MissionType'];
    $tier     = $activemission['Modifier'];

    if ( !isset( $voidrelictierlist[ $tier ] ) ) {	// void T1～T4 以外
        return $retstr;
    }

    $s = date('H:i:s', $datefrom / 1000 );
    $e = date('H:i:s', $dateend / 1000 );

    if ( isset( $regionlist[ $region ] ) ) {
        $region = $regionlist[ $region ];
    }

    if ( isset( $missiontypelist[ $mission ] ) ) {
        $mission = $missiontypelist[ $mission ];
    }

    if ( isset( $solnodelist[ $node ] ) ) {
        $node = $solnodelist[ $node ];
    }

    $retstr .= sprintf( "%s(%s) %s %s %s～%s\n", $node, $region, $mission, $voidrelictierlist[ $tier ], $s, $e );

    return $retstr;
}


function parse_sortie( $sortie ) {
    global $sortiebosslist, $sortiemodifierlist, $missiontypelist, $solnodelist, $timezone;

    date_default_timezone_set( $timezone );

    $retstr = '';

    $boss     = $sortie['Boss'];
    $datefrom = $sortie['Activation']['$date']['$numberLong'];
    $dateend  = $sortie['Expiry']['$date']['$numberLong'];

    $s = date('Y-m-d H:i:s', $datefrom / 1000 );
    $e = date('Y-m-d H:i:s', $dateend / 1000 );

    if ( isset( $sortiebosslist[ $boss ] ) ) {
        $boss = $sortiebosslist[ $boss ];
    }

    $retstr .= sprintf( "%s %s～%s\n", $boss, $s, $e );

    $variant = $sortie['Variants'];
    foreach( $variant as $v2 ) {
        $node     = $v2['node'];
        $mission  = $v2['missionType'];
        $modifier = $v2['modifierType'];

        if ( isset( $missiontypelist[ $mission ] ) ) {
            $mission = $missiontypelist[ $mission ];
        }

        if ( isset( $solnodelist[ $node ] ) ) {
            $node = $solnodelist[ $node ];
        }

        if ( isset( $sortiemodifierlist[ $modifier ] ) ) {
            $modifier = $sortiemodifierlist[ $modifier ];
        }

        $retstr .= sprintf( "%s %s %s\n", $node, $mission, $modifier );

    }

    return $retstr;
}

function parse_alert( $alert ) {
    global $missiontypelist, $solnodelist, $timezone;

    date_default_timezone_set( $timezone );

    $retstr = '';
    $reward = '';

    $datefrom = $alert['Activation']['$date']['$numberLong'];
    $dateend  = $alert['Expiry']['$date']['$numberLong'];

    $s = date('Y-m-d H:i:s', $datefrom / 1000 );
    $e = date('Y-m-d H:i:s', $dateend / 1000 );

    $alerttitle = $alert['MissionInfo']['descText'];

    $retstr .= sprintf( "%s %s～%s\n", $alerttitle, $s, $e );

    $node     = $alert['MissionInfo']['location'];
    $mission  = $alert['MissionInfo']['missionType'];

    foreach( $alert['MissionInfo']['missionReward'] as $k => $v ) {
        switch( $k ) {
        case 'credits':
            $reward .= sprintf( "%d Credit\n", $v );
            break;
        case 'items':
            foreach( $v as $v1 ) {
                $reward .= $v1 . PHP_EOL;
            }
            break;
        default:
            break;
        }
    }

    if ( isset( $missiontypelist[ $mission ] ) ) {
        $mission = $missiontypelist[ $mission ];
    }

    if ( isset( $solnodelist[ $node ] ) ) {
        $node = $solnodelist[ $node ];
    }

    $retstr .= sprintf( "%s %s\n", $node, $mission );
    $retstr .= $reward . PHP_EOL;

    return $retstr;
}

?>
