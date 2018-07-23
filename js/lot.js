

lot = function(params){
    linq(params._params).foreach(function(p){
        p.Caption && (p.Caption = Base64.decode(p.Caption));
        p.Name = Base64.decode(p.Name);
        p.Comment && (p.Comment = Base64.decode(p.Comment));
        p.Visible = !!p.Visible;
    });
    linq(params._images).foreach(function(img){
        img.IsMain = !!img.IsMain;
        img.Visible = !!img.Visible;
        img.FileName = Base64.decode(img.FileName);
    });
    return  $.extend(new base_class(), {
        params: params,
        reloadURL: 'index.php?mode=data&datakey=lot',
        updateURL: 'index.php?mode=data&datakey=lot_update',
        getHTMLForm: function(){
            var _self = this;
            /*Метод рисует форму для отображения аукциона на странице*/
            var style = '';
            var conte = _self.htmlForm || $('<div class="lot category-item"></div>');
            
            conte.empty();
            conte.get(0).categoryItem = this;
            
            var topPanel = $('<div class="top-panel"></div>');
            conte.append(topPanel);
            
            var pdfBtn = $('<img src="pict/filetypes/pdffile.png" title="Печать в формате PDF"/>');
            pdfBtn.click(function(){
                if (_self._disabled) {
                    return;
                }
                $('iframe#loader').remove();
                $('body').append('<iframe src="index.php?mode=data&datakey=lot_print&IdLot=' + _self.params.IdLot + '" id="loader"></iframe>');
            });
            topPanel.append(pdfBtn);
            
            if (!this.params.Archive) {
                var syncBtn = $('<img src="pict/sync_128.png" title="Синхронизировать лот..."/>');
                syncBtn.click(function(){
                    if (_self._disabled) {
                        return;
                    }
                    $.ajax({
                        url: 'index.php?mode=data&datakey=auctiones_list',
                        type: 'POST',
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
                            _self.disabled(true);
                            var auction = linq(data.records).first(function(a){
                                return a.IdAuction == _self.params.IdAuction;
                            });
                            linq(auction).foreach(function(v, k){
                                typeof v === typeof 'aaa' && (auction[k] = Base64.decode(v));
                            })
                            auction = new window.auction(auction);
                            auction.synchronize(
                                [
                                    {
                                        Key: Base64.encode(_self.params.Key),
                                        DataFolder: Base64.encode(_self.params.DataFolder),
                                        _dataURL: Base64.encode(_self.params.BaseURL),
                                        IdAuction: _self.params.IdAuction
                                    }
                                ],
                                function(){
                                    _self.disabled(false);
                                    _self.reload();
                                },
                                {
                                    mode: 'singleLot',
                                    lot: _self.params
                                }
                            );
                        }
                    });
                });

                topPanel.append(syncBtn);
            }
            
            var archiveBtn = $('<img src="pict/archive_128.png" title="Архивировать лот"/>');
            archiveBtn.click(function(){
                if (_self._disabled) {
                    return;
                }
                if (_self.params.Archive) {
                    if (confirm('Вы уверены, что хотите извлечь лот из архива?')) {
                        _self.update('Archive', false);
                    }
                }
                else {
                    if (confirm('Вы уверены, что хотите отправить лот в архив?'))
                        _self.update('Archive', true);
                }
                
            });
            topPanel.append(archiveBtn);
            
            var editBtn = $('<img src="pict/lot_edit_128.png" title="Редактировать лот"/>');
            editBtn.click(function(){
                if (_self._disabled) {
                    return;
                }
                lot.edit(
                    linq(window.auctions_list).first(function(a){ 
                        return a.params.IdAuction == _self.params.IdAuction;
                    }), 
                    _self,
                    function(classPrototype, data){
                        _self.reload();
                    }
                );
            });
            topPanel.append(editBtn);
            
            var deleteBtn = $('<img src="pict/trash_red_round_128.png" title="Удалить лот"/>');
            deleteBtn.click(function(){
                if (_self._disabled) {
                    return;
                }
                if (confirm('Вы уверены, что хотите удалить лот без возможности восстановления?')) {
                    _self.disabled(true);
                    $.ajax({
                        url: 'index.php?mode=data&datakey=delete_objects',
                        type: 'POST',
                        data: {ObjectType: 'lot_list', ObjectsIdList: [_self.params.IdLot]},
                        success: function(data){
                            if (typeof 'aaa' === typeof data) {
                                data = JSON.parse(data);
                            }
                            _self.disabled(false);
                            if (data && data.success) {
                                _self.htmlForm.remove();
                            }
                        }
                    });
                }
            });
            topPanel.append(deleteBtn);
            
            style = 'font-size: 140%; line-height: 140%;';
            conte.append('<div class="lot-key" style="' + style + '"><span class="label">ЛОТ </span>' + this.params.Key + '</div>');
            
            var lotName = this.params.ManufactYear + ' ' + this.params.Maker + ' ' + this.params.Model;
            if (lotName.replace(/\s+/g, '') !== '') {
                style = 'font-size: 120%; line-height: 120%;';
                conte.append('<div class="lot-name" style="' + style + '">' + lotName + '</div>');
            }
            
            var date = this.params.SaleDate ? new Date(this.params.SaleDate.date) : null;
            var calendParams = {
                change: function(d){
                    _self.update('SaleDate', d.y == 0 ? null : d.y + '-' + d.m + '-' + d.d);
                },
                date: {
                    d: date ? date.getDate() : 0,
                    m: date ? date.getMonth() + 1 : 0,
                    y: date ? date.getFullYear() : 0
                }
            };
            
            var block = $('<div class="lot-saledate-panel"><span class="label">Дата реализации лота</span></div>');
            conte.append(block);
            var calend = new calendar(calendParams);
            block.append(calend.getHTMLForm());
            
            
            var optionsPanel = $('<div class="category-item-options-panel"></div>');
            conte.append(optionsPanel);
            
            
            var imgSize = 48;
            
            var mainImg = null;
            if (this.params.Archive) {
                conte.append('<div><img title="Лот находится в архиве" src="pict/archive_256.png" style="max-width: 150px; max-height: 150px;" /></div>');
            }
            else {
                if (
                        this.params._images && 
                        (
                            mainImg = linq(this.params._images)
                                .first(function(img){ return !!img.IsMain && !!img.Visible; }) || 
                                linq(this.params._images)
                                .first(function(img){ return !!img.Visible; })
                        ) !== null &&
                        typeof mainImg !== typeof void null
                    ) {
                    /*Прикрепим главное изображение*/
                    var src = mainImg.FileName;
                    conte.append('<div class="lot-main-img-container"><a href="' + src + '" target="_blank"><img class="lot-main-img" src="' + src + '" class="main-img-preview"  /></a></div>');

                }
            }
            
            var option = $('<div class="category-item-option" title="Параметры лота"><img src="pict/list_128.png" style="width: ' + imgSize + 'px; height: ' + imgSize + 'px;" /> </div>');
            optionsPanel.append(option);
            option.click(function(){_self.getFormParamsList();});
            
            if (!this.params.Archive) {
                var option = $('<div class="category-item-option" title="Список изображений"><img src="pict/camera_128.png" style="width: ' + imgSize + 'px; height: ' + imgSize + 'px;" /><div class="img-counter">' + this.params._images.length + '</div></div>');
                optionsPanel.append(option);
                option.click(function(){_self.getFormImagesList();});
                
            }
            
            style = 'margin-top: 10px;';
            conte.append('<div class="lot-data" style="' + style + '"></div>');
            
            this.htmlForm = conte;
            
            return conte;
        },
        updateMainImg: function(newImg){
            var img = this.htmlForm.find('.main-img-preview:first');
            img.attr('src', newImg);
            img.parents('a:first').attr('href', newImg);
        },
        getFormParamsList: function(){
            var conte = this.htmlForm.find('.lot-data:first');
            conte.empty();
            var modeName = 'lot-params';
            if (conte.get(0).mode != modeName) {
                linq(this.params._params).foreach(function(param){
                    if (param.Visible !== true) {
                        return;
                    }
                    conte.append('<div class="lot-param"><span class="lot-param-name">' + 
                        (param.Caption ? param.Caption : param.Name) + 
                        '</span><span class="lot-param-value">' + 
                        (param.Value === null ? '' : param.Value) + 
                        '</span></div>'
                    );
                });
                conte.get(0).mode = modeName;
            }
            else {
                conte.get(0).mode = null;
            }
        },
        getFormImagesList: function(){
            var conte = this.htmlForm.find('.lot-data:first');
            conte.empty();
            var modeName = 'lot-images';
            if (conte.get(0).mode != modeName) {
                linq(this.params._images).foreach(function(image){
                    if (image.Visible !== true) {
                        return;
                    }
                    var lm = new lot_image(image);
                    conte.append(lm.getHTMLForm());
                });
                conte.get(0).mode = modeName;
            }
            else {
                conte.get(0).mode = null;
            }
        }
        
    });
};

