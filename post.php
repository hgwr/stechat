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

    foreach (array('room', 'name', 'cont') as $key) {
        if ($key != 'name' && (!isset($_POST[$key]) || trim($_POST[$key]) === '')) {
            throw new RuntimeException("'$key' is too short.");
        }
        if (get_magic_quotes_gpc()) {
            $_POST[$key] = stripslashes($_POST[$key]);
        }
        $o[$key] = trim($_POST[$key]);
    }
    if (strlen($o['room']) >= MAX_ROOM_LEN ||
        strlen($o['name']) >= MAX_NAME_LEN ||
        strlen($o['cont']) >= MAX_CONT_LEN ) {
        throw new RuntimeException("Some parameters are too long.");
    }

    $_SESSION['name'] = $o['name'];
    $size = sc_add_content($o['room'], $o['name'], $_SESSION['idstr'], $o['cont']);
    if ($size < 0) {
        throw new RuntimeException("Data file is full.");
    }
} catch (RuntimeException $e) {
    $o['error'] = $e->getMessage();
}

header("Content-type: text/plain");

if (isset($o['error'])) {
    print $o['error'] . "\n";
}
