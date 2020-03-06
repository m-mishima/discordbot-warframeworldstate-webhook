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

    if ( ( isset( $solnodelist[$node] ) ) && ( isset( $solnodelist[$node]['region'] ) ) ) {
        // overwrite region value. correct DE's server response.
        $region = $solnodelist[$node]['region'];
    }
    if ( isset( $regionlist[ $region ] ) ) {
        $region = $regionlist[ $region ];
    }

    if ( isset( $missiontypelist[ $mission ] ) ) {
        $mission = $missiontypelist[ $mission ];
    }

    if ( ( isset( $solnodelist[ $node ] ) ) && ( isset( $solnodelist[ $node ]['name'] ) ) ) {
        $node = $solnodelist[ $node ]['name'];
    }

    $retstr .= sprintf( "%s(%s) %s %s %s～%s\n", $node, $region, $mission, $voidrelictierlist[ $tier ], $s, $e );

    return $retstr;
}


function parse_sortie( $sortie ) {
    global $sortiebosslist, $sortiemodifierlist, $missiontypelist, $solnodelist, $regionlist, $timezone;

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

        $nodename = '';
        if ( ( isset( $solnodelist[ $node ] ) ) && ( isset( $solnodelist[ $node ]['name'] ) ) ) {
            $nodename = $solnodelist[ $node ]['name'];
        } else {
            $nodename = $node;
        }
        if ( ( isset( $solnodelist[ $node ] ) ) && ( isset( $solnodelist[ $node ]['region'] ) ) ) {
            $region = $solnodelist[ $node ]['region'];
            if ( isset( $regionlist[ $region ] ) ) {
                $region = $regionlist[ $region ];
                $nodename .= '(' . $region . ')';
            }
        }

        if ( isset( $sortiemodifierlist[ $modifier ] ) ) {
            $modifier = $sortiemodifierlist[ $modifier ];
        }

        $retstr .= sprintf( "%s %s %s\n", $nodename, $mission, $modifier );

    }

    return $retstr;
}

function create_sortiehash( $sortie ) {
    $boss     = $sortie['Boss'];
    $datefrom = $sortie['Activation']['$date']['$numberLong'];
    $dateend  = $sortie['Expiry']['$date']['$numberLong'];

    $var = array();

    $variant = $sortie['Variants'];
    foreach( $variant as $v2 ) {
        $node     = $v2['node'];
        $mission  = $v2['missionType'];
        $modifier = $v2['modifierType'];

        $var[] = array(
            'node'     => $node,
            'mission'  => $mission,
            'modifier' => $modifier
        );

    }

    $arr = array(
        'boss'     => $boss,
        'datefrom' => $datefrom,
        'dateend'  => $dateend,
        'variant'  => $var
    );

    return hash( 'sha256', json_encode( $arr ) );

}

function parse_alert( $alert ) {
    global $missiontypelist, $solnodelist, $itemtranslatelist, $alertweaponmodifierlist, $timezone;

    date_default_timezone_set( $timezone );

    $retstr = '';
    $reward = '';
    $regulation = '';

    $datefrom = $alert['Activation']['$date']['$numberLong'];
    $dateend  = $alert['Expiry']['$date']['$numberLong'];

    $s = date('Y-m-d H:i:s', $datefrom / 1000 );
    $e = date('Y-m-d H:i:s', $dateend / 1000 );

    $alerttitle = $alert['MissionInfo']['descText'];
    if ( isset( $itemtranslatelist[$alerttitle] ) ) {
        $alerttitle = $itemtranslatelist[$alerttitle];
    }

    $retstr .= sprintf( "%s %s～%s\n", $alerttitle, $s, $e );

    $node          = $alert['MissionInfo']['location'];
    $mission       = $alert['MissionInfo']['missionType'];
    $enemylevelmin = $alert['MissionInfo']['minEnemyLevel'];
    $enemylevelmax = $alert['MissionInfo']['maxEnemyLevel'];
    $wave          = $alert['MissionInfo']['maxWaveNum'];
    if ( isset( $alert['MissionInfo']['exclusiveWeapon'] ) ) {
        $regulation = $alert['MissionInfo']['exclusiveWeapon'];
        if ( isset( $alertweaponmodifierlist[ $regulation ] ) ) {
            $regulation = "*" . $alertweaponmodifierlist[ $regulation ];
        }
    }

    $rewardlist = parse_reward( $alert['MissionInfo']['missionReward'] );
    foreach( $rewardlist as $v ) {
        if ( $reward != '' ) $reward .= ', ';
        $reward .= $v;
    }

    if ( isset( $missiontypelist[ $mission ] ) ) {
        $mission = $missiontypelist[ $mission ];
    }

    if ( ( isset( $solnodelist[ $node ] ) ) && ( isset( $solnodelist[ $node ]['name'] ) ) ) {
        $node = $solnodelist[ $node ]['name'];
    }

    $retstr .= sprintf( "%s %s (Lv%d-%d) %dwave %s\n", $node, $mission, $enemylevelmin, $enemylevelmax, $wave, $regulation );
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

            $retstr .= sprintf( "%sducats %scredits %s\n", number_format( $ducats ), number_format( $credits ), $name );

        }
    }

    return $retstr;
}