$.extend(lot, new base_new_class(), {
    entityName: 'lot_list', 
    auctionInstance: null,
    lotInstance: null,
    saveURL: 'index.php?mode=data&datakey=save_lot',
    'new': function(auctionInstance, callback){
        this.auctionInstance = auctionInstance;
        typeof function(){} === typeof callback && (this.saveCallback = callback);
        var _self = this;
        $.ajax({
            url: 'index.php?mode=data&datakey=empty_entity',
            type: 'POST',
            data: {entityName: this.entityName},
            success: function(data){
                if (typeof 'aaa' === typeof data) {
                    data = JSON.parse(data);
                }
                if (data) {

                    linq(data).foreach(function(v, k){
                        typeof v === typeof 'aaa' && (data[k] = Base64.decode(v));
                    });
                    /*Привяжем параметр к конкретному аукциону*/
                    data.IdAuction = auctionInstance.params.IdAuction;
                    _self.params = data;
                    $('body').append(_self.getHTMLForm());
                }
            }
        });
    },
    edit: function(auctionInstance, lotInstance, callback){
        typeof function(){} === typeof callback && (this.saveCallback = callback);
        this.auctionInstance = auctionInstance;
        this.params = lotInstance.params;
        this.editedInstance = lotInstance;
        $('body').append(this.getHTMLForm());
    },
    getHTMLForm: function(){
        /*Метод рисует форму для отображения аукциона на странице*/
        var _self = this;
        var style = '';
        var fixedCoverForm = _self.getFixedCoverForm();
        var _conte = fixedCoverForm.find('.fixed-cover-workarea:first');
        _conte.empty();
        var conte = $('<div class="lot category-item"></div>');
        _conte.append(conte);
        
        conte.get(0).categoryItem = this;

        style = 'font-size: 140%; line-height: 140%; margin-bottom: 15px;';
        conte.append('<div style="' + style + '"> ' + (_self.params.IdLot > 0 ? 'Редактирование лота ' + _self.params.Key : 'Создание нового лота') + '</div>');
        
        var block = $('<div class="auct-lot-param-field"><div class="param-label">ID</div><div class="param-value"><input type="text" /></div></div>');
        block.find('input').val(this.params.Key || '').blur(function(){
            if (_self.params.IdLot > 0) {
                /*Если это редактируемый, а не новый лот, то его ключ уже нельзя менять*/
                return;
            }
            var newVal = $(this).val();
            if (newVal != _self.params.Key) {
                if(newVal.replace(/\s+/g, '') && !(/^\s*[_a-z0-9]+\s*$/i.test(newVal))) {
                    /*На пустую строку не ругаемся при blur-событии - просто не позволим ее сохранить при отправке на сервер*/
                    alert('Идентификатор лота должен состоять из  символов подчеркивания, цифр или латинских букв.');
                    return;
                }
                _self.update('Key', $(this).val());
            }
        });
        conte.append(block);
        if (_self.params.IdLot > 0) {
            /*Если это редактируемый, а не новый лот, то его ключ уже нельзя менять*/
            block.find('input').attr('disabled', true);
        }
        
        //select по аукциону
        var block = $('<div class="auct-lot-param-field"><div class="param-label">Аукцион</div><div class="param-value"><select ></select></div></div>');
        var select = block.find('select:first');
        _self.params.IdAuction > 0 && select.attr('disabled', true);
        linq(window.auctions_list).foreach(function(auctInstance){
            select.append('<option value="' + auctInstance.params.IdAuction + '">' + auctInstance.params.Name + '</option>');
        });
        select.val(this.params.IdAuction || '').blur(function(){
            var newVal = $(this).val();
            if (newVal != _self.params.IdAuction) {
                
                _self.update('IdAuction', +$(this).val());
            }
        });
        conte.append(block);
        
        //календарь для даты реализации
        var block = $('<div class="auct-lot-param-field"><div class="param-label">Дата реализации</div><div class="param-value"></div></div>');
        var d = {
            d: 0,
            m: 0,
            y: 0
        };
        if (_self.params.SaleDate) {
            var _d = lib.MySQLDateToDate(_self.params.SaleDate.date);
            d.y = _d.getFullYear();
            d.m = _d.getMonth() + 1;
            d.d = _d.getDate();
        }
        var calendParams = {
            change: function(d){
                if (d.d > 0 && d.m > 0 && d.y > 0) {
                    _self.params.SaleDate = new Date(d.y, d.m - 1, d.d);
                }
            },
            date: d
        };
        var calend = new calendar(calendParams);
        block.find('.param-value').append(calend.getHTMLForm());
        conte.append(block);
        
        var block = $('<div class="auct-lot-param-field"><div class="param-label">VIN</div><div class="param-value"><input type="text" /></div></div>');
        block.find('input').val(this.params.VIN || '').blur(function(){
            var newVal = $(this).val();
            if (newVal != _self.params.VIN) {
                if(newVal.replace(/\s+/g, '') && !(/^\s*[_a-z0-9]+\s*$/i.test(newVal))) {
                    /*На пустую строку не ругаемся при blur-событии - просто не позволим ее сохранить при отправке на сервер*/
                    alert('Идентификатор VIN должен состоять из  символов подчеркивания, цифр или латинских букв.');
                    return;
                }
                _self.update('VIN', $(this).val());
            }
        });
        conte.append(block);
        
        var block = $('<div class="auct-lot-param-field"><div class="param-label">Год выпуска</div><div class="param-value"><input type="text" /></div></div>');
        block.find('input').val(this.params.ManufactYear || '').keypress(lib.numericFields).blur(function(){
            var newVal = $(this).val();
            if (newVal && (newVal < 1900 || newVal > 2100)) {
                alert('Недопустимый год выпуска!');
                return;
            }
            if (newVal != _self.params.ManufactYear) {
                _self.update('ManufactYear', +$(this).val());
            }
        });
        conte.append(block);
        
        
        var block = $('<div class="auct-lot-param-field"><div class="param-label">Производитель</div><div class="param-value"><input type="text" /></div></div>');
        block.find('input').val(this.params.Maker || '').blur(function(){
            if ($(this).val() != _self.params.Maker) {
                _self.update('Maker', $(this).val());
            }
        });
        conte.append(block);
        
        var block = $('<div class="auct-lot-param-field"><div class="param-label">Модель</div><div class="param-value"><input type="text" /></div></div>');
        block.find('input').val(this.params.Model || '').blur(function(){
            if ($(this).val() != _self.params.Model) {
                _self.update('Model', $(this).val());
            }
        });
        conte.append(block);
        
        var optionsPanel = $('<div class="category-item-options-panel"></div>');
        conte.append(optionsPanel);


        var imgSize = 48;

        var option = $('<div class="category-item-option" title="Добавить параметр"><img src="pict/list_add_128.png" style="width: ' + imgSize + 'px; height: ' + imgSize + 'px;" /> </div>');
        optionsPanel.append(option);
        option.click(function(){
            _self.getFormParamsList();
        });
        
        var option = $('<div class="category-item-option" title="Добавить изображение"><img src="pict/camera_add_128.png" style="width: ' + imgSize + 'px; height: ' + imgSize + 'px;" /><div class="img-counter">' + this.params._images.length + '</div></div>');
        optionsPanel.append(option);
        option.click(function(){
            _self.getFormImagesList();
        });
        
        conte.append('<div class="lot-data"></div>');
        
        var block = $('<div style="text-align: center;"></div>');
        var applyBtn = $('<a class="filter-apply-btn apply-btn" >Сохранить</a>');
        block.append(applyBtn);
        applyBtn.click(function(){
            if(!(/^\s*[_a-z0-9]+\s*$/i.test(_self.params.Name))) {
                alert('Идентификатор лота должен состоять из  символов подчеркивания, цифр или латинских букв.');
                return;
            }
            _self.save();
        });
        var cancelBtn = $('<a href="javascript:void(0)" class="cancel-btn" style="margin-left: 15px;">Отмена</button>');
        block.append(cancelBtn);
        cancelBtn.click(function(){
            _self.htmlForm.remove();
        });
        _conte.append(block);
        _conte.append('<iframe class="loader" id="lot-image-uploader" name="lot-image-uploader"></iframe>');

        this.htmlForm = fixedCoverForm;

        dataPanel.setViewMode(dataPanel._viewMode, this);
        
        return fixedCoverForm;
    },
    _save: function(sdata){
        if (sdata.ManufactYear < 1900 || sdata.ManufactYear > 2100) {
            alert('Недопустимый год выпуска!');
            return false;
        }
        sdata._params = linq(this.params._params).select(function(p){
            return linq(p).reduce(function(res, v, k){
                res[k] = typeof v === typeof 'aaa' ? Base64.encode(v) : v;
                return res;
            }, {});
        }).collection;
        sdata._images = linq(this.params._images).select(function(img){
            return linq(img).reduce(function(res, v, k){
                res[k] = typeof v === typeof 'aaa' ? Base64.encode(v) : v;
                return res;
            }, {});
        }).collection;
        return sdata;
    },
    getFormParamsList: function(){
        var conte = this.htmlForm.find('.lot-data:first');
        conte.empty();
        var modeName = 'lot-params';
        if (conte.get(0).mode != modeName) {
            var _self = this;
            var select = $('<select class="select-param"><option value="-1"></option></select>');
            linq(this.auctionInstance.params.auction_params || []).orderBy(function(p1, p2){
                p1 = Base64.decode(p1.Caption || p1.Name || '');
                p2 = Base64.decode(p2.Caption || p2.Name || '');
                return p1 < p2 ? -1 : p1 === p2 ? 0 : 1;
            }).foreach(function(p){
                var decodedCaption = Base64.decode(p.Caption || p.Name || '');
                var option = $('<option value="' + p.IdParam + '">' + decodedCaption + '</option>');
                option.get(0).categoryItem = p;
                option.get(0).decodedCaption = decodedCaption;
                if (!p.Visible) {
                    option.css({
                        color: '#aaa'
                    });
                }
                select.append(option);
            });
            conte.append(select);
            
            var formEditParam = function(param){
                var form = $('<div class="lot-param param-' + param.IdParam + '"><span class="lot-param-name">' + 
                    (param.Caption ? param.Caption : param.Name) + 
                    '</span><span class="lot-param-value"><textarea rows="1"></textarea>' + 
                    '</span><div>'
                );
				var textarea = form.find('textarea:first');
                textarea.val((param.Value === null ? '' : param.Value));
                
                textarea.blur(function(){
                    param.Value = this.value;
                });
                
                return form;
            };
            
            select.change(function(){
                var IdParam = +$(this).val();
                if (IdParam === -1) {
                    return;
                }
                var htmlForm = null;
                !_self.params._params && (_self.params._params = []);
                if ((htmlForm = conte.find('.param-' + IdParam + ':first')).length === 0) {
                    var selectedOption = $(this).children().get(this.selectedIndex);
                    var p = {
                        IdParamValue: 0,
                        IdLot: 0,
                        IdParam: +$(this).val(),
                        Value: null,
                        Caption: selectedOption.decodedCaption,
                        Name: selectedOption.decodedCaption
                    };
                    htmlForm = formEditParam(p);
                    htmlForm.get(0).categoryItem = p;
                    conte.append(htmlForm);
                    _self.params._params.push(p);
                    if (!selectedOption.categoryItem.Visible) {
                        htmlForm.find('.lot-param-name:first').css({
                            color: '#aaa'
                        });
                    }
                }
                $(this).after(htmlForm);
                
            });
            
            linq(this.params._params).foreach(function(param){
                conte.append(formEditParam(param));
            });
            conte.get(0).mode = modeName;
        }
        else {
            conte.get(0).mode = null;
        }
    },
    getFormImagesList: function(){
        var conte = this.htmlForm.find('.lot-data:first');
        conte.empty();
        var modeName = 'lot-images';
        if (conte.get(0).mode != modeName) {
            
            var formFile = $('<div class="image-uploader"><form target="lot-image-uploader" ' +
                ' enctype="multipart/form-data" method="post" action="index.php?mode=page&page=file_upload&archive_extract=true"><input multiple type="file" name="uploaded_files[]"></form><div>');
            formFile.find('input[type="file"]').change(function(){
                $(this).parents('form:first').submit();
            });
            
            conte.append(formFile);
            
            linq(this.params._images).foreach(function(image){
                var l_i = new lot_image_upPreview(image);
                conte.append(l_i.getHTMLForm());
            });
            conte.get(0).mode = modeName;
        }
        else {
            conte.get(0).mode = null;
        }
    },
    setUploadedFiles: function(data){
        if (data) {
            
            var _self = this;
            !_self.params._images && (_self.params._images = []);
            var conte = this.htmlForm.find('.lot-data:first');
            data = linq(data).where(function(image){
                image.Visible = true;
                var lm = new lot_image_upPreview(image);
                conte.append(lm.getHTMLForm());
                return !lm.rejected;
            }).collection;
            _self.params._images = _self.params._images.concat(data);
        }
    }
    
});