

function postMessager(data) {
    if (!data) {
        return;
    }
    var _data = {data: data.data, original: data};
    if (typeof data.data === typeof 'aaa') {
        try {
            _data.data = JSON.parse(data.data);
        } catch (e) {
            console.log('JSON parse error ', data.data);
            throw 'Не удалось преобразовать данные из строки в формат JSON. Данные: ' + data.data;
        }
    }
    var context = window;
    if (_data.data.contextCallback) {
        var context = _data.data.contextCallback.split('.');
        context = linq(context).reduce(function(res, key){
            if (res && key in res) {
                res = res[key];
            }
            return res;
        }, window);
        
    }
    /*Вычисляем callback*/
    if (_data.data.callback) {
        var callback = _data.data.callback.split('.');
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