function parse_nightwave( $nightwave ) {
    global $nightwavetranslatelist, $timezone;

    date_default_timezone_set( $timezone );

    $retstr = '';

    $datefrom  = $nightwave['Activation']['$date']['$numberLong']; // nightwave season begin
    $dateend   = $nightwave['Expiry']['$date']['$numberLong'];     // nightwave season end
    $tag       = $nightwave['AffiliationTag']; // ???
    $season    = $nightwave['Season']; // season
    $phase     = $nightwave['Phase'];  // phase
    $params    = $nightwave['Params']; // params

    $s = date('Y-m-d H:i:s', $datefrom / 1000 );
    $e = date('Y-m-d H:i:s', $dateend / 1000 );

    $retstr .= sprintf( "nightwave season%d phase%d %s～%s\n", $season, $phase, $s, $e );

    if ( isset( $nightwave['ActiveChallenges'] ) ) {
        $challengelist = $nightwave['ActiveChallenges'];
        foreach( $challengelist as $v2 ) {
            $oid           = $v2['_id']['$oid'];
            $dailyflg      = false;
            if ( isset( $v2['Daily'] ) ) $dailyflg = $v2['Daily'];
            $challengefrom = $v2['Activation']['$date']['$numberLong'];
            $challengeend  = $v2['Expiry']['$date']['$numberLong'];
            $challenge     = $v2['Challenge'];
            if ( isset( $nightwavetranslatelist[ $challenge ] ) ) {
                $challenge = $nightwavetranslatelist[ $challenge ];
            }

            $retstr .= sprintf( "%s %s\n", ( $dailyflg ? '日' : '週' ), $challenge );

        }
    }

    return $retstr;
}

function parse_invasion( $invasion ) {
    global $solnodelist, $timezone;

    date_default_timezone_set( $timezone );

    $retstr = '';

    $oid            = $invasion['_id']['$oid'];
    $attackfaction  = $invasion['Faction']; // 攻撃側勢力 FC_CORPUS, FC_GRINEER
    $defencefaction = $invasion['DefenderFaction']; // 防御側勢力
    $node           = $invasion['Node'];
    $count          = $invasion['Count']; // 進行度？
    $goal           = $invasion['Goal'];
    $completed      = $invasion['Completed']; // true, false
    $chainid        = $invasion['ChainID']['$oid'];

    $datefrom  = $invasion['Activation']['$date']['$numberLong'];

    $s = date('Y-m-d H:i:s', $datefrom / 1000 );

    if ( ( isset( $solnodelist[ $node ] ) ) && ( isset( $solnodelist[ $node ]['name'] ) ) ) {
        $node = $solnodelist[ $node ]['name'];
    }

    $retstr .= sprintf( "侵略 %s %s～\n", $node, $s );

    $attackrewardlist  = parse_reward( $invasion['AttackerReward'] );
    $defencerewardlist = parse_reward( $invasion['DefenderReward'] );

    $attackrewardstr = '';
    foreach( $attackrewardlist as $v ) {
        if ( $attackrewardstr != '' ) $attackrewardstr .= ', ';
        $attackrewardstr .= $v;
    }

    $defencerewardstr = '';
    foreach( $defencerewardlist as $v ) {
        if ( $defencerewardstr != '' ) $defencerewardstr .= ', ';
        $defencerewardstr .= $v;
    }

    if ( $attackrewardstr == '' ) {
        // if infestation mission, no reward from infedted faction
        $retstr .= sprintf( "[ %s ]\n", $defencerewardstr );
    } else {
        $retstr .= sprintf( "[ %s ] vs [ %s ]\n", $attackrewardstr, $defencerewardstr );
    }

    return $retstr;
}

