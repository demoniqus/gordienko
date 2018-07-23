


function injectScript(file, node) {
    
    var th = document.getElementsByTagName(node)[0];
    var s = document.createElement('script');
    s.setAttribute('type', 'text/javascript');
    s.setAttribute('src', file);
    th.appendChild(s);
}

/*В зависимости от того, на каком домене сейчас находимся, организуем работу скрипта*/
var mode = null;
if (/^https?:\/\/[^\/]*(copart|iaai)/i.test(document.location.href)) {
    mode = 'auction_site';
}
else if (/^https?:\/\/[^\/]*(gordienko|usgm)/i.test(document.location.href)) {
    mode = 'root_site';
}

if (mode !== null) {
    /*Устанавливаем возможность обмена сообщениями между окнами*/
    injectScript(chrome.extension.getURL('/postmessager.js'), 'body');
}
if (mode === 'auction_site') {
    injectScript( chrome.extension.getURL('/linq.js'), 'body');
    injectScript( chrome.extension.getURL('/Base64.js'), 'body');
    injectScript( chrome.extension.getURL('/synchronizer.js'), 'body');
    
}