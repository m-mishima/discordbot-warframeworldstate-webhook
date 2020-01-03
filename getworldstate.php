<?php

function getworldstate( $url ) {

    $curl = curl_init();

    curl_setopt_array( $curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FAILONERROR => false,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10
    ) );

    $res = curl_exec( $curl );
    $httpresultcode = curl_getinfo( $curl, CURLINFO_RESPONSE_CODE );

    $en = curl_errno( $curl );
    $em = curl_error( $curl );

    curl_close( $curl );

    if ( $res === false ) {	// curl自体のエラー
        echo "curl error code = " . $en . PHP_EOL;
        echo $em . PHP_EOL;
        return false;
    }
    if ( $httpresultcode != 200 ) {	// http 404, 403 etc
        echo "http result = " . $httpresultcode . PHP_EOL;
        echo $res . PHP_EOL;
        return false;
    }
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
    global $missiontypelist, $solnodelist, $itemtranslatelist, $timezone;

    date_default_timezone_set( $timezone );

    $retstr = '';
    $reward = '';

    $datefrom = $alert['Activation']['$date']['$numberLong'];
    $dateend  = $alert['Expiry']['$date']['$numberLong'];

    $s = date('Y-m-d H:i:s', $datefrom / 1000 );
    $e = date('Y-m-d H:i:s', $dateend / 1000 );

    $alerttitle = $alert['MissionInfo']['descText'];
    if ( isset( $itemtranslatelist[$alerttitle] ) ) {
        $alerttitle = $itemtranslatelist[$alerttitle];
    }

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
                $in = $v1;
                if ( isset( $itemtranslatelist[$in] ) ) {
                    $in = $itemtranslatelist[$in];
                }
                $reward .= $in . PHP_EOL;
            }
            break;
        case 'countedItems':
            foreach( $v as $v1 ) {
                $in = ''; $ic = 0;
                foreach( $v1 as $k2 => $v2 ) {
                    switch( $k2 ) {
                    case 'ItemType':
                        $in = $v2;
                        if ( isset( $itemtranslatelist[$in] ) ) {
                            $in = $itemtranslatelist[$in];
                        }
                        break;
                    case 'ItemCount':
                        $ic = $v2;
                        break;
                    }
                }
                if ( ( $in != '' ) && ( $ic != 0 ) ) {
                    $reward .= $ic . 'x ' . $in . PHP_EOL;
                }
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

function parse_baro( $baro ) {
    global $itemtranslatelist, $timezone;

    date_default_timezone_set( $timezone );

    $retstr = '';

    $datefrom  = $baro['Activation']['$date']['$numberLong'];
    $dateend   = $baro['Expiry']['$date']['$numberLong'];
    $character = $baro['Character'];	// Baro'Ki Teel
    $node      = $baro['Node'];

    $s = date('Y-m-d H:i:s', $datefrom / 1000 );
    $e = date('Y-m-d H:i:s', $dateend / 1000 );

    $retstr .= sprintf( "%s %s %s～%s\n", $character, $node, $s, $e );

    if ( isset( $baro['Manifest'] ) ) {
        $itemlist = $baro['Manifest'];
        foreach( $itemlist as $v2 ) {
            $name    = $v2['ItemType'];
            if ( isset( $itemtranslatelist[$name] ) ) {
                $name = $itemtranslatelist[$name];
            }
            $ducats  = $v2['PrimePrice'];
            $credits = $v2['RegularPrice'];

            $retstr .= sprintf( "%dducats %dcredits %s\n", $ducats, $credits, $name );

        }
    }

    return $retstr;
}

?>