function parse_acolyte( $acolyte ) {
    global $acolytelist, $solnodelist, $regionlist, $missiontypelist, $timezone;

    date_default_timezone_set( $timezone );

    $retstr = '';

    $oid                    = $acolyte['_id']['$oid'];
    $agenttype              = $acolyte['AgentType']; // /Lotus/Types/Enemies/Acolytes/ControlAcolyteAgent
    $loctag                 = $acolyte['LocTag']; // /Lotus/Language/Game/ControlAcolyte
    $icon                   = $acolyte['Icon']; // /Lotus/Interface/Icons/Npcs/Special/ControlAcolyte.png
    $rank                   = $acolyte['Rank']; // 30
    $healthpercent          = $acolyte['HealthPercent']; // 0.931803225
    $fleedamage             = $acolyte['FleeDamage']; // 50000
    $lastdiscoveredlocation = $acolyte['LastDiscoveredLocation']; // SolNode62
    $discovered             = $acolyte['Discovered']; // false
    $useticketing           = $acolyte['UseTicketing']; // false

    $region = '';
    if ( isset( $acolyte['Region'] ) ) {
        $region = $acolyte['Region'] + 1;
    }
    if ( ( isset( $solnodelist[$lastdiscoveredlocation] ) ) && ( isset( $solnodelist[$lastdiscoveredlocation]['region'] ) ) ) {
        // overwrite region value. correct DE's server response.
        $region = $solnodelist[$lastdiscoveredlocation]['region'];
    }
    if ( isset( $regionlist[ $region ] ) ) {
        $region = $regionlist[ $region ];
    }

    $mission = '';
    if ( ( isset( $solnodelist[$lastdiscoveredlocation] ) ) && ( isset( $solnodelist[$lastdiscoveredlocation]['mission'] ) ) ) {
        $mission = $solnodelist[$lastdiscoveredlocation]['mission'];
    }
    if ( isset( $missiontypelist[ $mission ] ) ) {
        $mission = $missiontypelist[ $mission ];
    }

    $lastdiscoveredtime = $acolyte['LastDiscoveredTime']['$date']['$numberLong'];

    $lastdiscoveredtime = date('Y-m-d H:i:s', $lastdiscoveredtime / 1000 );

    if ( ( isset( $solnodelist[ $lastdiscoveredlocation ] ) ) && ( isset( $solnodelist[ $lastdiscoveredlocation ]['name'] ) ) ) {
        $lastdiscoveredlocation = $solnodelist[ $lastdiscoveredlocation ]['name'];
    }

    $name = "";
    $mods = "";
    if ( isset( $acolytelist[ $agenttype ] ) ) {
        $acolyteinfo = $acolytelist[ $agenttype ];
        $name = $acolyteinfo['name'];
        $mods = $acolyteinfo['mods'];
    }

    if ( $name != '' ) {
        $titleline = 'Acolyte ' . $name;
        if ( $discovered === false ) {
            $retstr .= $titleline . ' is lost' . PHP_EOL;
        } else {
            $retstr .= $titleline . sprintf( " (残%s%%)", number_format( $healthpercent * 100, 2 ) ) . PHP_EOL;
            $modstr = '';
            foreach( $mods as $k => $v ) {
                if ( $modstr != '' ) $modstr .= ', ';
                $modstr .= $k . ' ' . $v . '%';
            }
            $retstr .= $modstr . PHP_EOL;
            $retstr .= $lastdiscoveredlocation;
            if ( $region != '' ) {
                $retstr .= '(' . $region . ')';
            }
            if ( $mission != '' ) {
                $retstr .= ' ' . $mission;
            }
            $retstr .= ' ' . $lastdiscoveredtime . '～' . PHP_EOL;
        }
    }

    return $retstr;
}

