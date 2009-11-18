<?php
require_once("config.php");

function sc_set_idstr() {
    if (!isset($_SESSION['idstr'])) {
        $_SESSION['idstr'] = sprintf("%u", (mt_rand() / mt_getrandmax()) * 4294967295.0);
    }
}

function sc_out($text, $returnString = FALSE) {
    $str = htmlspecialchars($text, ENT_QUOTES, "UTF-8");
    if ($returnString) {
        return $str;
    } else {
        print $str;
    }
}

function sc_dat_encode($str) {
    return str_replace("\n", "<>", htmlspecialchars($str, ENT_QUOTES, "UTF-8"));
}

function sc_dat_decode($str) {
    return htmlspecialchars_decode(str_replace("<>", "\n", $str), ENT_QUOTES);
}

function sc_dat_filename($room) {
    $hashed = sha1($room);
    $subdir = substr($hashed, 0, 2);
    return join('', array(DATFILE_DIR, "/", $subdir, "/", $hashed, DATFILE_EXT));
}

function sc_trip($name) {
    if (preg_match('/^(.+?#)(.+)$/', $name, $matches)) {
        $name = $matches[1] . base64_encode(sha1($matches[2], true));
    }
    return $name;
}

function sc_generate_next_room_name($name) {
    if (preg_match('/^(.+)\(([0-9]+)\)$/', $name, $matches)) {
        $name = sprintf("%s(%d)", $matches[1], ((int) $matches[2]) + 1);
    } else {
        $name .= '(2)';
    }
    return $name;
}

function sc_generate_prev_room_name($name) {
    if (preg_match('/^(.+)\(([0-9]+)\)$/', $name, $matches)) {
        $current = (int) $matches[2];
        if ($current == 2) {
            $name = $matches[1];
        } elseif ($current == 1) {
            // do nothing
        } else {
            $prev = $current - 1;
            $name = sprintf("%s(%d)", $matches[1], $prev);
        }
    }
    return $name;
}

function sc_read_data($room, $old_size) {
    $dat = NULL;
    $fp = @fopen(sc_dat_filename($room), "r");
    if ($fp && flock($fp, LOCK_SH)) {
        $stat = fstat($fp);
        $size = $stat['size'];
        if ($old_size < $size) {
            $dat = fread($fp, MAX_DAT_SIZE);
        }
        flock($fp, LOCK_UN);
    }
    $fp !== FALSE && fclose($fp);
    if ($dat == NULL) {
        return array($size, NULL);
    }
    
    $contents = explode("\n", $dat);
    array_shift($contents);     // discard the title line
    array_pop($contents);       // discard a blank line
    
    return array($size, $contents);
}

function sc_prepare_contents(&$contents, $doEncode) {
    $len = sizeof($contents);
    for ($i = 0; $i < $len; $i++) {
        list($name, $idstr, $time, $content) = unserialize($contents[$i]);
        $name = sc_dat_decode($name);
        $content = sc_dat_decode($content);
        if ($doEncode) {
            $name = sc_out($name, true);
            $content = sc_out($content, true);
        }
        $contents[$i] = array($name, $idstr, $time, $content);
    }
}

function sc_get_contents($room, $start = NULL, $end = NULL, $doEncode = false) {
    list($size, $contents) = sc_read_data($room, 0);
    $dat_len = sizeof($contents);
    if ($contents == NULL || ($start != NULL && $start > $dat_len)) {
        return array(array($size));
    }

    if ($start == NULL || $start <= 0) {
        $start = 1;
    }
    if ($end == NULL || $end <= 0 || $end > sizeof($contents)) {
        $end = sizeof($contents);
    }
    if ($start > $end) {
        $end = $start + 1;
    }
    $len = $end - $start + 1;
    if ($len > MAX_MSGS_LEN) {
        $len = MAX_MSGS_LEN;
        $end = $start + $len - 1;
    }
    $contents = array_slice($contents, $start - 1, $len);

    sc_prepare_contents($contents, $doEncode);
    array_unshift($contents, array($size, $start, $end, ($end == $dat_len) ? 1 : 0));
    return $contents;
}

function sc_get_last_contents($room, $old_size, $doEncode = false) {
    list($size, $contents) = sc_read_data($room, $old_size);
    if ($contents == NULL) {
        return array(array($size));
    }
    $len = sizeof($contents);
    $start = 1;
    if ($len > MAX_MSGS_LEN) {
        $start = $len - MAX_MSGS_LEN + 1;
        $len = MAX_MSGS_LEN;
        $contents = array_slice($contents, $start - 1, $len);
    }
    sc_prepare_contents($contents, $doEncode);
    array_unshift($contents, array($size, $start, $start + $len - 1, 1));
    return $contents;
}

function sc_add_content($room, $name, $idstr, $content) {
    $name = sc_trip($name);
    $size = 0;
    $fp = fopen(sc_dat_filename($room), "a");
    if (flock($fp, LOCK_EX)) {
        $stat = fstat($fp);
        $size = $stat['size'];
        if ($size == 0) {
            $size = fwrite($fp, sc_dat_encode($room) . "\n");
        }
        $output = serialize(array(sc_dat_encode($name), $idstr, time(), sc_dat_encode($content))) . "\n";
        if ($size + strlen($output) < MAX_DAT_SIZE) {
            $size += fwrite($fp, $output);
        } else {
            $size = -1;
        }
        flock($fp, LOCK_UN);
    }
    fclose($fp);
    return $size;
}

function sc_wipeout_content($room, $idstr) {
    $fp = fopen(sc_dat_filename($room), "w");
    if (flock($fp, LOCK_EX)) {
        fwrite($fp, sc_dat_encode($room) . "\n");
        $output = serialize(array('', $idstr, time(), sc_dat_encode("wiped out"))) . "\n";
        fwrite($fp, $output);
        flock($fp, LOCK_UN);
    }
    fclose($fp);
}
