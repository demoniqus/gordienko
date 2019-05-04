

auction = function(params){
    var _self = {
        params: params,
        htmlForm: null,
        getHTMLForm: function(){
            /*Метод рисует форму для отображения аукциона на странице*/
            var style = '';
            if (!this.htmlForm) {
                this.htmlForm = $('<div class="auction category-item"></div>');
                this.htmlForm.get(0).categoryItem = this;
            }
            
            var conte = this.htmlForm;
            conte.empty();
            
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
            option.click(function(){ 
                !_self.disable && _self.getLotsList(); 
            });
            
            var option = $('<div class="category-item-option" title="Настройка загружаемых параметров лотов аукциона"><img src="pict/car_doc_128.png" style="width: ' + imgSize + 'px; height: ' + imgSize + 'px;" /> </div>');
            optionsPanel.append(option);
            option.click(function(){ 
                !_self.disable && _self.getParamsList(); 
            });
            
            var option = $('<div class="category-item-option" title="Создать новый лот"><img src="pict/camaro_add_128.png" style="width: ' + imgSize + 'px; height: ' + imgSize + 'px;" /> </div>');
            optionsPanel.append(option);
            option.click(function(){ 
                !_self.disable && lot.new(_self, function(classPrototype, data){
                    /*Здесь не нужно вызывать создание формы лота*/
                });
            });
            
            var option = $('<div class="category-item-option" title="Удаление всех лотов аукциона"><img src="pict/broom_128.png" style="width: ' + imgSize + 'px; height: ' + imgSize + 'px;" /> </div>');
            optionsPanel.append(option);
            option.click(function(){ 
                var _self = $(this).parents('.category-item:first').get(0).categoryItem;
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
                        
                        if (confirm('Вы уверены, что хотите удалить все лоты аукциона без возможности их восстановления?')) {
                            /*
                             * Команда на очистку всех лотов аукциона - блокируем все манипуляции 
                             * с данным экземляром аукциона до полного выполнения операции. Хотя
                             * это не спасет, если пользователь заново перестроит список аукционов
                             * или пойдет напрямую в список аукционов.
                             */
                            _self.disable = true;
                            $.ajax({
                                url: 'index.php?mode=data&datakey=auction_clear',
                                type: 'POST',
                                data: {
                                    ObjectType: 'lot_list', 
                                    ObjectsIdList: linq(data.records).select(function(rec){
                                        return rec.IdLot;
                                    }).collection,
                                    IdAuction: _self.params.IdAuction
                                },
                                success: function(data){
                                    if (typeof 'aaa' === typeof data) {
                                        data = JSON.parse(data);
                                    }
                                    if (data && data.success) {
                                        alert('Все лоты аукциона удалены!');
                                    }
                                    delete _self.disable;
                                }
                            });
                        }
                    }
                });
                
            });
            
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
            
            form.append('<div style="text-align: center"><img src="pict/sync_force_128.png" style="width: ' + imgSize + 'px; height: ' + imgSize + 'px;"  title="Синхронизировать с аукционом" class="sync-btn" /> </div>');
            form.find('.sync-btn:first').click(function(){
                _self.beginSync();
            });
            return form;
        },
        funcLotAppend: function(data){
            if (data.records && data.records instanceof Array) {
                linq(data.records).foreach(function(record){
                    linq(record).foreach(function(v, k){
                        typeof v === typeof 'aaa' && (record[k] = Base64.decode(v));
                    });
                    var instance = new lot(record);
                    var to = setTimeout(function(){
                        /*Чтобы не было долго зависания из-за прорисовки списка лотов, вынесем append в отдельный поток*/
                        clearTimeout(to);
                        $(dataPanel).append(instance.getHTMLForm());
                    }, 0);
                });
            }
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
                    
                    $(dataPanel).empty();
                    
                    auctionLotsListFilter(_self);
                    
                    $(dataPanel).append('<div class="category-name">Лоты аукциона ' + _self.params.Name + '</div>');
                    _self.funcLotAppend(data);
                    

                }
            });
        },
        getParamsList: function(){
            /*Получим список параметров лотов для данного аукциона*/
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
                    $(dataPanel).empty();
                    
                    
                    auctionParamsListFilter(_self);
                    
                    $(dataPanel).append('<div class="category-name">Параметры лотов аукциона ' + _self.params.Name + '</div>');
                    var table = $('<table class="table-category-items"></table>');
                    table.append('<tr><th class="cell35">Наименование</th><th class="cell30">Подпись</th><th class="cell20">Комментарий</th><th class="cell10">Порядок сортировки при выводе</th><th class="cell5">Видимость параметра</th></tr>');
                    table.append(
                        '<tr><th class="cell35">' + 
                        '<img src="pict/sort_asc.png" class="sort-icon" param="Name" sorttype="asc"/>' + 
                        '<img src="pict/sort_desc.png" class="sort-icon" param="Name" sorttype="desc" />' + 
                        '</th><th class="cell30">' + 
                        '<img src="pict/sort_asc.png" class="sort-icon" param="Caption" sorttype="asc"/>' + 
                        '<img src="pict/sort_desc.png" class="sort-icon" param="Caption" sorttype="desc" />' +
                        '</th><th class="cell20"></th>' + 
                        '<th class="cell10">' + 
                        '<img src="pict/sort_asc.png" class="sort-icon" param="OrderNum" sorttype="asc"/>' + 
                        '<img src="pict/sort_desc.png" class="sort-icon" param="OrderNum" sorttype="desc" />' +
                        '</th><th class="cell5">' + 
                        '<img src="pict/sort_asc.png" class="sort-icon" param="Visible" sorttype="asc"/>' + 
                        '<img src="pict/sort_desc.png" class="sort-icon" param="Visible" sorttype="desc" />' +
                        '</th></tr>');
                    table.find('.sort-icon').click(function(){
                        var field = this.getAttribute('param');
                        var sorttype = this.getAttribute('sorttype');
                        var rows = linq(table.find('tr.category-item')).orderBy(function(a, b){
                            a = a.categoryItem.params[field];
                            b = b.categoryItem.params[field];
                            return a < b ? -1 : a > b ? 1 : 0;
                        }).collection;
                        if (rows && rows instanceof jQuery) {
                            if (sorttype.toLowerCase() === 'desc') {
                                rows = rows.toArray().reverse();
                            }
                            table.append(
                                rows
                            );
                        }
                    });
                    
                    $(dataPanel).append(table);
                    /*Выводим список аукционов*/
                    if (data.records && data.records instanceof Array) {
                        linq(data.records).foreach(function(record){
                            linq(record).foreach(function(v, k){
                                typeof v === typeof 'aaa' && (record[k] = Base64.decode(v));
                            });
                            var instance = new window['auct_lot_param'](record);
                            table.append(instance.getHTMLForm('tablerow'));
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
                        let message;
                        if (errorLots.length) {
                            message = 'Ошибки при синхронизации <span style="color: #f66;">' + errorLots.join(', ') + '</span>';
                        }
                        else {
                            message = 'Синхронизировано лотов ' + syncLotList.length;
                        }
                        _self.htmlForm.find('.sync-state:first').empty().append(message);
                        _self.update();
                    });
                }
            });
        },
        synchronize: function(syncLotList, callback, params){
            /*Начинаем синхронизацию лотов*/
            if (_self.params.SyncFromFrame || _self.params.SyncFromPopupWindow) {
                if (!window.postMessager || typeof function(){} !== typeof window.postMessager) {
                    /*
                     * Если между окнами не установлена связь посредством postMessage,
                     * то синхронизация невозможна
                     */
                    alert('Синхронизация аукциона ' + _self.params.name + ' не может быть выполнена, из-за отстутствия необходимых возможностей браузера.');
                    return;
                }
                /*Синхронизация через фрейм*/
                !('listSync' in window) && (window.listSync = {});//Создаем список активных синхронизаторов
                var auctionName = _self.params.Name.toLowerCase();
                window.listSync[auctionName] = new synchronizer(_self.params);
                switch (auctionName) {
                    case 'copart':
                        /*Для этого аукциона мы данные пока просто берем напрямую без авторизации*/
                        window.listSync[auctionName].synchronize(
                            syncLotList, 
                            _self.htmlForm ? _self.htmlForm.find('.sync-state:first') : $('<div></div>'), 
                            callback,
                            params
                        );
                        break;
                    case 'iaai':
                        /*Для этого аукциона нужно сначала авторизоваться и загрузить список лотов, из которых пользователь выберет те, которые подлежат синхронизации*/
                        if (confirm('Вы имеете активный авторизованный сеанс на данном аукционе?')) {
                            window.listSync[auctionName].loadLotsList_iaai(
                                _self.htmlForm ? _self.htmlForm.find('.sync-state:first') : $('<div></div>'), 
                                callback,
                                params
                            );
                        }
                        
                        break;
                }
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
                    /*Из аукциона синхронизация производится с перезаписью всей информации о лоте*/
                    lot.forceSync = true;
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
                /*Здесь дата находится в формате SQL*/
                var d = lib.MySQLDateToDate(this.params.DateLastSync.date);
                var dateString = (d.getHours() < 10 ? '0' : '') + d.getHours() + ':' + 
                                 (d.getMinutes() < 10 ? '0' : '') + d.getMinutes() + ':' + 
                                 (d.getSeconds() < 10 ? '0' : '') + d.getSeconds() + ' ' + 
                                 (d.getDate() < 10 ? '0' : '') + d.getDate() + '.' + 
                                 (d.getMonth() < 9 ? '0' : '') +
                                 (d.getMonth() + 1) + '.' + d.getFullYear();
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



/*Данный компонент отвечает за синхронизацию лотов через использование фреймов / всплывающих окон*/
synchronizer = function (auction_params) {
    var _self = {
        index: 0,
        frame: null,
        frameWindow: null,
        timers: {},
        state_panel: null,
        auction_params: auction_params,
        syncLotList: null,
        syncErrorsList: [],
        callback: null,
        errors: {},
        afterSave: function(){},
        connectionTimeoutTime: function(){
            /*Функция возвращает предельное время ожидания поключения к окну-источнику данных*/
            return 4 * //минуты
            60 * // секунды
            1000; // миллисекунды
        },
        synchronize: function(syncLotList, state_panel, callback){
            this.index = 0;
            this.callback = callback;
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
                    if ((new Date()).getTime() - _self.timers.windows_connect_start_time > _self.connectionTimeoutTime())
                    {
                        if (confirm('Не удается подключиться к источнику данных для синхронизации лота. Остановить процесс подключения?')) {
                            try {
                                _self.frameWindow && _self.frameWindow.close();
                                _self.frameWindow = null;
                                _self.frame && (_self.frame.parentNode.removeChild(_self.frame), _self.frame = null);
                                _self.state_panel.empty()
                            }catch (e) {}
                            return;
                        }
                        else {
                            _self.timers.windows_connect_start_time = (new Date()).getTime();
                        }
                    }
                    _self.frameWindow.postMessage({
                        timerName: 'windows_connect',
                        callback: 'sync.setParentWindow',
                        contextCallback: 'sync',
                        'lot': lot,
                        'auction': _self.auction_params
                    }, '*');
                    _self.timers.windows_connect = setTimeout(f, 250);
                };
                this.timers.windows_connect = setTimeout(f, 250);
                this.timers.windows_connect_start_time = (new Date()).getTime();

                // let self = this;
                // setTimeout(function(){
                //     console.log(self.frame.contentDocument || self.frame.contentWindow.document);
                // }, 6000);



                this.state_panel.empty().append('Синхронизация лота ' + Base64.decode(lot.Key) + ': установка соединения с источником');
            }
            else {
                /*Синхронизация завершена*/
                this.closeFrame();
                if (typeof function(){} === typeof this.callback) {
                    this.callback(this.syncLotList, this.syncErrorsList);
                }

            }
        },
        getFrame: function(lot){
            this.closeFrame();
            var lotKey = lot._key ? 
                (
                    typeof function(){} === typeof lot._key ? lot._key.call(lot) : lot._key
                ) : 
                Base64.decode(lot.Key);
            var src = this.auction_params.BaseLotUrl.replace('{KeyLot}', lotKey);
            if (this.auction_params.SyncFromFrame) {
                this.frame = document.createElement('iframe');
                this.frame.id = 'loader';
                this.frame.src = src;
                document.body.appendChild(this.frame);
                /*Чтобы получить ссылку на contentWindow, надо сначала внедрить фрейм в body и позволить ему начать загружаться*/
                this.frameWindow = this.frame.contentWindow;
            }
            else if (this.auction_params.SyncFromPopupWindow) {
                this.frameWindow = window.open(src);
                window.focus()
            }
        },
        closeFrame: function(){
            if (this.frame) {
                /*Если синхронизация производилась через фрейм*/
                this.frame.parentNode.removeChild(this.frame);
                this.frame = null;
                this.frameWindow = null;
            }
            else if (this.frameWindow) {
                /*Если синхронизация производилась через новое окно / вкладку браузера*/
                this.frameWindow.close();
                this.frameWindow = null;
            }
        },
        stop_timer: function(data){
            var canDeleteTimer = false;
            switch(data.data.timer) {
                case 'windows_connect':
                    /*Соединение установлено. Останавливаем таймер.*/
                    clearTimeout(this.timers.windows_connect);
                    this.state_panel.empty().append('Синхронизация лота ' + Base64.decode(this.syncLotList[this.index].Key) 
                            + ': соединение с источником установлено. Ожидается передача данных.');
                    canDeleteTimer = true;
                    break;
                case 'windows_connect_lot_list':
                    /*Соединение установлено. Останавливаем таймер.*/
                    clearTimeout(this.timers.windows_connect_lot_list);
                    this.state_panel.empty().append('Загрузка списка лотов для синхронизации.');
                    canDeleteTimer = true;
                    break;
            }
            if (canDeleteTimer) {
                delete this.timers[data.data.timer];
            }
        },
        saveData: function(data){
            let importErrorsHandler = function(errors) {
                return linq(errors)
                    .select(function(errorData){
                        return 'Адрес на сайте аукциона ' + errorData.path + '<br />Текст ошибки ' + (errorData.error || '');
                    }).collection.join('<hr /><br />');
            };
            var _self = this;
            var keyLot = Base64.decode(_self.syncLotList[this.index].Key);
            this.state_panel.empty().append('Синхронизация лота ' + keyLot 
                    + ': Данные получены. Подготовка к сохранению.');
            var sData = linq(_self.syncLotList[this.index])
                .reduce(function(res, v, k){
                    res[k] = v;
                    return res;
                }, {});
            sData = this.savedData(sData, data);
            let errors = data.data.errors && data.data.errors.length ? data.data.errors : null;
            if (errors) {
                _self.errors[keyLot] = errors;
            }
            /*Из аукциона синхронизация производится с перезаписью всей информации о лоте*/
            sData.forceSync = true;
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
                        _self.syncErrorsList.push(errors ? importErrorsHandler(errors) : keyLot);
                    }
                    typeof function(){} === typeof _self.afterSave && _self.afterSave(_self.syncLotList[_self.index]);
                    _self.index++;

                    message += ' (' + _self.index + ' из ' + _self.syncLotList.length + ')'
                    if (errors) {
                        _self.syncErrorsList.push(keyLot);
                        _self.syncErrorsList.push(importErrorsHandler(errors));
                        message += '<br /><span style="color:#f66;">При синхронизации лота ' + keyLot + ' возникли ошибки: <br /> ';

                        message += importErrorsHandler(errors);

                        message += '</span>';
                    }
                    _self.state_panel.empty().append(message);
                    /*
                     * В данном случае мы потратили время на загрузку страницы, на загрузку картинок сервером,
                     * поэтому нет смысла еще тратить время на ожидание
                     */
                    _self.next();
                }
            });
        }
    };
    
    /*Дополним специфичными полями, характерными для конкретного аукциона*/
    var specific = new window['synchronizer_' + auction_params.Name.toLowerCase()](auction_params);
    for (var key in specific) {
        _self[key] = specific[key];
    }
    return _self;
};