function create_acolytehash( $acolyte ) {
    $oid                    = $acolyte['_id']['$oid'];
    $agenttype              = $acolyte['AgentType'];
    $lastdiscoveredlocation = $acolyte['LastDiscoveredLocation'];
    $lastdiscoveredtime     = $acolyte['LastDiscoveredTime']['$date']['$numberLong'];
    $discovered             = $acolyte['Discovered'];

    $arr = array(
        'oid' => $oid,
        'agenttype' => $agenttype,
        'lastdiscoveredlocation' => $lastdiscoveredlocation,
        'lastdiscoveredtime' => $lastdiscoveredtime,
        'discovered' => $discovered
    );

    return hash( 'sha256', json_encode( $arr ) );
}

function parse_goals( $goals ) {

    $retstr = '';

    // 各イベント毎にデータ構造が違うので、別処理
    $desc = $goals['Desc'];
    switch( $desc ) {
    case '/Lotus/Language/Menu/CorpusRazorbackProject':
        $retstr = parse_goals_razorback( $goals );
        break;
    case '/Lotus/Language/G1Quests/FomorianRevengeBattleName':
        $retstr = parse_goals_fomorian( $goals );
        break;
    case '/Lotus/Language/G1Quests/HeatFissuresEventName':
        $retstr = parse_goals_thermia( $goals );
        break;
    case '/Lotus/Language/GameModes/RecurringGhoulAlert':
        $retstr = parse_goals_ghoul( $goals );
        break;
    default:
        $retstr = parse_goals_unknown( $goals );
        break;
    }

    return $retstr;
}

function create_goalshash( $goals ) {

    $oid           = $goals['_id']['$oid'];
    $activation    = $goals['Activation']['$date']['$numberLong'];
    $expiry        = $goals['Expiry']['$date']['$numberLong'];
    $desc          = $goals['Desc']; // /Lotus/Language/Menu/CorpusRazorbackProject

    $arr = array(
        'oid'           => $oid,
        'activation'    => $activation,
        'expiry'        => $expiry,
        'desc'          => $desc
    );

    return hash( 'sha256', json_encode( $arr ) );
}

