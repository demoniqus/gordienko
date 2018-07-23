

auction = function(params){
    var _self = {
        params: params,
        htmlForm: null,
        getHTMLForm: function(){
            /*Метод рисует форму для отображения аукциона на странице*/
            var style = '';
            var conte = $('<div class="auction category-item"></div>');
            this.htmlForm = conte;
            
            style = 'font-size: 140%; line-height: 140%;';
            conte.append('<div style="' + style + '">' + this.params.Name + '</div>');
            
            style = 'font-size: 80%;';
            conte.append('<div style="' + style + '" class="last-sync-date"></div>');
            this.updateSyncDate();
            
            var optionsPanel = $('<div class="category-item-options-panel"></div>');
            conte.append(optionsPanel);
            
            optionsPanel.append(this.getSyncForm());
            var imgSize = 48;
            if (this.params.UsedAuthorize) {
                imgSize = 60;
            }
            var option = $('<div class="category-item-option" title="Список лотов аукциона"><img src="pict/camaro_128.png" style="width: ' + imgSize + 'px; height: ' + imgSize + 'px;" /> </div>');
            optionsPanel.append(option);
            option.click(function(){ _self.getLotsList(); });
            
            var option = $('<div class="category-item-option" title="Настройка загружаемых параметров лотов аукциона"><img src="pict/wheel_128.png" style="width: ' + imgSize + 'px; height: ' + imgSize + 'px;" /> </div>');
            optionsPanel.append(option);
            option.click(function(){ _self.getParamsList(); });
            
            conte.append('<div class="sync-state" ></div>');
            
            return conte;
        },
        getSyncForm: function(){
            var _self = this;
            var form  = $('<div class="category-item-option"></div>');
            var imgSize = 48;
            if (this.params.UsedAuthorize) {
                /*Аукцион требует авторизации*/
                imgSize = 32;
                form.append('<div style="padding-left: 24px; position: relative; min-height: 24px; box-sizing: border-box;"><img src="pict/user_128.png" style="position: absolute; width: 18px; height: 18px; left: 3px; top: 0px;" /><input type="text" style="width: 95%;" name="login" id="login" /></div>')
                form.append('<div style="padding-left: 24px; position: relative; min-height: 24px; box-sizing: border-box;"><img src="pict/key_128.png" style="position: absolute; width: 18px; height: 18px; left: 3px; top: 0px;" /><input type="password" style="width: 95%;" name="pass" id="pass" /></div>')
            }
            
            form.append('<div style="text-align: center"><img src="pict/sync_128.png" style="width: ' + imgSize + 'px; height: ' + imgSize + 'px;"  title="Синхронизировать с аукционом" class="sync-btn" /> </div>');
            form.find('.sync-btn:first').click(function(){
                _self.beginSync();
            });
            return form;
        },
        getLotsList: function(){
            /*Получим список лотов для данного аукциона*/
            var _self = this;
            $.ajax({
                url: 'index.php?mode=data&datakey=lots_list',
                type: 'POST',
                data: {IdAuction: _self.params.IdAuction},
                success: function(data){
                    if (!data) {
                        return;
                    }
                    try {
                        data = JSON.parse(data.replace(/^\s+/ig, '').replace(/\s+$/ig, ''));
                    }
                    catch (e) {
                        return;
                    }
                    
                    var dataPanel = $('.data-panel:first');
                    dataPanel.empty();
                    
                    var funcLotAppend = function(data){
                        if (data.records && data.records instanceof Array) {
                            linq(data.records).foreach(function(record){
                                linq(record).foreach(function(v, k){
                                    typeof v === typeof 'aaa' && (record[k] = Base64.decode(v));
                                });
                                var instance = new window['lot'](record);
                                dataPanel.append(instance.getHTMLForm());
                            });
                        }
                    };
                    
                    var lot_filter = new filter({
                        fields: [
                            {
                                caption: 'VIN',
                                type: 'text',
                                id: 'VIN'
                            },
                            {
                                caption: '№ лота',
                                type: 'text',
                                id: 'KeyLot'
                            },
                            {
                                caption: 'Дата реализации',
                                type: 'dateinterval',
                                id: 'SaleDate'
                            }
                        ],
                        change: function(data){
                            if (!data || !linq(data).firstKey(function(v,k){return true;})) {
                                return;
                            }
                            data.KeyLot && (data.KeyLot = Base64.encode(data.KeyLot));
                            data.VIN && (data.VIN = Base64.encode(data.VIN));
                            $.ajax({
                                url: 'index.php?mode=data&datakey=lots_list',
                                type: 'POST',
                                data: {
                                    filter: data,
                                    IdAuction: _self.params.IdAuction
                                },
                                success: function(data){
                                    if (!data) {
                                        return;
                                    }
                                    try {
                                        data = JSON.parse(data.replace(/^\s+/ig, '').replace(/\s+$/ig, ''));
                                    }
                                    catch (e) {
                                        return;
                                    }
                                    var dataPanel = $('.data-panel:first');
                                    dataPanel.children('.category-item').remove();

                                    funcLotAppend(data);
                                }
                            });
                        }
                    });
                    dataPanel.append(lot_filter.htmlForm);
                    
                    dataPanel.append('<div class="category-name">Лоты аукциона ' + _self.params.Name + '</div>');
                    funcLotAppend(data);
                    

                }
            });
        },
        getParamsList: function(){
            /*Получим список лотов для данного аукциона*/
            var _self = this;
            $.ajax({
                url: 'index.php?mode=data&datakey=auction_params_list',
                type: 'POST',
                data: {IdAuction: _self.params.IdAuction},
                success: function(data){
                    if (!data) {
                        return;
                    }
                    try {
                        data = JSON.parse(data.replace(/^\s+/ig, '').replace(/\s+$/ig, ''));
                    }
                    catch (e) {
                        return;
                    }
                    var dataPanel = $('.data-panel:first');
                    dataPanel.empty();
                    dataPanel.append('<div class="category-name">Параметры лотов аукциона ' + _self.params.Name + '</div>');
                    /*Выводим список аукционов*/
                    if (data.records && data.records instanceof Array) {
                        linq(data.records).foreach(function(record){
                            linq(record).foreach(function(v, k){
                                typeof v === typeof 'aaa' && (record[k] = Base64.decode(v));
                            });
                            var instance = new window['auct_lot_param'](record);
                            dataPanel.append(instance.getHTMLForm());
                        });
                    }

                }
            });
        },
        beginSync: function(){
            var login = null;
            var pass = null;
            if (this.params.UsedAuthorize) {
                login = this.htmlForm.find('#login:first').val();
                pass = this.htmlForm.find('#pass:first').val();
            }
            var _self = this;
            /*Сбросим состояние информационного контейнера*/
            _self.htmlForm.find('.sync-state:first').empty();
            /*Получим список лотов, которые требуется синхронизировать*/
            $.ajax({
                url: 'index.php?mode=data&datakey=lots_sync_list',
                type: 'POST',
                data: {'IdAuction': this.params.IdAuction},
                success: function(data){
                    if (!data) {
                        return;
                    }
                    try {
                        data = JSON.parse(data.replace(/^\s+/ig, '').replace(/\s+$/ig, ''));
                    }
                    catch (e) {
                        return;
                    }
                    _self.synchronize(data.auction_lotes_list, function(syncLotList, errorLots){
                        _self.htmlForm.find('.sync-state:first').empty().append(
                            'Синхронизировано лотов ' + (syncLotList.length - errorLots.length) + 
                            (errorLots.length > 0 ?
                                '<br /><span style="color:#f66;">Не синхронизированы лоты ' + join(', ', errorLots) + '</span>'
                                : '')
                        );
                        _self.update();
                    });
                }
            });
        },
        synchronize: function(syncLotList, callback){
            /*Начинаем синхронизацию лотов*/
            if (_self.params.SyncFromFrame) {
                /*Синхронизация через фрейм*/
                synchronizer.synchronize(
                    syncLotList, 
                    _self.params, 
                    _self.htmlForm ? _self.htmlForm.find('.sync-state:first') : $('<div></div>'), 
                    callback
                );
            }
            else {
                var index = 0;
                var errorLots = [];
                var syncFunc = function(){
                    clearTimeout(syncFunc.timeout);
                    var lot = syncLotList[index];
                    if (!lot) {
                        /*Все заявленные лоты синхронизированы*/
                        if (typeof callback === typeof function(){}) {
                            callback(syncLotList, errorLots);
                        }
                        return;
                    };
                    $.ajax({
                        url: lib.lotSyncDataUrl,
                        type: 'POST',
                        data: lot,
                        success: function(data){
                            var message = 'Синхронизирован лот ' + Base64.decode(lot.Key);
                            if (typeof data === typeof 'aaa') {
                                data = JSON.parse(data.replace(/^\s+/, '').replace(/\s+$/, ''));
                            }
                            if (
                                    typeof {} === typeof data && 
                                    data &&
                                    data.syncStatus === false
                                ) {
                                message = '<span style="color:#f66;">Лот ' + Base64.decode(lot.Key) + ' не синхронизирован: ' + data.message + '</span>';
                                errorLots.push(Base64.decode(data.key));
                            }

                            index++;
                            _self.htmlForm.find('.sync-state:first').empty().append(
                                    message + ' (' + index + ' из ' + syncLotList.length + ')'
                            );
                            /*Чтобы не делать кучу последовательных запросов, поставим небольшой интервал ожидания в пределах нескольких секунд*/
                            var time = _self.getRequestTimeout();
                            syncFunc.timeout = setTimeout(syncFunc, time);
                        }
                    });
                };
                /*первый запрос выполняем незамедлительно*/
                syncFunc.timeout = setTimeout(syncFunc, 0);
            }
        },
        getRequestTimeout: function(){
            /*Таймаут в JS задается в миллисекундах*/
            var multiple = 1000;
            /*Установим разумные границы для запроса, чтобы он не был слишком частым или редким*/
            var maxTime = 5;
            var minTime = 2;
            var t = Math.random() * 10;
            while (t < minTime || t > maxTime) {
                t = Math.random() * 10;
            }
            return t * multiple;
        },
        update: function(){
            /*Получим информацию обо всех доступных аукционах и выберем среди них нужный*/
            var _self = this;
            $.ajax({
                url: 'index.php?mode=data&datakey=auctiones_list',
                success: function(data){
                    try {
                        data = JSON.parse(data.replace(/^\s+/ig, '').replace(/\s+$/ig, ''));
                    }
                    catch (e) {
                        return;
                    }
                    _self.params = linq(linq(data.records).first(function(r){ return r.IdAuction === _self.params.IdAuction; }))
                        .select(function(v, k){
                            return typeof v === typeof 'aaa' ? Base64.decode(v) : v;
                        }).collection;
                    _self.updateSyncDate();
                }
            });
        },
        updateSyncDate: function(){
            var conte = this.htmlForm.find('.last-sync-date:first');
            if (this.params.DateLastSync) {
                var d = new Date(this.params.DateLastSync.date);
                var dateString = d.getHours() + ':' + d.getMinutes() + ':' + d.getSeconds() +
                    ' ' + (d.getDate() < 10 ? '0' : '') + d.getDate() + '.' + (d.getMonth() < 9 ? '0' : '') 
                    + (d.getMonth() + 1) + '.' + d.getFullYear();
                conte.html('синхронизирован ' + dateString);
            }
            else {
                
                conte.html('не синхронизирован');
            }
        }
    };
    
    if (!('auctions_list' in window)) {
        window.auctions_list = {};
    }
    /*Запишем данный аукцион, чтобы иметь возможность обращения к нему извне*/
    window.auctions_list[params.Name] = _self;
    
    return _self;
};

