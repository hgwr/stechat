<?php
require_once("config.php");
require_once("util.php");

ini_set('zlib.output_compression', 'Off');

$o = array();

try {
    if (! isset($_COOKIE['stechat'])) {
        throw new RuntimeException('Cookie is disabled?');
    }

    session_name("stechat");
    session_start();
    sc_set_idstr();

    if (isset($_SESSION['prev_time']) && $_SESSION['prev_time'] + 5 > time()) {
        throw new RuntimeException('Wait a sec.');
    }
    $_SESSION['prev_time'] = time();

    if (!isset($_POST['room']) || trim($_POST['room']) === '') {
        throw new RuntimeException("'room' is too short.");
    }
    if (get_magic_quotes_gpc()) {
        $_POST['room'] = stripslashes($_POST['room']);
    }
    $o['room'] = trim($_POST['room']);

    if (strlen($o['room']) >= MAX_ROOM_LEN ) {
        throw new RuntimeException("Some parameters are too long.");
    }

    sc_wipeout_content($o['room'], $_SESSION['idstr']);
} catch (RuntimeException $e) {
    $o['error'] = $e->getMessage();
}

header("Content-type: text/plain");

if (isset($o['error'])) {
    print $o['error'] . "\n";
}