function parse_goals_razorback( $goals ) {
    global $solnodelist, $regionlist, $itemtranslatelist, $timezone;

    date_default_timezone_set( $timezone );

    $retstr = '';

    $oid           = $goals['_id']['$oid']; // 5e4d5221e8fa3c4737fa543c
    $fomorian      = $goals['Fomorian']; // true
    $activation    = $goals['Activation']['$date']['$numberLong'];
    $expiry        = $goals['Expiry']['$date']['$numberLong'];
    $countnum      = $goals['Count']; // 0
    $goal          = $goals['Goal']; // 3
    $healthpct     = $goals['HealthPct']; // 0.9345284
    $victimnode    = $goals['VictimNode']; // ErisHUB
    $personal      = $goals['Personal']; // true
    $best          = $goals['Best']; // false
    $scorevar      = $goals['ScoreVar']; // ""
    $scoremaxtag   = $goals['ScoreMaxTag']; // ""
    $scoretagblock = $goals['ScoreTagBlocksGuildTierChanges']; // false
    $success       = $goals['Success']; // 0
    $node          = $goals['Node']; // EventNode10
    $faction       = $goals['Faction']; // FC_CORPUS
    $desc          = $goals['Desc']; // /Lotus/Language/Menu/CorpusRazorbackProject
    $icon          = $goals['Icon']; // /Lotus/Interface/Icons/Npcs/Corpus/ArmoredJackal.png
    $regiondrops   = $goals['RegionDrops']; // array();
    $archwingdrops = $goals['ArchwingDrops']; // array( '/Lotus/Types/Items/MiscItems/RazorbackCipherPartPickup' );
    $scoreloctag   = $goals['ScoreLocTag']; // /Lotus/Language/Menu/RazorbackArmadaScoreHint
    $tag           = $goals['Tag']; // FriendlyFireTacAlert

    $missioninfo = $goals['MissionInfo'];
    $mission_type             = $missioninfo['missionType']; // MT_ASSASSINATION
    $mission_faction          = $missioninfo['faction']; // FC_CORPUS
    $mission_location         = $missioninfo['location']; // EventNode10
    $mission_leveloverride    = $missioninfo['levelOverride']; // /Lotus/Levels/Proc/Corpus/CorpusShipArmoredJackalBoss
    $mission_minlevel         = $missioninfo['minEnemyLevel']; // 20
    $mission_maxlevel         = $missioninfo['maxEnemyLevel']; // 30
    $mission_archwingrequired = $missioninfo['archwingRequired']; // false
    $mission_requireditems    = $missioninfo['requiredItems']; // array( /Lotus/StoreItems/Types/Restoratives/Consumable/RazorbackCipher );
    $mission_consumerequireditems = $missioninfo['consumeRequiredItems']; // true
    $mission_missionreward    = $missioninfo['missionReward']; // array( 'randomizedItems' => 'razorbackRewardManifest' );
    $mission_vipagent         = $missioninfo['vipAgent']; // /Lotus/Types/Enemies/Corpus/SpecialEvents/ArmoredJackal/ArmoredJackalAgent
    $mission_leadersalwaysallowed = $missioninfo['leadersAlwaysAllowed']; // true
    $mission_goaltag          = $missioninfo['goalTag']; // FriendlyFireTacAlert
    $mission_levelauras       = $missioninfo['levelAuras']; // array( '/Lotus/Upgrades/Mods/DirectorMods/BossDropReductionAura' );
    $mission_icon             = $missioninfo['icon']; // /Lotus/Interface/Icons/Events/RazorbackArmada.png

    $hubevent = $goals['ContinuousHubEvent'];
    $hubevent_transmission   = $hubevent['Transmission']; // /Lotus/Sounds/Dialog/HubAnnouncements/NefAnyoMoaEventPropaganda
    $hubevent_activation     = $hubevent['Activation']['$date']['$numberLong'];
    $hubevent_expiry         = $hubevent['Expiry']['$date']['$numberLong'];
    $hubevent_repeatinterval = $hubevent['RepeatInterval']; // 1800

    $reward_credit = $goals['Reward']['credits']; // 200000
    $reward_items  = $goals['Reward']['items']; // array( /Lotus/StoreItems/Types/Items/MiscItems/OrokinCatalyst );

    $activation = date('Y-m-d H:i:s', $activation / 1000 );
    $expiry     = date('Y-m-d H:i:s', $expiry / 1000 );

    $region = '';
    if ( ( isset( $solnodelist[$node] ) ) && ( isset( $solnodelist[$node]['region'] ) ) ) {
        $region = $solnodelist[$node]['region'];
    }
    if ( isset( $regionlist[ $region ] ) ) {
        $region = $regionlist[ $region ];
    }

    $retstr = sprintf( '%s %s～%s', 'Razorback Armada', $activation, $expiry ) . PHP_EOL;
    $rewardlist = parse_reward( $goals['Reward'] );
    $rewardstr = '';
    foreach( $rewardlist as $v ) {
        if ( $rewardstr != '' ) $rewardstr .= ', ';
        $rewardstr .= $v;
    }

    if ( $rewardstr != '' ) {
        $retstr .= $rewardstr . PHP_EOL;
    }

    return $retstr;
}

