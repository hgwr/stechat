<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html><head>
<meta http-equiv="Content-Style-Type" content="text/css">
<meta http-equiv="Content-Script-Type" content="text/javascript">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link type="text/css" rel="stylesheet" href="stechat.css">
<script type="text/javascript" src="prototype.js"></script>
<script type="text/javascript" src="effects.js"></script>
<!--[if IE]><script type="text/javascript" src="http://www.moreslowly.jp/products/identicon/excanvas.js"></script><![endif]-->
<script type="text/javascript" src="http://www.moreslowly.jp/products/identicon/identicon.js"></script>
<script type="text/javascript" src="json2.js"></script>
<script type="text/javascript" src="stechat.js"></script>
<title>Stechat<?php  print ' - '; sc_out($o['room']); ?></title>
</head>
<body>

<div id="page_header">
  <div id="header_title">
    <div style="float: left; width: 18px; height: 18px;">
    <img id="loader_wheel" width="16" height="16" src="ajax-loader.gif">
    </div>
    <div style="float: left; "><a href=".">Stechat</a><?php print ' - '; sc_out($o['room']); ?></div>
    <div style="text-align: right"><a href="help.html">Help</a></div>
    <div style="clear: left"></div>
  </div>

  <form name="utterance" id="utterance" action="javascript:void(0)">
    <input type="hidden" name="room" id="room" value="<?php sc_out($o['room']); ?>">
    <div style="margin: 2px;">
      <input type="text" name="name" id="name" title="Name" value="<?php sc_out($_SESSION['name']); ?>" tabindex="1">
    </div>
    <div>
      <textarea name="cont" id="cont" title="Message" tabindex="2"></textarea>
    </div>
    <div style="float: left; ">
      <input type="submit" name="send" id="send" value="Send" title="'Shift-Enter' to send message" tabindex="3">
    </div>
  </form>
  <div style="float: left; padding-left: 0.2em; ">
    <span id="auto_reload_label">
      <input type="checkbox" name="auto_reload" id="auto_reload" checked>
      <label for="auto_reload">Auto Scroll</label> 
    </span>
    <input type="button" id="top_link" title="Go to Top" value="|&lt;&lt;">
    <input type="button" id="bottom_link" title="Go to Bottom" value="&gt;&gt;|">
    <input type="button" id="jump" value="Jump"> to (<input type="text" name="num" id="num" size="3">).
  </div>
  <div style="text-align: right">
    <input type="button" id="wipeout" value="Wipe Out!">
  </div>
  <div style="clear: left"></div>
  <div id="errors"></div>
  <div id="messages"></div>
</div>

<div id="contents"></div>

<div id="page_footer">
  <div style="float: left"><a href="http://www.moreslowly.jp/">moreslowly.jp</a></div>
  <div style="text-align: right">
    <?php if (isset($o['prev_room'])) { ?>
    <a href="<?php sc_out($o['prev_room_url']); ?>"><?php sc_out($o['prev_room']); ?></a>
    <? } ?>
    <?php if (isset($o['next_room'])) { ?>
    <a href="<?php sc_out($o['next_room_url']); ?>"><?php sc_out($o['next_room']); ?></a>
    <? } ?>
  </div>
  <div style="clear: left"></div>
</div>
</body></html>
