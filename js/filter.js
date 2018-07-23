

filter = function(params){
    
    var _self = {
        params: params,
        htmlForm: null,
        icon: null,
        value: {},
        getHTMLForm: function(){
            
            var style = '';
            var conte = $(
                '<div class="filter-panel"></div>'
            );
            
            var _self = this;
            if (this.params.fields) {
                conte.append('<div class="filter-fields"></div>' + 
                    '<div class="filter-label"><img src="pict/filter_128.png" /></div>');
                var c = conte.find('.filter-fields:first');
                for (var i in this.params.fields) {
                    var field = this.params.fields[i];
                    c.append(this['getFilterForm_' + field.type.toLowerCase()](field));
                }
                var div = $('<div style="text-align: center; margin-top: 10px;"></div>');
                var applyBtn = $('<a class="filter-apply-btn apply-btn" >Применить</a>');

                div.append(applyBtn);
                c.append(div);
                
                applyBtn.click(function(){
                    _self.change();
                });
            }
            
            if (this.params.options) {
                conte.append('<div class="filter-options"></div>');
                var div = conte.find('.filter-options:first');
                for (var i in this.params.options) {
                    var o = this.params.options[i];
                    var el;
                    if (o.element instanceof jQuery) {
                        el = o.element;
                    }
                    else if (typeof o.element === typeof function(){}) {
                        el = o.element();
                    }
                    else {
                        el = $(o.element);
                    }
                    div.append(el);
                    el.get(0)._filterOption = o;
                    el.addClass('filter-option');
                    el.click(function(){
                        typeof function(){} === typeof this._filterOption.click && 
                            this._filterOption.click.call(this, _self.clone(_self.value));
                    });
                }
            }
            
            
            
            this.htmlForm = conte;
            
            return conte;
        },
        clone: function(obj, maxDeep){
            typeof 111 !== typeof maxDeep && (maxDeep = 10);
            var f = function (source, currentDeep){
                if (typeof source !== typeof {} || !source) return source;
                var target = source instanceof Array ? [] : {};
                var nextDeep = currentDeep + 1;
                if (nextDeep > maxDeep) return undefined;
                for (var key in source) {
                    target[key] = f(source[key], nextDeep);
                }
                return target;
            };
            return f(obj, 0);
        },
        getBaseFilterForm: function(field){
            var conte = $('<div class="filter-field"><div class="filter-field-caption"></div><div class="filter-field-value"></div></div>');
            if (field.caption && typeof field.caption === typeof 'aaa') {
                conte.find('.filter-field-caption').html(field.caption);
            }
            return conte;
        },
        getTextField: function(field){
            var input = document.createElement('input');
            input.type = 'text';
            if (field.style && typeof {} === typeof field.style) {
                for (var key in field.style) {
                    input.style[key] = field.style[key];
                }
            }
            if (field.attributes && typeof {} === typeof field.attributes) {
                for (var key in field.attributes) {
                    input.setAttribute(key, field.attributes[key]);
                }
            }
            
            var _self = this;
            input.onblur = function(){
                _self.value[field.id] = this.value;
            };
            
            return input;
        },
        getSelectField: function(field){
            var select = document.createElement('SELECT');
            if (field.style && typeof {} === typeof field.style) {
                for (var key in field.style) {
                    select.style[key] = field.style[key];
                }
            }
            for (var i in field.elements) {
                var o = document.createElement('OPTION');
                o.value = field.elements[i].value;
                o.innerHTML = field.elements[i].caption;
                select.appendChild(o);
            }
            
            
            var _self = this;
            select.onchange = function(){
                _self.value[field.id] = this.value;
            };
            
            return select;
        },
        getFilterForm_singleselect: function(field){
            var conte = this.getBaseFilterForm(field);
            var select = $(this.getSelectField(field));
            conte.find('.filter-field-value').append(select);
            
            return conte;
        },
        getFilterForm_dateinterval: function(field){
            var _self = this;
            
            _self.value[field.id] = {
                from: {
                    d: 0,
                    m: 0, 
                    y: 0
                },
                to: {
                    d: 0,
                    m: 0, 
                    y: 0
                }
            };
            var conte = this.getBaseFilterForm(field);
            conte.addClass('filter-dateinterval');
            conte.append('<p class="caption">с</p><p class="dateinterval-from"></p><p class="caption">по</p><p class="dateinterval-to"></p>');
            
            var calendParams = {
                change: function(d){
                    _self.value[field.id].from = d;
                },
                date: {
                    d: 0,
                    m: 0,
                    y: 0
                }
            };
            var calend = new calendar(calendParams);
            conte.find('.dateinterval-from').append(calend.getHTMLForm());
            
            var calendParams = {
                change: function(d){
                    _self.value[field.id].to = d;
                },
                date: {
                    d: 0,
                    m: 0,
                    y: 0
                }
            };
            var calend = new calendar(calendParams);
            conte.find('.dateinterval-to').append(calend.getHTMLForm());
            
            return conte;
        },
        getFilterForm_checkpoint: function(field){
            var _self = this;
            var conte = this.getBaseFilterForm(field);
            conte.addClass('filter-checkpoint');
            conte.append(this.getSelectField({
                id: field.id_CPCSign,
                elements: [
                    {
                        value: '=',
                        caption: '='
                    },
                    {
                        value: '<',
                        caption: '<'
                    },
                    {
                        value: '>',
                        caption: '>'
                    },
                    {
                        value: '<>',
                        caption: '<>'
                    },
                    {
                        value: '>=',
                        caption: '>='
                    },
                    {
                        value: '<=',
                        caption: '<='
                    }
                ]
            }));
            /*Т.к. знак сравнения уже задан, тонужно его сразу прописать в хранилище*/
            conte.find('select:first').change();
            conte.append(this.getTextField({
                id: field.id_CPValue,
                attributes: field.attributes || null
            }));
            
            return conte;
        },
        getFilterForm_date: function(field){
            var conte = this.getBaseFilterForm(field);
            var _self = this;
            
            var calendParams = {
                change: function(d){
                    _self.value[field.id] = d;
                }
            };
            var calend = new calendar(calendParams);
            conte.find('.filter-field-value').append(calend.getHTMLForm());
            
            return conte;
        },
        getFilterForm_text: function(field){
            var conte = this.getBaseFilterForm(field);
            var input = $(this.getTextField(field));
            conte.find('.filter-field-value').append(input);
            
            return conte;
        },
        change: function(){
            typeof function(){} === typeof this.params.change && this.params.change(this.clone(this.value));
        }
        
    };
    _self.getHTMLForm();
    return _self;
};


