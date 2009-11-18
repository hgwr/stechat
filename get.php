<?php
require_once("config.php");
require_once("util.php");

$o = array();
foreach (array('room', 'm', 's', 'e') as $key) {
    if (get_magic_quotes_gpc()) {
        $_GET[$key] = stripslashes($_GET[$key]);
    }
    $o[$key] = trim($_GET[$key]);
}

if (!isset($o['room']) ||
    strlen($o['room']) >= MAX_ROOM_LEN ) {
    exit;
}

foreach (array('m', 's', 'e') as $key) {
    if (isset($o[$key]) && is_numeric($o[$key])) {
        $o[$key] = ((int) $o[$key]);
        if ($o[$key] <= 0) { $o[$key] = 1; }
    }
}

if (isset($_GET['last'])) {
    $contents = sc_get_last_contents($o['room'], $o['m']);
} else {
    $contents = sc_get_contents($o['room'], $o['s'], $o['e'], true);
}

$len_contents = sizeof($contents) - 1;
if ($len_contents == 0) {
    ini_set('zlib.output_compression', 'Off');   
}
session_name("stechat");
session_start();
sc_set_idstr();

header("Content-type: text/javascript");

if ($len_contents > 0) {
    print json_encode($contents);
}
