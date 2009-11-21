// if (window.opera && !window.console) {
//     window.console = {};
//     var fn = function () { opera.postError(arguments); };
//     ['log', 'debug', 'info', 'warn', 'error', 'assert', 'dir', 'dirxml', 'group', 'groupEnd',
//      'time', 'timeEnd', 'count', 'trace', 'profile', 'profileEnd'].forEach(function (name) {
//          window.console[name] = fn;
//      });
// }

Date.prototype.toStechatString = function () {
  return [
    [ this.getFullYear(), (this.getMonth() + 1).toPaddedString(2), this.getDate().toPaddedString(2) ].join('-'),
    [ this.getHours().toPaddedString(2), this.getMinutes().toPaddedString(2), this.getSeconds().toPaddedString(2) ].join(':')
  ].join(' ');
};

var Stechat = {
  MAX_DAT_SIZE: 524288,
  MAX_MSGS_LEN: 30,
  SCROLL_UNIT: 10,
  __holdingContents: { s: 0, e: 0 },
  __hasTail: false,
  __fileSize: 0,
  __reloader: null,
  __scrollMonitor: null,

  nstr: function (s, n) {
    var r = '', i = 0;
    for (; i < n; i++) { r += s; }
    return r;
  },

  numround: function (num, position) {
    var base = Math.round(num),
    fraction = Math.abs(num - base),
    rounded = Math.round(fraction * Math.pow(10.0, position));
    return base + "." + Stechat.nstr('0', (position - String(rounded).length)) + rounded;
  },

  isBlank: function (val) {
    return val.replace(/[ \t]+$/, '').replace(/^[ \t]+/, '').length === 0;
  },

  validate: function (key, val) {
    var field = { cont: 'content', room: 'room name' };
    if (Stechat.isBlank(val)) {
      Stechat.flash('errors', field[key] + " is too short.");
      return true;
    }
    return false;
  },

  renderer: {
    insertBeforeTop: function (contents) {
      var len = contents.length,
      i = len - 1,
      contentsElement = $('contents'),
      element, contentsStart;
      if (len <= 1) { return; }
      Stechat.__fileSize = contents[0][0];
      Stechat.__holdingContents.s = contentsStart = contents[0][1];
      Stechat.__hasTail = contents[0][3];

      while (contentsElement.childNodes.length > 0 &&
             contentsElement.childNodes.length + contents.length > Stechat.MAX_MSGS_LEN) {
        contentsElement.removeChild(contentsElement.lastChild);
        Stechat.__holdingContents.e--;
      }

      for (; i >= 1; i--) {
        element = document.createElement('div');
        element.className = 'chat';
        contentsElement.insertBefore(element, contentsElement.firstChild);
        Stechat.createChat(element, contents[i], contentsStart + i - 1);
      }
      return true;
    },

    appendToBottom: function (contents) {
      var len = contents.length,
      i = 1,
      contentsElement = $('contents'),
      element, contentsStart;
      if (len <= 1) { return; }
      Stechat.__fileSize = contents[0][0];
      contentsStart = contents[0][1];
      Stechat.__holdingContents.e = contents[0][2];
      Stechat.__hasTail = contents[0][3];

      while (contentsElement.childNodes.length > 0 &&
             contentsElement.childNodes.length + contents.length > Stechat.MAX_MSGS_LEN) {
        contentsElement.removeChild(contentsElement.firstChild);
        Stechat.__holdingContents.s++;
      }

      for (; i < len; i++) {
        element = document.createElement('div');
        element.className = 'chat';
        contentsElement.appendChild(element);
        Stechat.createChat(element, contents[i], contentsStart + i - 1);
      }
      return true;
    },

    replaceWhole: function (contents) {
      var len = contents.length,
      i = 1,
      contentsElement = $('contents'),
      element, contentsStart;
      if (len <= 1) { return; }
      Stechat.__fileSize = contents[0][0];
      Stechat.__holdingContents.s = contentsStart = contents[0][1];
      Stechat.__holdingContents.e = contents[0][2];
      Stechat.__hasTail = contents[0][3];

      while (contentsElement.childNodes.length > 0) {
        contentsElement.removeChild(contentsElement.firstChild);
      }

      for (; i < len; i++) {
        element = document.createElement('div');
        element.className = 'chat';
        contentsElement.appendChild(element);
        Stechat.createChat(element, contents[i], contentsStart + i - 1);
      }
      return true;
    }
  },

  remoteFileSizeMsg: function () {
    var r = Stechat.numround((Stechat.__fileSize / Stechat.MAX_DAT_SIZE) * 100.0, 2);
    return ["remote file size ", Stechat.__fileSize, "/", Stechat.MAX_DAT_SIZE, " bytes (", r, "%)."].join("");
  },

  flash: function (kind, msg) {
    var elem = $(kind),
    doEffect = arguments[2],
    effect;
    elem.update(msg);
    if (doEffect) {
      effect = new Effect.Highlight(elem, { duration: 0.3, startcolor: '#000000', endcolor: '#00ff00' });
    }
    window.setTimeout(function () { elem.update(''); }, 6000);
  },

  createChat: function (chatElem, c, number) {
    var size = 36, identicon,
    name = c[0], idstr = c[1], date = new Date(c[2] * 1000), content = c[3],
    fullName = name,
    m = name.match(/^(.+#.{8}).+$/),
    headElem = document.createElement('div'),
    numbElem = document.createElement('div'),
    iconElem = document.createElement('canvas'),
    nameElem = document.createElement('div'),
    dateElem = document.createElement('div'),
    clear1Elem = document.createElement('div'),
    clear2Elem = document.createElement('div'),
    contElem = document.createElement('div');

    if (m) { name = m[1]; }
    if (Stechat.isBlank(name)) {
      name = '<em>anonymous</em>';
      fullName = 'anonymous';
    }

    headElem.className = 'header';
    numbElem.className = 'num';
    iconElem.className = 'icon';
    nameElem.className = 'name';
    dateElem.className = 'date';
    clear1Elem.className = 'clear1';
    contElem.className = 'content';
    clear2Elem.className = 'clear2';

    numbElem.innerHTML = ['(', number, ')'].join('');
    nameElem.innerHTML = name;
    nameElem.setAttribute("title", fullName);
    dateElem.innerHTML = date.toStechatString();
    contElem.innerHTML = content;

    chatElem.id = 'chat' + number;
    chatElem.appendChild(headElem);
    chatElem.appendChild(contElem);
    chatElem.appendChild(clear2Elem);

    headElem.appendChild(numbElem);

    iconElem.setAttribute("width", size);
    iconElem.setAttribute("height", size);
    headElem.appendChild(iconElem);
    if (typeof G_vmlCanvasManager != "undefined") {
      iconElem = G_vmlCanvasManager.initElement(iconElem);
    }
    identicon = new Identicon(iconElem, parseInt(idstr, 10), size);
    iconElem.title = idstr;

    headElem.appendChild(nameElem);
    headElem.appendChild(dateElem);
    headElem.appendChild(clear1Elem);

    return chatElem;
  },

  get: function (handler) {
    var ajaxParam, ajax, handlerResult,
    afterHandler = arguments[1],
    getParams = Object.extend({
                                room: $F('room')
                              }, arguments[2] || { });
    ajaxParam = {
      method: 'get',
      parameters: getParams,
      onFailure: function () {
        Stechat.flash('errors', "some errors occured.");
      },
      onSuccess: function (transport) {
        if (! Stechat.isBlank(transport.responseText)) {
          handlerResult = handler(JSON.parse(transport.responseText));
        }
        if (afterHandler) { afterHandler(handlerResult); }
      },
      onComplete: function () {
        $('loader_wheel').hide();
      }
    };
    $('loader_wheel').show();
    ajax = new Ajax.Request('get.php', ajaxParam);
    return false;
  },

  send: function () {
    var ajax,
    i = 0,
    k, keys = ['name', 'cont', 'room'],
    param = {
      method: 'post',
      parameters: { },
      onFailure: function () {
        Stechat.flash('errors', "some errors occured.");
      },
      onSuccess: function (transport) {
        if (transport.responseText.length > 0) {
          Stechat.flash('errors', 'Error: ' + transport.responseText);
        } else {
          Stechat.goToBottom(true);
          Stechat.startAutoReload();
          $('cont').value = '';
        }
      },
      onComplete: function () {
        $('loader_wheel').hide();
      }
    };
    $('loader_wheel').show();
    for (; i < keys.length; i++) {
      k = keys[i];
      param.parameters[k] = $F(k);
      if (k != 'name' && Stechat.validate(k, param.parameters[k])) {
        return false;
      }
    }
    ajax = new Ajax.Request('post.php', param);

    return false;
  },

  wipeOut: function () {
    var ajax,
    param = {
      method: 'post',
      parameters: { },
      onFailure: function () {
        Stechat.flash('errors', "some errors occured.");
      },
      onSuccess: function (transport) {
        if (transport.responseText.length > 0) {
          Stechat.flash('errors', 'Error: ' + transport.responseText);
        } else {
          Stechat.goToBottom(true);
          Stechat.startAutoReload();
        }
      },
      onComplete: function () {
        $('loader_wheel').hide();
      }
    };
    $('loader_wheel').show();
    param.parameters.room = $F('room');
    if (Stechat.validate('room', param.parameters.room)) {
      return false;
    }
    ajax = new Ajax.Request('wipe.php', param);

    return false;
  },

  startAutoReload: function () {
    if (Stechat.__reloader === null) {
      Stechat.__reloader = new PeriodicalExecuter(
        function (pe) {
          if (Ajax.activeRequestCount === 0) {
            Stechat.get(Stechat.renderer.appendToBottom,
                        function () {
                          Effect.ScrollTo('page_footer', { offset: -1 * $('page_header').clientHeight });
                          Stechat.flash('messages', Stechat.remoteFileSizeMsg(), false);
                        },
                        { m: Stechat.__fileSize, last: true });
          }
        }, 3);
    }
    $('auto_reload').checked = true;
    $('auto_reload_label').style.color = '#fff';
    $('auto_reload_label').style.backgroundColor = '#222';
  },

  stopAutoReload: function () {
    if (Stechat.__reloader !== null) {
      Stechat.__reloader.stop();
    }
    Stechat.__reloader = null;
    $('auto_reload').checked = false;
    $('auto_reload_label').style.color = '#222';
    $('auto_reload_label').style.backgroundColor = '#fff';
  },

  monitorScroll: function () {
    var root = document.documentElement, s, e,
    scrollTop = document.viewport.getScrollOffsets()[1];
    if (Stechat.__reloader === null && root.clientHeight < root.scrollHeight) {
      if (scrollTop === 0 &&
          Ajax.activeRequestCount === 0 && Stechat.__holdingContents.s > 1) {
        e = Stechat.__holdingContents.s - 1;
        Stechat.get(Stechat.renderer.insertBeforeTop,
                    function () { window.scrollTo(0, 1); },
                    { s: e + 1 - Stechat.SCROLL_UNIT, e: e });

      }
      if (scrollTop + root.clientHeight >= root.scrollHeight &&
          Ajax.activeRequestCount === 0) {
        if (Stechat.__hasTail) {
          Stechat.get(Stechat.renderer.appendToBottom,
                      function () { window.scrollTo(0, root.scrollHeight - root.clientHeight - 1); },
                      { m: Stechat.__fileSize, last: true });
        } else {
          s = Stechat.__holdingContents.e + 1;
          Stechat.get(Stechat.renderer.appendToBottom,
                      function (rendered) { window.scrollTo(0, root.scrollHeight - root.clientHeight - 1); },
                      { s: s, e: s - 1 + Stechat.SCROLL_UNIT });
        }
      }
    }
  },

  goToTop: function () {
    Stechat.stopAutoReload();
    Stechat.get(Stechat.renderer.replaceWhole,
                function () {
                  Stechat.flash('messages', Stechat.remoteFileSizeMsg());
                  window.scrollTo(0, 1);
                },
                { s: 1, e: Stechat.MAX_MSGS_LEN });
    return false;
  },

  goToBottom: function () {
    var continueReload = arguments[0] || false;
    if (! continueReload) { Stechat.stopAutoReload(); }
    Stechat.get(Stechat.renderer.replaceWhole,
                function () {
                  var root = document.documentElement;
                  window.scrollTo(0, root.scrollHeight - root.clientHeight - 1);
                  Stechat.flash('messages', Stechat.remoteFileSizeMsg());
                },
                { last: true });
    return false;
  },

  goToNumber: function () {
    var s = $F('num'),
    match = s.match(/([0-9]+)/);
    if (match) {
      s = parseInt(match[1], 10);
      Stechat.stopAutoReload();
      Stechat.get(Stechat.renderer.replaceWhole,
                  function () {
                    Stechat.flash('messages', Stechat.remoteFileSizeMsg());
                    window.scrollTo(0, 1);
                  },
                  { s: s, e: s + Stechat.MAX_MSGS_LEN });
    }
    return false;
  }

};

Event.observe(window, 'load',
              function () {
                $('loader_wheel').hide();
                if ($('utterance')) {
                  Event.observe('utterance', 'submit', Stechat.send);
                  Event.observe('cont', 'keydown',
                                function (e) {
                                  if ((window.event && window.event.keyCode || e.which) == 13 && e.shiftKey) {
                                    Stechat.send();
                                    return false;
                                  }
                                  return false;
                                });
                  Stechat.goToBottom();
                  Stechat.startAutoReload();

                  Event.observe('top_link', 'click', Stechat.goToTop);
                  Event.observe('bottom_link', 'click', Stechat.goToBottom);
                  Event.observe('jump', 'click', Stechat.goToNumber);
                  Event.observe('num', 'keydown',
                                function (e) {
                                  if ((window.event && window.event.keyCode || e.which) == 13) {
                                    Stechat.goToNumber();
                                    return false;
                                  }
                                  return false;
                                });
                  Event.observe('wipeout', 'click', Stechat.wipeOut);

                  Event.observe('auto_reload', 'click', function () {
                                  if ($('auto_reload').checked) {
                                    Stechat.goToBottom();
                                    Stechat.startAutoReload();
                                  } else {
                                    Stechat.stopAutoReload();
                                  }
                                });

                  Stechat.__scrollMonitor = new PeriodicalExecuter(Stechat.monitorScroll, 0.5);
                }
                return false;
              });