filterOptions = {
    removeLotsByList: {
        element: '<img src="pict/trash_red_round_128.png" title="Удалить лоты по списку"/>',
        click: function(data){
            var _self = this;
            if (confirm('Вы уверены, что хотите удалить выбранные лоты без возможности их восстановления?')) {
                $.ajax({
                    url: 'index.php?mode=data&datakey=delete_objects',
                    type: 'POST',
                    data: {
                        ObjectType: 'lot_list', 
                        ObjectsIdList: linq(dataPanel.getItems())
                            .select(function(item){ return item.params.IdLot;}).collection
                    },
                    success: function(data){
                        _self.change();
                    }
                });
            }
        }
    },
    archiveLotsByList: {
        element: '<img src="pict/to_archive_128.png" title="Архивировать лоты по списку"/>',
        click: function(data){
            if (confirm('Вы уверены, что хотите отправить выбранные лоты в архив?')) {
                linq(dataPanel.getItems()).foreach(function(lot){
                    lot.update('Archive', true);
                });
            }
        }
    },
    unarchiveLotsByList: {
        element: '<img src="pict/from_archive_128.png" title="Извлечь из архива лоты по списку"/>',
        click: function(data){
            if (confirm('Вы уверены, что хотите извлечь выбранные лоты из архива?')) {
                linq(dataPanel.getItems()).foreach(function(lot){
                    lot.update('Archive', false);
                });
            }
        }
    },
    forcedGeneratePDFbyList: {
        element: '<img src="pict/filetypes/pdffile.png" title="Заново сгенерировать PDF-файлы по списку лотов"/>',
        click: function(data){
            var parent = $(this).parents('.filter-options:first');
            parent.find('.state-panel').remove();
            var statePanel = $('<div class="state-panel"></div>');
            if (confirm('Вы уверены, что хотите для выбранных лотов сгенерировать PDF-файлы заново, удалив без возможности восстановления ранее созданные файлы?')) {
                linq(dataPanel.getItems()).foreach(function(lot){
                    $.ajax({
                        url: 'index.php?mode=data&datakey=lot_gen_pdf',
                        type: 'POST',
                        data: {IdLot: lot.params.IdLot},
                        success: function(data){
                            statePanel.html('Обработан лот ' + lot.Key);
                        }
                    });
                    
                });
            }
        }
    },
    viewTable: {
        element: '<img src="pict/view_list.png" title="Табличный вид"/>',
        click: function(data){
            dataPanel.setViewMode('table');
        }
    },
    viewTail: {
        element: '<img src="pict/view_tail.png" title="Вид - плитки"/>',
        click: function(data){
            dataPanel.setViewMode('tail');
        }
    },
    blockSplitter: {
        element: '<div class="filter-blockoptions-splitter"></div>',
        click: function(data){
            return;
        }
    }
    
};

filterFields = {
    lot_VIN: {
        caption: 'VIN',
        type: 'text',
        id: 'VIN'
    },
    lot_Key: {
        caption: '№ лота',
        type: 'text',
        id: 'KeyLot'
    },
    lot_SaleDate: {
        caption: 'Дата реализации',
        type: 'dateinterval',
        id: 'SaleDate'
    },
    lot_ImagesCount: {
        caption: 'Количество изображений',
        type: 'checkpoint',
        id_CPCSign: 'ImagesCount_CSign',
        id_CPValue: 'ImagesCount',
        attributes: {
            size: 4
        }
        
    }
};