function parse_goals_fomorian( $goals ) {
    global $solnodelist, $regionlist, $itemtranslatelist, $timezone;

    date_default_timezone_set( $timezone );

    $retstr = '';

    $oid           = $goals['_id']['$oid']; // 5e5607093cea494b1ec8c7a0
    $fomorian      = $goals['Fomorian']; // true
    $activation    = $goals['Activation']['$date']['$numberLong'];
    $expiry        = $goals['Expiry']['$date']['$numberLong'];
    $countnum      = $goals['Count']; // 0
    $goal          = $goals['Goal']; // 1000000
    $healthpct     = $goals['HealthPct']; // 0.9594032
    $victimnode    = $goals['VictimNode']; // EuropaHUB
    $personal      = $goals['Personal']; // true
    $best          = $goals['Best']; // true
    $scorevar      = $goals['ScoreVar']; // "FomorianEventScore"
    $scoremaxtag   = $goals['ScoreMaxTag']; // "FomorianEventScore"
    $scoretagblock = $goals['ScoreTagBlocksGuildTierChanges']; // false
    $success       = $goals['Success']; // 0
    $node          = $goals['Node']; // EventNode12
    $faction       = $goals['Faction']; // FC_GRINEER
    $desc          = $goals['Desc']; // /Lotus/Language/G1Quests/FomorianRevengeBattleName
    $icon          = $goals['Icon']; // /Lotus/Materials/Emblems/SlingStone2_e.png
    $regiondrops   = $goals['RegionDrops']; // array( /Lotus/Types/Items/MiscItems/OmegaIsotopePickup );
    $archwingdrops = $goals['ArchwingDrops']; // array();
    $scoreloctag   = $goals['ScoreLocTag']; // /Lotus/Language/Menu/FomorianScoreHint
    $tag           = $goals['Tag']; // ''

    $missioninfo = $goals['MissionInfo'];
    $mission_type             = $missioninfo['missionType']; // MT_SABOTAGE
    $mission_faction          = $missioninfo['faction']; // FC_GRINEER
    $mission_location         = $missioninfo['location']; // EventNode12
    $mission_leveloverride    = $missioninfo['levelOverride']; // /Lotus/Levels/Proc/Space/SpaceGrineerFomorianAssaultProcLevel
    $mission_enemyspec        = $missioninfo['enemySpec']; // /Lotus/Types/Game/EnemySpecs/FomorianAttackSpec
    $mission_minlevel         = $missioninfo['minEnemyLevel']; // 20
    $mission_maxlevel         = $missioninfo['maxEnemyLevel']; // 30
    $mission_difficulty       = $missioninfo['difficulty']; // 1
    $mission_archwingrequired = $missioninfo['archwingRequired']; // true
    $mission_requireditems    = $missioninfo['requiredItems']; // array( /Lotus/StoreItems/Types/Restoratives/Consumable/FomorianNegator );
    $mission_consumerequireditems = $missioninfo['consumeRequiredItems']; // false
    $mission_missionreward    = $missioninfo['missionReward']; // array( 'randomizedItems' => 'fomorianRewardManifest' );
    $mission_vipagent         = $missioninfo['vipAgent']; // ''
    $mission_leadersalwaysallowed = $missioninfo['leadersAlwaysAllowed']; // true
    $mission_goaltag          = $missioninfo['goalTag']; // ''
    $mission_levelauras       = $missioninfo['levelAuras']; // array();
    $mission_icon             = $missioninfo['icon']; // /Lotus/Interface/Icons/Events/Fomorian.png

    $hubevent = $goals['ContinuousHubEvent'];
    $hubevent_transmission   = $hubevent['Transmission']; // /Lotus/Sounds/Dialog/HubAnnouncements/HekPropoganda
    $hubevent_activation     = $hubevent['Activation']['$date']['$numberLong'];
    $hubevent_expiry         = $hubevent['Expiry']['$date']['$numberLong'];
    $hubevent_repeatinterval = $hubevent['RepeatInterval']; // 1800

    $reward_credit = $goals['Reward']['credits']; // 200000
    $reward_items  = $goals['Reward']['items']; // array( /Lotus/StoreItems/Types/Items/MiscItems/OrokinCatalyst );

    $activation = date('Y-m-d H:i:s', $activation / 1000 );
    $expiry     = date('Y-m-d H:i:s', $expiry / 1000 );

    $region = '';
    if ( ( isset( $solnodelist[$node] ) ) && ( isset( $solnodelist[$node]['region'] ) ) ) {
        $region = $solnodelist[$node]['region'];
    }
    if ( isset( $regionlist[ $region ] ) ) {
        $region = $regionlist[ $region ];
    }

    $retstr = sprintf( '%s %s～%s', 'バロール・フォーモリアン', $activation, $expiry ) . PHP_EOL;
    $rewardlist = parse_reward( $goals['Reward'] );
    $rewardstr = '';
    foreach( $rewardlist as $v ) {
        if ( $rewardstr != '' ) $rewardstr .= ', ';
        $rewardstr .= $v;
    }

    if ( $rewardstr != '' ) {
        $retstr .= $rewardstr . PHP_EOL;
    }

    return $retstr;
}

