<?php

function fissurefilter( $voidinfo ) {
    global $regionlist, $missiontypelist, $voidrelictierlist, $solnodelist;
    global $fissurefilterlist;
    foreach( $fissurefilterlist as $v1 ) {
        $r = true;
        foreach( $v1 as $k2 => $v2 ) {
            switch( $k2 ) {
            case 'Region':
                if ( $voidinfo['Region'] == $v2 ) break;
                if ( ( isset( $regionlist[$voidinfo['Region']] ) ) && ( $regionlist[$voidinfo['Region']] == $v2 ) ) break;
                $r = false;
                break;
            case 'MissionType':
                if ( $voidinfo['MissionType'] == $v2 ) break;
                if ( ( isset( $missiontypelist[$voidinfo['MissionType']] ) ) && ( $missiontypelist[$voidinfo['MissionType']] == $v2 ) ) break;
                $r = false;
                break;
            case 'Modifier':
                if ( $voidinfo['Modifier'] == $v2 ) break;
                if ( ( isset( $voidrelictierlist[$voidinfo['Modifier']] ) ) && ( $voidrelictierlist[$voidinfo['Modifier']] == $v2 ) ) break;
                $r = false;
                break;
            case 'Node':
                if ( $voidinfo['Node'] == $v2 ) break;
                if ( ( isset( $solnodelist[$voidinfo['Node']] ) ) && ( $solnodelist[$voidinfo['Node']] == $v2 ) ) break;
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