function allLotsListFilter(params) {
    var lot_filter = new filter({
        fields: [
            filterFields.lot_VIN,
            filterFields.lot_Key,
            filterFields.lot_SaleDate,
            {
                caption: 'Аукцион',
                type: 'singleselect',
                id: 'IdAuction',
                elements: [
                    {
                        caption: '',
                        value: ''
                    },
                    {
                        caption: 'COPART',
                        value: 'copart'
                    },
                    {
                        caption: 'IAAI',
                        value: 'iaai'
                    }
                ]
            },
            filterFields.lot_ImagesCount
        ],
        change: function(data){
            if (!data || !linq(data).firstKey(function(v,k){return true;})) {
                return;
            }
            data.KeyLot && (data.KeyLot = Base64.encode(data.KeyLot));
            data.VIN && (data.VIN = Base64.encode(data.VIN));
            $.ajax({
                url: 'index.php?mode=data&datakey=' + params.key,
                type: 'POST',
                data: {filter: data},
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
                    $(dataPanel).children('.category-item').remove();

                    params.funcCtgrItemAppend(data, params.base_class, true);
                }
            });
        },
        options: [
            filterOptions.removeLotsByList,
            filterOptions.archiveLotsByList,
            filterOptions.unarchiveLotsByList,
            filterOptions.forcedGeneratePDFbyList,
            filterOptions.blockSplitter,
            filterOptions.viewTable,
            filterOptions.viewTail
            /*
             * Создание лота здесь не реализую, поскольку без указания 
             * аукциона неясно, какой список параметров брать
             */
        ]
    });
    
    $(dataPanel).append(lot_filter.htmlForm);
}

function auctionLotsListFilter(auctionInstance) {
    var lot_filter = new filter({
        fields: [
            filterFields.lot_VIN,
            filterFields.lot_Key,
            filterFields.lot_SaleDate,
            filterFields.lot_ImagesCount
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
                    IdAuction: auctionInstance.params.IdAuction
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
                    $(dataPanel).children('.category-item').remove();

                    auctionInstance.funcLotAppend(data);
                    dataPanel.setViewMode()
                }
            });
        },
        options: [
            filterOptions.removeLotsByList,
            filterOptions.archiveLotsByList,
            filterOptions.unarchiveLotsByList,
            filterOptions.forcedGeneratePDFbyList,
            {
                element: '<img src="pict/camaro_add_128.png" title="Создать новый лот"/>',
                click: function(data){
                    lot.new(
                        auctionInstance,
                        function(classPrototype, data){
                            if (classPrototype.editedInstance) {
                                classPrototype.editedInstance.reload();
                            }
                            else {
                                var instance = linq(dataPanel.getItems()).first(function(catItem){
                                    return catItem.params.IdLot == data.IdLot;
                                });
                                if (instance) {
                                    instance.reload();
                                }
                                else {
                                    instance = new classPrototype(data);
                                    instance.reload(function(){
                                        $(dataPanel).append(instance.htmlForm);
                                    });
                                }
                            }
                        }
                    );
                }
            },
            filterOptions.blockSplitter,
            filterOptions.viewTable,
            filterOptions.viewTail
        ]
    });
    $(dataPanel).append(lot_filter.htmlForm);
}

function auctionParamsListFilter(auctionInstance) {
    var params_filter = new filter({
        change: function(data){

        },
        options: [
            //Удалять параметры бессмысленно, т.к. при первой же синхронизации они восстановятся
            {
                element: '<img src="pict/visible_128.png" title="Включить показ параметров по списку"/>',
                click: function(data){
                    if (confirm('Вы уверены, что хотите изменить видимость выбранных параметров?')) {
                        linq(dataPanel.getItems()).foreach(function(auct_param){
                            auct_param.update('Visible', true);
                        });
                    }
                }
            },
            {
                element: '<img src="pict/unvisible_128.png" title="Отключить показ параметров по списку"/>',
                click: function(data){
                    if (confirm('Вы уверены, что хотите изменить видимость выбранных параметров?')) {
                        linq(dataPanel.getItems()).foreach(function(auct_param){
                            auct_param.update('Visible', false);
                        });
                    }
                }
            },
            {
                element: '<img src="pict/car_doc_add_128.png" title="Создать новый параметр"/>',
                click: function(data){
                    /*
                     * Здесь callback не передаем и воспользуемся стандартным,
                     * поскольку здесь нужно обновлять список параметров при создании нового
                     */
                    auct_lot_param.new(auctionInstance);
                }
            }
        ]
    });
    $(dataPanel).append(params_filter.htmlForm);
}