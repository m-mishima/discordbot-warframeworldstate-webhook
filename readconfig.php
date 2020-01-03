<?php

function readconfig() {
    $readlist = array( 'missiontypelist',
                       'regionlist',
                       'solnodelist',
                       'sortiebosslist',
                       'sortiemodifierlist',
                       'fissurefilterlist',
                       'voidrelictierlist',
                       'timezone',
                       'worldstateurl',
                       'webhookurl_sortie',
                       'webhookurl_fissure',
                       'webhookurl_nicefissure',
                       'webhookurl_devel',
                       'itemtranslatelist'
    );

    foreach ( $readlist as $v ) {
        readjsonconfig( $v );
    }
}


function readjsonconfig( $name ) {

    global ${$name}, ${$name . '_filedate'};

    $filename       = __DIR__ . '/configs/' . $name . '.json';
    $arrayname      = $name;
    $arrayname_date = $name . '_filedate';

    if ( ! is_readable( $filename ) ) return false;

    $filetime = filemtime( $filename );

    if ( $filetime === ${$arrayname_date} ) {
        //echo 'not modified, skip "' . $filename . '"' . PHP_EOL;
        return true;
    }

    $src = file_get_contents( $filename );
    // コメント除去
    $src = preg_replace( "/(\/\*[\s\S]*?\*\/|\/\/.*)/", "", $src );
    // ケツカンマ除去
    $src = preg_replace( "/(,)(\s*[\]\}])/", "$2", $src );

    $src = json_decode( $src, true );
    if ( json_last_error() !== JSON_ERROR_NONE ) {
        echo 'config file "' . $filename . '" in ' . json_last_error_msg() . PHP_EOL;
        return null;
    }

    ${$arrayname}      = $src;
    ${$arrayname_date} = $filetime;

    return ${$arrayname};
}

?>
