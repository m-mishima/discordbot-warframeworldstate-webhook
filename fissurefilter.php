<?php

function fissurefilter( $voidinfo ) {
    global $regionlist, $missiontypelist, $voidrelictierlist, $solnodelist;
    global $fissurefilterlist;

    $node   = $voidinfo['Node'];
    $region = $voidinfo['Region'];
    if ( ( isset( $solnodelist[$node] ) ) && ( isset( $solnodelist[$node]['region'] ) ) ) {
        // overwrite region value. correct DE's server response.
        $region = $solnodelist[$node]['region'];
    }

    foreach( $fissurefilterlist as $v1 ) {
        $r = true;
        foreach( $v1 as $k2 => $v2 ) {
            switch( $k2 ) {
            case 'Region':
                if ( $region == $v2 ) break;
                if ( ( isset( $regionlist[$region] ) ) && ( $regionlist[$region] == $v2 ) ) break;
                $r = false;
                break;
            case 'MissionType':
                if ( $voidinfo['MissionType'] == $v2 ) break;
                if ( ( isset( $missiontypelist[$voidinfo['MissionType']] ) ) && ( $missiontypelist[$voidinfo['MissionType']] == $v2 ) ) break;
                $r = false;
                break;
            case 'Modifier':
            case 'Tier':
                if ( $voidinfo['Modifier'] == $v2 ) break;
                if ( ( isset( $voidrelictierlist[$voidinfo['Modifier']] ) ) && ( $voidrelictierlist[$voidinfo['Modifier']] == $v2 ) ) break;
                $r = false;
                break;
            case 'Node':
                if ( $node == $v2 ) break;
                if ( ( isset( $solnodelist[$node] ) ) && ( isset( $solnodelist[$node]['name'] ) ) && ( $solnodelist[$node]['name'] == $v2 ) ) break;
                $r = false;
                break;
            case 'action':
                if ( $r == true ) return $v2;
                break;
            default:
                break;
            }
        }
    }
    return false;
}

?>