synchronizer_iaai = function(auction_params){
    return {
        listLotsFrame: null,
        listLotsFrameWindow: null,
        previewForm: null,
        additionalParams: null,
        loadLotsList_iaai: function(state_panel, callback, params){
            /*
             * Метод создает фрейм, из которого можно получить список лотов для синхронизации
             */
            this.index = 0;
            this.callback = callback;
            this.state_panel = state_panel;
            this.state_panel.empty().append('Идет подготовка к загрузке списка лотов для синхронизации...');
            /*Загрузим фрейм со списком лотов*/
            var src = '';
            this.additionalParams = params || null;
            if (params && params.mode === 'singleLot' && params.lot) {
                src = 'https://www.iaai.com/PurchaseHistory/Default?FAVVIN=' + (params.lot.VIN || 'null');
            }
            else {
                src = 'https://www.iaai.com/PurchaseHistory';
            }
            if (auction_params.SyncFromFrame) {
                this.listLotsFrame = document.createElement('iframe');
                this.listLotsFrame.id = 'loader';
                $(this.listLotsFrame).addClass('loader');
                this.listLotsFrame.src = src;
                document.body.appendChild(this.listLotsFrame);
                /*Чтобы получить ссылку на contentWindow, надо сначала внедрить фрейм в body и позволить ему начать загружаться*/
                this.listLotsFrameWindow = this.listLotsFrame.contentWindow;
            }
            else if (auction_params.SyncFromPopupWindow) {
                this.listLotsFrameWindow = window.open(src);
                window.focus();
            }
            
            /*Теперь ждем, пока фрейм загрузится и получит первое сообщение, задающее ссылку на текущее окно*/
            var _self = this;
            var f = function(){
                clearTimeout(_self.timers.windows_connect_lot_list);
                if ((new Date()).getTime() - _self.timers.windows_connect_lot_list_start_time > _self.connectionTimeoutTime())
                {
                    if (confirm('Не удается подключиться к аукциону для загрузки списка лотов для синхронизации. Остановить процесс подключения?')) {
                        try {
                            _self.listLotsFrameWindow && _self.listLotsFrameWindow.close();
                            _self.listLotsFrameWindow = null;
                            _self.listLotsFrame && (_self.listLotsFrame.parentNode.removeChild(_self.listLotsFrame), _self.listLotsFrame = null);
                            _self.state_panel.empty();
                        }catch (e) {}
                        return;
                    }
                    else {
                        _self.timers.windows_connect_lot_list_start_time = (new Date()).getTime();
                    }
                }
                _self.listLotsFrameWindow.postMessage({
                    timerName: 'windows_connect_lot_list',
                    callback: 'sync.setParentWindow',
                    contextCallback: 'sync',
                    'auction': _self.auction_params
                }, '*');
                _self.timers.windows_connect_lot_list = setTimeout(f, 250);
            };
            this.timers.windows_connect_lot_list = setTimeout(f, 250);
            this.timers.windows_connect_lot_list_start_time = (new Date()).getTime();
        },
        needAuthorize: function(data){
            /*
             * Метод выводит предупреждение о том, что для данного аукциона нет 
             * активной авторизованной сессии
             */
            var _self = this;
            (linq(this.timers)).foreach(function(timer, timerName){
                clearTimeout(timer);
                delete _self.timers[timerName];
            });
            this.state_panel.empty();
            alert('У вас нет активного авторизованного сеанса на данном аукционе. Авторизуйтесь и выполните синхронизацию заново.');
        },
        previewLotList: function(data){
            /*
             * Метод инициирует генерацию формы, в которой будет показан список
             * доступных для синхронизации лотов
             */
            var _self = this;
            var lotList = [];
            if (data.data && data.data.listLot) {
                lotList = data.data.listLot;
            }
            var filters = [];
            if (data.data && data.data.filters) {
                filters = data.data.filters;
            }
            var pages = [];
            if (data.data && data.data.pages) {
                pages = data.data.pages;
            }
            /*Создаем форму, в которой отобразим лоты для синхронизации*/
            if (!this.previewForm) {
                this.previewForm = $('<div class="sync-lot-list-preview"><div class="preview-background"></div><div class="preview-workarea"></div></div>');
                this.previewForm.get(0).close = function(){
                    $(this).remove();
                    try {
                        _self.listLotsFrameWindow && _self.listLotsFrameWindow.close();
                        _self.listLotsFrameWindow = null;
                        _self.listLotsFrame && (_self.listLotsFrame.parentNode.removeChild(_self.listLotsFrame), _self.listLotsFrame = null);
                        _self.state_panel.empty();
                    }catch (e) {}
                    return;
                    
                };
                this.previewForm.find('.preview-background:first').click(function(){
                    _self.previewForm.get(0).close();
                });
                $('body').append(this.previewForm);
            }
            this.previewForm;
            var wa = this.previewForm.find('.preview-workarea:first');
            wa.empty();
            this.previewSetFilters(wa, filters);
            this.previewSetPages(wa, pages);
            this.previewSetLots(wa, lotList);
            this.previewSetPages(wa, pages);
            this.previewSetButtons(wa, lotList);
            
        },
        afterSave: function(lot){
            $(this.previewForm).find('#lot-' + lot.OaAuctionItemId).prop('checked', false);
        },
        previewSetPages: function(wa, pages){
            /*
             * Метод рисует фильтры в форму со списком доступных для синхронизации лотов
             */
            var _self = this;
            if (pages && pages.length > 0) {
                var div = $('<div style="margin: 10px 0px; vertical-align: top;"></div>');
                var ul = $('<ul class="paginator"></ul>');
                div.append(ul);
                linq(pages).foreach(function(pageInfo){
                    if (!pageInfo.caption || !pageInfo.index) {
                        return;
                    }
                    var li = $('<li>' + pageInfo.caption + '</li>');
                    pageInfo.active && (li.addClass('active'));
                    li.get(0)._pageInfo = pageInfo;
                    ul.append(li);
                    li.click(function(){
                        $(this).parent().find('.active').removeClass('active');
                        wa.append('<div class="transparent-cover" style="text-align: center; font-size: 250%;">Идет перезагрузка списка лотов...</div>');
                        _self.listLotsFrameWindow.postMessage({
                            callback: 'sync.changePage',
                            contextCallback: 'sync',
                            index: this._pageInfo.index
                        }, '*');
                    });
                    
                });
                wa.append(div);
            }
        },
        previewSetFilters: function(wa, filters){
            /*
             * Метод рисует фильтры в форму со списком доступных для синхронизации лотов
             */
            var _self = this;
            if (filters && filters.length > 0) {
                var div = $('<div style="margin-bottom: 10px; vertical-align: top;"></div>');
                linq(filters).foreach(function(filter){
                    if (!filter.elements || filter.elements.length < 1) {
                        return;
                    }
                    var ul = $('<ul class="filter"></ul>');
                    linq(filter.elements).foreach(function(fElem){
                        var li = $('<li><input type="' + filter.type + '" id="' + 
                            fElem.id + '" name="' + fElem.name + '" value="' + 
                            fElem.value + '" ' + (fElem.checked ? 'checked="checked"' : '') + 
                            '/><label for="' + fElem.id + '">' + fElem.label + '</label></li>');
                        ul.append(li);
                        li.find('input:first').change(function(){
                            if (filter.type.toLowerCase() === 'radio') {
                                if (!this.checked) {
                                    return;
                                }
                            }
                            wa.append('<div class="transparent-cover" style="text-align: center; font-size: 250%;">Идет перезагрузка списка лотов...</div>');
                            _self.listLotsFrameWindow.postMessage({
                                callback: 'sync.changeFilterState',
                                contextCallback: 'sync',
                                IdFilter: filter.id,
                                elemId: fElem.id,
                                checked: this.checked
                            }, '*');
                        });
                    });
                    
                    div.append(ul);
                });
                wa.append(div);
            }
        },
        previewSetLots: function(wa, lotList){
            /*
             * Метод непосредственно рисует список лотов, доступных для синхронизации
             */
            var _self = this;
            if (lotList && lotList.length > 0) {
                var table = $('<table class="lot-list"></table>');
                wa.append(table);
                var cellsInRow = 0;
                (linq(lotList)).foreach(function(lot){
                    var tr = $('<tr></tr>');
                    var lotKey = lot.OaAuctionItemId;
                    tr.append('<td><input type="checkbox" value="' + lotKey + '" name="lot-' + lotKey + '" id="lot-' + lotKey + '" class="sync-this-lot"/></td>');
                    tr.find('input:first').get(0).__lot = lot;
                    if (lot.__previewLotImg) {
                        tr.append('<td><img src="' + lot.__previewLotImg + '" class="lot-preview-img"/></td>');
                    }
                    tr.append('<td>' + lot.Caption + '</td>');
                    cellsInRow = tr.children('td').length;
                    
                    table.append(tr);
                });
                var tr = $('<tr></tr>');
                tr.append('<td><input type="checkbox"/></td>');
                tr.find('input').change(function(){
                    var _s = this;
                    $(this).parents('table:first').find('.sync-this-lot').each(function(){
                        this.checked = _s.checked;
                    });
                });
                tr.append('<td colspan="' + (cellsInRow - 1) + '" style="text-align: right;"><img src="pict/update_91.png" title="Обновить список лотов" style="width: 20px; height: 20px; cursor: pointer;"/></td>');
                tr.find('img').click(function(){
                    _self.listLotsFrameWindow.postMessage({
                        callback: 'sync.getLotList',
                        contextCallback: 'sync'
                    }, '*');
                    
                });
                table.find('tr:first').before(tr);
                
            }
            else {
                wa.append('Нет лотов для синхронизации');
            }
        },
        previewSetButtons: function(wa, lotList){
            /*
             * Метод непосредственно рисует список лотов, доступных для синхронизации
             */
            var _self = this;
            var div = $('<div style="margin-bottom: 10px; vertical-align: top; white-space: nowrap;"></div>');
            
            if (lotList && lotList.length > 0) {
                var button = $('<a href="javascript:void(0)" class="apply-btn">Синхронизировать выбранные лоты</button>');
                div.append(button);
                button.click(function(){
                    var list = (linq(wa.find('table.lot-list:first').find('.sync-this-lot:checked'))).select(function(chkbx){
                        var lot = chkbx.__lot;
                        lot.Key = Base64.encode(lot.StockNo);
                        lot._key = lot.OaAuctionItemId;
                        lot.IdAuction = _self.auction_params.IdAuction;
                        lot._dataURL = null;
                        lot.DataFolder = null;
                        return lot;
                    }).collection;
                    _self.synchronize(list, _self.state_panel, _self.callback);
                });

                var button = $('<a href="javascript:void(0)" class="cancel-btn" style="margin-left: 15px;">Отмена</button>');
                div.append(button);
                button.click(function(){
                    _self.state_panel.empty().append('Синхронизация отменена пользователем...');
                    _self.previewForm.get(0).close();
                });
                wa.append(div);
                
            }
            else {
                wa.append('Нет лотов для синхронизации');
            }
        },
        savedData: function(sData, data){
            sData.lotImages = data.data.lotImages;
            sData.lotParams = data.data.lotParams;
            sData._dataURL = data.data._dataURL;
            return sData;
            
        }
    };
};
synchronizer_copart = function(auction_params){
    return {
        savedData: function(sData, data){
            sData.lotImages = data.data.lotImages;
            sData.lotParams = data.data.lotParams;
            return sData;
        }
        
    };
};

function crossDomainQuery(data) {
    console.log('crossDomainQuery', data);

    return 'crossDomainQueryResultData'
}