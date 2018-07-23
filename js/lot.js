

lot = function(params){
    var _self = {
        params: params,
        htmlForm: null,
        getHTMLForm: function(){
            var _self = this;
            /*Метод рисует форму для отображения аукциона на странице*/
            var style = '';
            var conte = _self.htmlForm || $('<div class="lot category-item"></div>');
            conte.empty();
            conte.get(0).categoryItem = this;
            
            var topPanel = $('<div class="top-panel"></div>');
            conte.append(topPanel);
            
            var syncBtn = $('<img src="pict/sync_128.png" title="Синхронизировать лот..."/>');
            syncBtn.click(function(){
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
                        auction.synchronize([
                            {
                                Key: Base64.encode(_self.params.Key),
                                DataFolder: Base64.encode(_self.params.DataFolder),
                                _dataURL: Base64.encode(_self.params.BaseURL),
                                IdAuction: _self.params.IdAuction,
                            }
                            ],
                            function(){
                                _self.reload();
                            }
                        );
                    }
                });
            });
            
            topPanel.append(syncBtn);
            
            var pdfBtn = $('<img src="pict/filetypes/pdffile.png" title="Печать в формате PDF"/>')
            pdfBtn.click(function(){
                $('iframe#loader').remove();
                $('body').append('<iframe src="index.php?mode=data&datakey=lot_print&IdLot=' + _self.params.IdLot + '" id="loader"></iframe>');
            });
            topPanel.append(pdfBtn);
            
            style = 'font-size: 140%; line-height: 140%;';
            conte.append('<div style="' + style + '">ЛОТ ' + this.params.Key + '</div>');
            
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
            
            var block = $('<div class="lot-saledate-panel">Дата реализации лота</div>');
            conte.append(block);
            var calend = new calendar(calendParams);
            block.append(calend.getHTMLForm());
            
            
            var optionsPanel = $('<div class="category-item-options-panel"></div>');
            conte.append(optionsPanel);
            
            
            var imgSize = 48;
            var mainImg = null;
            if (
                    this.params._images && 
                    (mainImg = linq(this.params._images).first(function(img){ return !!img.IsMain; }) || this.params._images[0]) !== null &&
                    typeof mainImg !== typeof void null
                ) {
                /*Прикрепим главное изображение*/
                var src = Base64.decode(mainImg.FileName);
                conte.append('<div><a href="' + src + '" target="_blank"><img src="' + src + '" style="max-width: 200px; max-height: 200px;" /></a></div>');
                
            }
            
            var option = $('<div class="category-item-option" title="Параметры лота"><img src="pict/list_128.png" style="width: ' + imgSize + 'px; height: ' + imgSize + 'px;" /> </div>');
            optionsPanel.append(option);
            option.click(function(){_self.getFormParamsList();});
            
            var option = $('<div class="category-item-option" title="Список изображений"><img src="pict/camera_128.png" style="width: ' + imgSize + 'px; height: ' + imgSize + 'px;" /> </div>');
            optionsPanel.append(option);
            option.click(function(){_self.getFormImagesList();});
            
            style = 'margin-top: 10px;';
            conte.append('<div class="lot-data" style="' + style + '"></div>');
            
            this.htmlForm = conte;
            
            return conte;
        },
        getFormParamsList: function(){
            var conte = this.htmlForm.find('.lot-data:first');
            conte.empty();
            var modeName = 'lot-params';
            if (conte.get(0).mode != modeName) {
                linq(this.params._params).foreach(function(param){
                    if (param.Visible !== '1') {
                        return;
                    }
                    conte.append('<div class="lot-param"><span class="lot-param-name">' + 
                        (param.Caption ? Base64.decode(param.Caption) : Base64.decode(param.Name)) + 
                        '</span><span class="lot-param-value">' + 
                        (/*param.Value && typeof param.Value === typeof 'aaa' ? Base64.decode(param.Value) : */param.Value === null ? '' : param.Value) + 
                        '</span><div>'
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
                    var lm = new lot_image(image);
                    conte.append(lm.getHTMLForm());
                });
                conte.get(0).mode = modeName;
            }
            else {
                conte.get(0).mode = null;
            }
        },
        update: function(field, val){
            this.params[field] = val;
            var _self = this;
            $.ajax({
                url: 'index.php?mode=data&datakey=lot_update',
                type: 'POST',
                data: linq(this.params).select(function(v){ return typeof 'aaa' === typeof v ? Base64.encode(v) : v; }).collection,
                success: function(data){
                    linq(data).foreach(function(v, k){
                        typeof v === typeof 'aaa' && (data[k] = Base64.decode(v));
                    });
                    _self.params = data;
                }
            });
        },
        reload: function(){
            $.ajax({
                url: 'index.php?mode=data&datakey=lot',
                type: 'POST',
                data: {IdLot: this.params.IdLot},
                success: function(data){
                    if (typeof 'aaa' === typeof data) {
                        data = JSON.parse(data);
                    }
                    if (data) {
                        linq(data).foreach(function(v, k){
                            typeof v === typeof 'aaa' && (data[k] = Base64.decode(v));
                        });
                        _self.params = data;
                        _self.getHTMLForm();
                        _self.disabled(false);
                    }
                }
            });
        },
        disabled: function(state){
            if (state) {
                this.htmlForm.append('<div class="cover transparent-cover"></div>');
                
            }
            else {
                this.htmlForm.find('.cover').remove();
            }
        }
        
    };
    return _self;
};
