

/*
 * базовый прототип для многих объектов
 */
base_class = function(){
    var _self = {
        _disabled: false,
        params: null,
        htmlForm: null,
        reloadURL: null,
        updateURL: null,
        getHTMLForm: function(){
            if (!this.htmlForm) {
                this.htmlForm = $('<div class="category-item"></div>');
            }
            
            var conte = this.htmlForm;
            conte.empty();
            
            return conte;
        },
        update: function(field, val){
            if (!this.updateURL) {
                throw 'Не указан адрес для функции обновления объекта';
                return;
            }
            this.params[field] = val;
            var _self = this;
            $.ajax({
                url: _self.updateURL,
                type: 'POST',
                data: _self.paramsToPOST(),
                success: function(data){
                    _self.reload();
                }
            });
        },
        reload: function(callback){
            if (!this.reloadURL) {
                throw 'Не указан адрес для функции обновления информации об объекте';
                return;
            }
            $.ajax({
                url: this.reloadURL,
                type: 'POST',
                data: _self.paramsToPOST(),
                success: function(data){
                    if (typeof 'aaa' === typeof data) {
                        data = JSON.parse(data);
                    }
                    if (data) {
                        
                        linq(data).foreach(function(v, k){
                            typeof v === typeof 'aaa' && (data[k] = Base64.decode(v));
                            if (k[0] === '_' && v && typeof v === typeof {}) {
                                (linq(v)).foreach(function(_v){
                                    (linq(_v)).foreach(function(__v, __k){
                                        typeof __v === typeof 'aaa' && (_v[__k] = Base64.decode(__v));
                                    });
                                    
                                });
                            }
                        });
                        _self.params = data;
                        _self.getHTMLForm();
                        _self.disabled(false);
                        typeof function(){} === typeof callback && (callback(_self));
                    }
                }
            });
        },
        paramsToPOST: function(){
            return linq(this.params)
                .where(function(v, k){ return k[0] !== '_';})
                .select(function(v){ return typeof 'aaa' === typeof v ? Base64.encode(v) : v; })
                .collection;
        },
        disabled: function(state){
            if (state) {
                this.htmlForm.addClass('disabled');
                
            }
            else {
                this.htmlForm.removeClass('disabled');
            }
            this._disabled = state;
        }
    };
    
    
    return _self;
};

base_new_class = function(){
    var _self = {
        disable: false,
        params: null,
        htmlForm: null,
        saveURL: null,
        saveCallback: function(classPrototype, data){
            var instance = new classPrototype(data);
            $(dataPanel).append(instance.getHTMLForm());
            instance.reload();
        },
        editedInstance: null,
        getHTMLForm: function(){
            if (!this.htmlForm) {
                this.htmlForm = $('<div class="auction category-item"></div>');
            }
            
            var conte = this.htmlForm;
            conte.empty();
            
            return conte;
        },
        getFixedCoverForm: function(){
            var _self = this;
            var res = $('<div class="fixed-cover"><div class="fixed-cover-background"></div><div class="fixed-cover-workarea"></div></div>');
            res.find('.fixed-cover-background').click(function(){
                $(this).parent().remove();
            });
            return res;
        },
        update: function(field, val){
            this.params[field] = val;
        },
        save: function(){
//            if (this.disable) {
//                return;
//            }
            if (!this.saveURL) {
                throw 'Не указан адрес для функции сохранения объекта';
                return;
            }
            var _self = this;
//            _self.disabled(true);
            var sdata = _self.paramsToPOST();
            (typeof function(){} === typeof this._save) && (sdata = this._save(sdata));
            if (sdata === false) {
                return;
            }
            $.ajax({
                url: this.saveURL,
                type: 'POST',
                data: sdata,
                success: function(data){
                    if (typeof 'aaa' === typeof data) {
                        data = JSON.parse(data);
                    }
                    if (data) {
                        linq(data).foreach(function(v, k){
                            typeof v === typeof 'aaa' && (data[k] = Base64.decode(v));
                        });
                        if (data.error) {
                            alert(data.error);
                            _self.disabled(false);
                            _self.disable = false;
                        }
                        else {
                            _self.saveCallback(_self, data);
                            _self.htmlForm.remove();
                        }
                    }
                }
            });
        },
        paramsToPOST: function(){
            return linq(this.params)
                .where(function(v, k){ return k[0] !== '_';})
                .select(function(v){ return typeof 'aaa' === typeof v ? Base64.encode(v) : v; })
                .collection;
        },
        disabled: function(state){
            if (state) {
                this.htmlForm.find('.fixed-cover-workarea:first').append('<div class="cover transparent-cover"></div>');
                this.disable = true;
                
            }
            else {
                this.htmlForm.find('.fixed-cover-workarea:first').find('.cover').remove();
                this.disable = false;
            }
        }
    };
    
    
    return _self;
};
