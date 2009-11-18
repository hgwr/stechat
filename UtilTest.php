<?php
require_once 'PHPUnit/Framework.php';
require_once 'util.php';

class UtilTest extends PHPUnit_Framework_TestCase {
    public function testScDatEncode() {
        $a = "hello <b>world</b>";
        $this->assertEquals('hello &lt;b&gt;world&lt;/b&gt;', sc_dat_encode($a));
    }

    public function testScDatDecode() {
        $a = 'hello &lt;b&gt;world&lt;/b&gt;';
        $this->assertEquals('hello <b>world</b>', sc_dat_decode($a));
    }

    public function testScDatFilename() {
        $this->assertEquals('dat/7e/7e240de74fb1ed08fa08d38063f6a6a91462a815.chatdat', sc_dat_filename('aaa'));
    }

    public function testScAddContent() {
        $room = 'njiSeDkea2';
        $name = 'foo';
        $idstr = '123';
        $content = 'Hello <b>world</b>';
        $dat_filename = sc_dat_filename($room);
        if (file_exists($dat_filename)) {
            unlink($dat_filename);
        }
        
        $pos = sc_add_content($room, $name, $idstr, $content);
        $this->assertTrue($pos > 0);
        $all_data = file($dat_filename);
        $this->assertEquals(2, sizeof($all_data));
        $this->assertEquals($room, trim($all_data[0]));

        $contents = sc_get_contents($room);
        $this->assertEquals(1, $contents[0][1]);
        $this->assertEquals(1, $contents[0][2]);
        $this->assertEquals($name, $contents[1][0]);
        $this->assertEquals($idstr, $contents[1][1]);
        $this->assertEquals($content, $contents[1][3]);

        sc_add_content($room, $name, $idstr, $content);
        sc_add_content($room, $name, $idstr, "3");
        sc_add_content($room, $name, $idstr, "4");
        sc_add_content($room, '', $idstr, "5");

        $contents = sc_get_contents($room, 4);
        $this->assertEquals(4, $contents[0][1]);
        $this->assertEquals(5, $contents[0][2]);
        $this->assertEquals(3, sizeof($contents));
        $chat = $contents[1];
        $this->assertEquals($name, $chat[0]);
        $this->assertEquals($idstr, $chat[1]);
        $this->assertEquals("4", $chat[3]);
        $chat = $contents[2];
        $this->assertEquals('', $chat[0]);
        $this->assertEquals($idstr, $chat[1]);
        $this->assertEquals("5", $chat[3]);
        
        $contents = sc_get_contents($room, 6);
        $this->assertEquals(1, sizeof($contents[0]));
        array_shift($contents);
        $this->assertEquals(0, sizeof($contents));
    }
    
    public function testScTrip() {
        $this->assertEquals("foo", sc_trip("foo"));
        $this->assertEquals("foo#".base64_encode(sha1('x',true)), sc_trip("foo#x"));
    }

    public function testScGetLastContents() {
        $room = 'njiSeDkea2';
        $name = 'foo';
        $idstr = '123';
        $content = 'Hello <b>world</b>';
        $dat_filename = sc_dat_filename($room);
        if (file_exists($dat_filename)) {
            unlink($dat_filename);
        }

        for ($i = 1; $i <=20; $i++) {
            sc_add_content($room, $name, $idstr, $i);
        }
        $contents = sc_get_last_contents($room, 0);
        $this->assertEquals(21, sizeof($contents));

        $this->assertEquals(1, $contents[0][1]);
        $this->assertEquals(20, $contents[0][2]);
        $chat = $contents[1];
        $this->assertEquals($name, $chat[0]);
        $this->assertEquals($idstr, $chat[1]);
        $this->assertEquals("1", $chat[3]);
        $chat = $contents[sizeof($contents) - 1];
        $this->assertEquals($name, $chat[0]);
        $this->assertEquals($idstr, $chat[1]);
        $this->assertEquals("20", $chat[3]);        


        for ($i = 21; $i <= MAX_MSGS_LEN + 20; $i++) {
            sc_add_content($room, $name, $idstr, $i);
        }
        $contents = sc_get_last_contents($room, 0);
        $this->assertEquals(MAX_MSGS_LEN + 1, sizeof($contents));

        $this->assertEquals(21, $contents[0][1]);
        $this->assertEquals(MAX_MSGS_LEN + 20, $contents[0][2]);
        $chat = $contents[1];
        $this->assertEquals($name, $chat[0]);
        $this->assertEquals($idstr, $chat[1]);
        $this->assertEquals("21", $chat[3]);
        $chat = $contents[sizeof($contents) - 1];
        $this->assertEquals($name, $chat[0]);
        $this->assertEquals($idstr, $chat[1]);
        $this->assertEquals("" . (MAX_MSGS_LEN + 20), $chat[3]);        
    }

    public function testGenerateRoomName() {
        $names = array(array("aaa", "aaa(1)", "aaa(2)", "aaa(3)"),
                      array("aaa(-1)", "aaa(-1)(1)", "aaa(-1)(2)", "aaa(-1)(3)"));
        foreach ($names as $name) {
            $this->assertEquals($name[2], sc_generate_next_room_name($name[0]), $name[0]);
            $this->assertEquals($name[2], sc_generate_next_room_name($name[1]), $name[1]);
            $this->assertEquals($name[3], sc_generate_next_room_name($name[2]));

            $this->assertEquals($name[2], sc_generate_prev_room_name($name[3]));
            $this->assertEquals($name[0], sc_generate_prev_room_name($name[2]));
            $this->assertEquals($name[1], sc_generate_prev_room_name($name[1]), $name[1]);
            $this->assertEquals($name[0], sc_generate_prev_room_name($name[0]));
        }
    }
}
