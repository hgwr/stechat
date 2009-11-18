<?php
require_once("config.php");
require_once("util.php");

session_name("stechat");
session_start();
sc_set_idstr();

$o = array();
if (isset($_GET['room']) && trim($_GET['room']) !== "") {
    if (get_magic_quotes_gpc()) {
        $o['room'] = trim(stripslashes($_GET['room']));
    }
}
if (strlen($o['room']) >= MAX_ROOM_LEN) {
    $o['room'] = NULL;
}

if ($o['room']) {
    $o['next_room'] = sc_generate_next_room_name($o['room']);
    $o['next_room_url'] = '?room=' . urlencode($o['next_room']);
    $o['prev_room'] = sc_generate_prev_room_name($o['room']);
    if ($o['prev_room'] == $o['room']) {
        unset($o['prev_room']);
    } else {
        $o['prev_room_url'] = '?room=' . urlencode($o['prev_room']);
    }
    include("room_view.php");
} else {
    include("index_view.php");
}