function parse_goals_thermia( $goals ) {
    global $solnodelist, $regionlist, $itemtranslatelist, $timezone;

    date_default_timezone_set( $timezone );

    $retstr = '';

    $oid           = $goals['_id']['$oid'];
    $activation    = $goals['Activation']['$date']['$numberLong'];
    $expiry        = $goals['Expiry']['$date']['$numberLong'];
    $node          = $goals['Node']; // SolNode129
    $scorevar      = $goals['ScoreVar']; // FissuresClosed
    $scoreloctag   = $goals['ScoreLocTag']; // /Lotus/Language/G1Quests/HeatFissuresEventScore

    $countnum      = $goals['Count']; // 0
    $healthpct     = $goals['HealthPct']; // 0
    $regions       = $goals['Regions']; // array( 1 );
    $desc          = $goals['Desc']; // /Lotus/Language/G1Quests/HeatFissuresEventName
    $tooltip       = $goals['ToolTip']; // /Lotus/Language/G1Quests/HeatFissuresEventDesc
    $optionalinmission = $goals['OptionalInMission']; // true
    $tag           = $goals['Tag']; // HeatFissure
    foreach( $goals['UpgradeIds'] as $v ) {
        $oid = $v['$oid']; // 5e58221912e7b6cb219bdcfe
    }
    $personal      = $goals['Personal']; // true
    $goal          = $goals['Goal']; // 100

    $reward_credit       = $goals['Reward']['credits']; // 0
    $reward_xp           = $goals['Reward']['xp']; // 0
    $reward_items        = $goals['Reward']['items']; // array( /Lotus/StoreItems/Weapons/Corpus/LongGuns/CrpBFG/Vandal/VandalCrpBFG );
    $reward_counteditems = $goals['Reward']['countedItems']; // array();

    $interimgoals[0] = $goals['InterimGoals'][0]; // 5
    $interimgoals[1] = $goals['InterimGoals'][1]; // 25
    $interimgoals[2] = $goals['InterimGoals'][2]; // 50
    $interimgoals[3] = $goals['InterimGoals'][3]; // 75

    foreach( $goals['InterimRewards'] as $v ) { // 各中間報酬(4つ)
        $interimreward_credits      = $v['credits']; // 0
        $interimreward_xp           = $v['xp']; // 0
        $interimreward_items        = $v['items']; // array( /Lotus/StoreItems/Upgrades/Skins/Clan/OrbBadgeItem );
        $inrerimreward_counteditems = $v['countedItems']; // array();
    }

    $activation = date('Y-m-d H:i:s', $activation / 1000 );
    $expiry     = date('Y-m-d H:i:s', $expiry / 1000 );

    $retstr = sprintf( '%s %s～%s', 'サーミアの裂け目', $activation, $expiry ) . PHP_EOL;

    return $retstr;
}

function parse_goals_ghoul( $goals ) {
    global $solnodelist, $regionlist, $itemtranslatelist, $timezone;

    date_default_timezone_set( $timezone );

    $retstr = '';

    $oid           = $goals['_id']['$oid'];
    $activation    = $goals['Activation']['$date']['$numberLong'];
    $expiry        = $goals['Expiry']['$date']['$numberLong'];
    $healthpct     = $goals['HealthPct']; // 1
    $victimnode    = $goals['VictimNode']; // SolNode228
    $regions       = $goals['Regions']; // array( 2 );
    $success       = $goals['Success']; // 0
    $desc          = $goals['Desc']; // /Lotus/Language/GameModes/RecurringGhoulAlert
    $tooltip       = $goals['Desc']; // /Lotus/Language/GameModes/RecurringGhoulAlertDesc
    $icon          = $goals['Icon']; // /Lotus/Interface/Icons/Categories/IconGhouls256.png
    $tag           = $goals['Tag']; // GhoulEmergence

	$jobs = $goals['Jobs']; // 開催中のbounty情報
    foreach( $jobs as $v ) { // lv15-25 lv40-50 の二つ
	    $jobtype       = $v['jobType']; // /Lotus/Types/Gameplay/Eidolon/Jobs/Events/GhoulAlertBountyRes
		$rewards       = $v['rewards']; // /Lotus/Types/Game/MissionDecks/EidolonJobMissionRewards/GhoulBountyTableARewards
		$masteryreq    = $v['masteryReq']; // 1
		$minenemylevel = $v['minEnemyLevel']; // 15
		$maxenemylevel = $v['maxEnemyLevel']; // 25
		$xpamounts[0]  = $v['xpAmounts'][0]; // 330
		$xpamounts[1]  = $v['xpAmounts'][1]; // 330
		$xpamounts[2]  = $v['xpAmounts'][2]; // 330
		$xpamounts[3]  = $v['xpAmounts'][3]; // 480
    }
    $jobpreviousversion = $goals['JobPreviousVersion']['$oid']; // 5e6125495d3ec16acecb3cd8
    $previousjobs = $goals['PreviousJobs'];
    foreach( $jobs as $v ) { // lv15-25 lv40-50 の二つ
	    $jobtype       = $v['jobType']; // /Lotus/Types/Gameplay/Eidolon/Jobs/Events/GhoulAlertBountyHunt
		$rewards       = $v['rewards']; // /Lotus/Types/Game/MissionDecks/EidolonJobMissionRewards/GhoulBountyTableARewards
		$masteryreq    = $v['masteryReq']; // 1
		$minenemylevel = $v['minEnemyLevel']; // 15
		$maxenemylevel = $v['maxEnemyLevel']; // 25
		$xpamounts[0]  = $v['xpAmounts'][0]; // 280
		$xpamounts[1]  = $v['xpAmounts'][1]; // 280
		$xpamounts[2]  = $v['xpAmounts'][2]; // 280
		$xpamounts[3]  = $v['xpAmounts'][3]; // 410
    }

    $activation = date('Y-m-d H:i:s', $activation / 1000 );
    $expiry     = date('Y-m-d H:i:s', $expiry / 1000 );

    $retstr = sprintf( '%s %s～%s', 'グール粛清', $activation, $expiry ) . PHP_EOL;

    return $retstr;
}

