

auct_lot_param = function(params){
    var _self = {
        params: params,
        htmlForm: null,
        getHTMLForm: function(){
            /*Метод рисует форму для отображения аукциона на странице*/
            var _self = this;
            var style = '';
            var conte = $('<div class="auct-lot-param category-item"></div>');
            
            style = 'font-size: 140%; line-height: 140%; margin-bottom: 15px;';
            conte.append('<div style="' + style + '">Параметр ' + this.params.Name + '</div>');
            
            var block = $('<div class="auct-lot-param-field"><div class="param-label">Подпись</div><div class="param-value"><input type="text" /></div></div>');
            block.find('input').val(this.params.Caption || '').blur(function(){
                if ($(this).val() != _self.params.Caption) {
                    _self.update('Caption', $(this).val());
                }
            });
            conte.append(block);
            
            var block = $('<div class="auct-lot-param-field"><div class="param-label">Комментарий</div><div class="param-value"><textarea></textarea></div></div>');
            block.find('textarea').val(this.params.Comment || '').blur(function(){
                if ($(this).val() != _self.params.Comment) {
                    _self.update('Comment', $(this).val());
                }
            });
            conte.append(block);
            
            var block = $(
                '<div class="auct-lot-param-field"><div class="param-label">Показ</div>' + 
                '<div class="param-value" style="text-align: left;">' + 
                '<div style="white-space: nowrap;"><input type="radio" name="param-' + this.params.IdParam + 
                    '" id="param-' + this.params.IdParam + '-on"><label style="color: #6aaa6a;" for="param-' + 
                    this.params.IdParam + '-on">Включить</label></div>' + 
                '<div style="white-space: nowrap;"><input type="radio" name="param-' + this.params.IdParam + 
                    '" id="param-' + this.params.IdParam + '-off"><label style="color: #dd6a6a;" for="param-' + 
                    this.params.IdParam + '-off">Выключить</label></div>' + 
                '</div></div>');
            block.find('#param-' + this.params.IdParam + '-' + (this.params.Visible ? 'on' : 'off')).prop('checked', true);
            block.find('input').change(function(){
                if (this.checked) {
                    _self.update('Visible', this.id.indexOf('-on') > 0);
                }
            });
            
            conte.append(block);
            
            var block = $('<div class="auct-lot-param-field"><div class="param-label">Порядок сортировки при выводе</div><div class="param-value"><input class="negative-value" type="text" /></div></div>');
            var input = block.find('input');
            input.val(this.params.OrderNum || '0').blur(function(){
                if (($(this).val() || '0') != _self.params.OrderNum) {
                    _self.update('OrderNum', $(this).val() || '0');
                }
            });
            input.keypress(this.numericFields);
            conte.append(block);
            
            this.htmlForm = conte;
            
            return conte;
        },
        update: function(field, val){
            this.params[field] = val;
            var _self = this;
            $.ajax({
                url: 'index.php?mode=data&datakey=auct_params_update',
                type: 'POST',
                data: linq(this.params).select(function(v){ return typeof 'aaa' === typeof v ? Base64.encode(v) : v; }).collection,
                success: function(data){
                    if (typeof data === typeof 'aaa') {
                        data = JSON.parse(data.replace(/^\s+/, '').replace(/\s+$/, ''));
                    }
                    linq(data).foreach(function(v, k){
                        typeof v === typeof 'aaa' && (data[k] = Base64.decode(v));
                    });
                    _self.params = data;
                }
            });
        },
        numericFields: function(e){
            var $this = $(this);
            var enabledSymbols = {
                48: {counter: 0, symbol: '0'},
                49: {counter: 0, symbol: '1'},
                50: {counter: 0, symbol: '2'},
                51: {counter: 0, symbol: '3'},
                52: {counter: 0, symbol: '4'},
                53: {counter: 0, symbol: '5'},
                54: {counter: 0, symbol: '6'},
                55: {counter: 0, symbol: '7'},
                56: {counter: 0, symbol: '8'},
                57: {counter: 0, symbol: '9'}
            };
            if ($this.hasClass('float-value')) {
                enabledSymbols["44"] = {counter: 1, symbol: ','};
                enabledSymbols["46"] = {counter: 1, symbol: '.'};
            }
            if ($this.hasClass('negative-value')) {
                enabledSymbols["45"] = {counter: 1, symbol: '-'};

            }
            var charCode = 0;
            (e.which == null && (charCode = e.keyCode)) || (e.which != 0 && (charCode = e.which));
            if (!(charCode in enabledSymbols)) {
                if (!(charCode in enabledSymbols)) {
                    /*
                     * Некоторые браузеры обрабатывают нажатия Delete и Backspace через
                     * событие keypress, а некоторые так не поступают.
                     * Специально для этого случая проведем проверку
                     */
                    /*
                     * FF для Backspace вернул следующие значения
                     * which = 8
                     * charCode = 0
                     * key = "Backspace"
                     * keyCode = 8
                     * 
                     * для Delete
                     * which = 0
                     * charCode = 0
                     * key = "Delete"
                     * keyCode = 46
                     */
                    if (e.key && typeof e.key === typeof 'aaa' && e.key.toLowerCase() in {'delete': true, 'backspace': true}) {
                        /*позволим выполниться команде стирания символа*/
                        return true;
                    }
                    return false;
                }
                return false;
            }
            if (enabledSymbols[charCode].counter > 0) {
                /*Если указан ненулевой счетчик, значит символ можно вводить не более раз. 0 - количество таких символов не ограничено*/
                var matches = $this.val().match(new RegExp((enabledSymbols[charCode].symbol in {'.': true, ',': true} ? '[.,]' : enabledSymbols[charCode].symbol), 'ig'));
                if (matches && matches.length > enabledSymbols[charCode].counter - 1)  {
                    return false;
                }
            }
        }
    };
    return _self;
};
