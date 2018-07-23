

function postMessager(data) {
    if (!data) {
        return;
    }
    var _data = {data: data.data, original: data};
    if (typeof data.data === typeof 'aaa') {
        _data.data = JSON.parse(data.data);
    }
    /*Вычисляем callback*/
    if (_data.data.callback) {
        var callback = _data.data.callback.split('.');
        var context = window;
        if (callback.length > 1) {
            context = window[callback[0]];
        }
        callback = linq(callback).reduce(function(res, key){
            if (res && key in res) {
                res = res[key];
            }
            return res;
        }, window);
        if (callback && typeof function(){} === typeof callback) {
            callback.call(context, _data);
        }
    }
}

if (window.addEventListener) {
    window.addEventListener('message', postMessager);
}
else {
    window.attachEvent('onMessage', postMessager);
}