function parse_goals_unknown( $goals ) {
    global $solnodelist, $regionlist, $itemtranslatelist, $timezone;

    date_default_timezone_set( $timezone );

    $retstr = '';

    $oid           = $goals['_id']['$oid'];
    $activation    = $goals['Activation']['$date']['$numberLong'];
    $expiry        = $goals['Expiry']['$date']['$numberLong'];
    $desc          = $goals['Desc']; // /Lotus/Language/GameModes/RecurringGhoulAlert

    $activation = date('Y-m-d H:i:s', $activation / 1000 );
    $expiry     = date('Y-m-d H:i:s', $expiry / 1000 );

    $retstr = sprintf( '%s %s～%s', 'unknown goals event ' . $desc, $activation, $expiry ) . PHP_EOL;
    $rewardlist = parse_reward( $goals['Reward'] );
    $rewardstr = '';
    foreach( $rewardlist as $v ) {
        if ( $rewardstr != '' ) $rewardstr .= ', ';
        $rewardstr .= $v;
    }

    if ( $rewardstr != '' ) {
        $retstr .= $rewardstr . PHP_EOL;
    }


    return $retstr;
}



function parse_sentientship( $sentient ) {

    $locationlist = array(
        505 => "Ruse War Field",
        510 => "Gian Point",
        550 => "Nsu Grid",
        551 => "Ganalen's Grave",
        552 => "Rya",
        553 => "Flexa",
        554 => "H-2 Cloud",
        555 => "R-9 Cloud"
    );

    $retstr = '';
    $node = '';

    $senti = json_decode( $sentient, true );
    if ( isset( $senti['sfn'] ) ) {
        $node = $senti['sfn'];
        if ( isset( $locationlist[ 0 + $node ] ) ) {
            $node = $locationlist[ 0 + $node ];
        } else {
            $node = "UnknownNode(" . $node . ")";
        }
    }

    if ( $node != '' ) {
        $retstr .= "センチエント船遭遇警報！\n";
        $retstr .= "位置：" . $node . "\n";
    }

    return $retstr;
}

function parse_reward( $rewardinfo ) {
    global $itemtranslatelist;

    $rewardlist = array();
    foreach( $rewardinfo as $k => $v ) {
        switch( $k ) {
        case 'credits':
            $rewardlist[] = sprintf( "%s Credit", number_format( $v ) );
            break;
        case 'items':
            foreach( $v as $v1 ) {
                $in = $v1;
                if ( isset( $itemtranslatelist[$in] ) ) {
                    $in = $itemtranslatelist[$in];
                }
                $rewardlist[] = $in;
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
                if ( $in != '' ) {
                    if ( $ic == 1 ) {
                        $rewardlist[] = $in;
                    } else if ( $ic >= 2 ) {
                        $rewardlist[] = $ic . 'x ' . $in;
                    }
                }
            }
            break;
        default:
            break;
        }
    }

    return $rewardlist;
}
?>