auction.auctions_list = {};

/*Данный компонент отвечает за синхронизацию лотов через использование фреймов / всплывающих окон*/
synchronizer = {
    index: 0,
    frame: null,
    frameWindow: null,
    timers: {},
    state_panel: null,
    auction_params: null,
    syncLotList: null,
    syncErrorsList: [],
    callback: null,
    synchronize: function( syncLotList, auction_params, state_panel, callback){
        this.index = 0;
        this.callback = callback;
        this.auction_params = auction_params;
        this.state_panel = state_panel;
        this.syncLotList = syncLotList;
        this.state_panel.empty();
        this.next();
    },
    next: function(){
        if (this.index < this.syncLotList.length) {
            var lot = this.syncLotList[this.index];
            /*Вызываем загрузку фрейма*/
            this.getFrame(lot);
            if (!this.frameWindow) {
                alert('Настройки безопасности не позволяют открыть окно для синхронизации лота. Синхронизация невозможна.');
                return;
            }
            /*Теперь ждем, пока фрейм загрузится и получит первое сообщение, задающее ссылку на текущее окно*/
            var _self = this;
            var f = function(){
                clearTimeout(_self.timers.windows_connect);
                _self.frameWindow.postMessage({callback: 'sync.setParentWindow', 'lot': lot}, '*');
                _self.timers.windows_connect = setTimeout(f, 250);
            };
            this.timers.windows_connect = setTimeout(f, 250);
            this.state_panel.empty().append('Синхронизация лота ' + Base64.decode(lot.Key) + ': установка соединения с источником');
        }
        else {
            /*Синхронизация завершена*/
            if (this.frame) {
                this.frame.parentNode.removeChild(this.frame);
                this.frame = null;
                this.frameWindow = null;
            }
            else if (this.frameWindow) {
                this.frameWindow.close();
                this.frameWindow = null;
            }
            if (typeof function(){} === typeof this.callback) {
                this.callback(this.syncLotList, this.syncErrorsList);
            }
            
        }
    },
    getFrame: function(lot){
        /*
         * Если в каком-то аукционе появится запрет на открытие страницы во фрейме, можно
         * переделать этот метод на открытие дополнительного окна. Но браузер может
         * начать блокировать множественные всплывающие окна.
         * Кроме того, потребуется обеспечить закрытие данных окон после использования.
         */
        if (this.frame) {
            this.frame.parentNode.removeChild(this.frame);
            this.frame = null;
        }
        else if (this.frameWindow) {
            this.frameWindow.close();
            this.frameWindow = null;
        }
        var src = this.auction_params.BaseLotUrl.replace('{KeyLot}', Base64.decode(lot.Key));
        if (this.auction_params.SyncFromFrame) {
            this.frame = document.createElement('iframe');
            this.frame.id = 'loader';
            document.body.appendChild(this.frame);
            this.frame.src = src;
            this.frameWindow = this.frame.contentWindow;
        }
        else if (this.auction_params.SyncFromPopupWindow) {
            this.frameWindow = window.open(src);
        }
        
    },
    stop_timer: function(data){
        switch(data.data.timer) {
            case 'windows_connect':
                /*Соединение установлено. останавливаем таймер.*/
                clearTimeout(this.timers.windows_connect);
                this.state_panel.empty().append('Синхронизация лота ' + Base64.decode(this.syncLotList[this.index].Key) 
                        + ': соединение с источником установлено. Ожидается передача данных.');
                break;
        }
    },
    saveData: function(data){
        var _self = this;
        var keyLot = Base64.decode(_self.syncLotList[this.index].Key);
        this.state_panel.empty().append('Синхронизация лота ' + keyLot 
                + ': Данные получены. Подготовка к сохранению.');
        var sData = linq(_self.syncLotList[this.index])
            .reduce(function(res, v, k){
                res[k] = v;
                return res;
            }, {});
        sData.lotImages = data.data.lotImages;
        sData.lotParams = data.data.lotParams;
        $.ajax({
            url: lib.lotSyncDataUrl,
            type: 'POST',
            data: sData,
            success: function(data){
                var message = 'Синхронизирован лот ' + keyLot;
                if (typeof data === typeof 'aaa') {
                    data = JSON.parse(data.replace(/^\s+/, '').replace(/\s+$/, ''));
                }
                if (
                        typeof {} === typeof data && 
                        data &&
                        data.syncStatus === false
                    ) {
                    message = '<span style="color:#f66;">Лот ' + keyLot + ' не синхронизирован: ' + data.message + '</span>';
                    _self.syncErrorsList.push(keyLot);
                }

                _self.index++;
                _self.state_panel.empty().append(
                        message + ' (' + _self.index + ' из ' + _self.syncLotList.length + ')'
                );
                /*
                 * В данном случае мы потратили время на загрузку страницы, на загрузку картинок сервером,
                 * поэтому нет смысла еще тратить время на ожидание
                 */
                _self.next();
            }
        });
    }
    
    
    
    
};
