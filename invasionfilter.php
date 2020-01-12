<?php

function invasionfilter( $invasioninfo ) {
    global $solnodelist;
    global $invasionfilterlist;
    foreach( $invasionfilterlist as $v1 ) {
        $r = true;
        foreach( $v1 as $k2 => $v2 ) {
            switch( $k2 ) {
            case 'Node':
                if ( $invasioninfo['Node'] == $v2 ) break;
                if ( ( isset( $solnodelist[$invasioninfo['Node']] ) ) && ( $solnodelist[$invasioninfo['Node']] == $v2 ) ) break;
                $r = false;
                break;
            case 'Item':
            case 'Reward':
                if ( checkinvasionreward( $invasioninfo['AttackerReward'], $v2 ) ) break;
                if ( checkinvasionreward( $invasioninfo['DefenderReward'], $v2 ) ) break;
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

function checkinvasionreward( $rewardinfo, $searchreward ) {
    global $itemtranslatelist;

    foreach( $rewardinfo as $k => $v ) {
        switch( $k ) {
        case 'credits':
            if ( stristr( $searchreward, 'credit' ) === false ) break;
            $cr = 0 + $searchreward;
            if ( $v >= $cr ) return true;
            break;
        case 'items':
            foreach( $v as $v1 ) {
                if ( $v1 == $searchreward ) return true;
                if ( isset( $itemtranslatelist[$v1] ) && ( $v1 == $itemtranslatelist[$v1] ) ) return true;
            }
            break;
        case 'countedItems':
            foreach( $v as $v1 ) {
                foreach( $v1 as $k2 => $v2 ) {
                    switch( $k2 ) {
                    case 'ItemType':
                        if ( $v2 == $searchreward ) return true;
                        if ( isset( $itemtranslatelist[$v2] ) && ( $v2 == $itemtranslatelist[$v2] ) ) return true;
                        break;
                    case 'ItemCount':
                        if ( stristr( $searchreward, 'count' ) === false ) break;
                        $ct = 0 + $searchreward;
                        if ( $v2 >= $ct ) return true;
                        break;
                    }
                }
            }
            break;
        default:
            break;
        }
    }

    return false;
}
                

?>
