

filter = function(params){
    
    var _self = {
        params: params,
        htmlForm: null,
        icon: null,
        value: {},
        getHTMLForm: function(){
            
            var style = '';
            var conte = $(
                '<div class="filter-panel"><div class="filter-fields"></div>' + 
                '<div class="filter-label"><img src="pict/filter_128.png" /></div></div>'
            );
            
            var c = conte.find('.filter-fields:first');
            for (var i in this.params.fields) {
                var field = this.params.fields[i];
                c.append(this['getFilterForm_' + field.type.toLowerCase()](field));
            }
            
            var div = $('<div style="text-align: center; margin-top: 10px;"></div>');
            var applyBtn = $('<img class="filter-apply-btn" src="pict/apply-btn.png" />');
            
            div.append(applyBtn);
            c.append(div);
            
            
            var _self = this;
            applyBtn.click(function(){
                typeof function(){} === typeof _self.params.change && _self.params.change(_self.clone(_self.value));
            });
            
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
        }
        
    };
    _self.getHTMLForm();
    return _self;
};
