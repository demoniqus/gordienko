



auct_lot_param = function(params){
    return $.extend(new base_class(), {
        params: params,
        reloadURL: 'index.php?mode=data&datakey=auct_params',
        updateURL: 'index.php?mode=data&datakey=auct_params_update',
        getHTMLForm: function(formType){
            formType = !formType || formType.toLowerCase() !== 'card' ? 'tablerow' : 'card';
            formType = formType.toLowerCase();
            switch (formType) {
                case 'card':
                    return this.getCardHTMLForm();
                case 'tablerow':
                    return this.getTableRowHTMLForm();
            }
        },
        getTableRowHTMLForm: function(){
            /*Метод рисует форму для отображения аукциона на странице*/
            var _self = this;
            var style = '';
            var conte = _self.htmlForm && _self.htmlForm.get(0).tagName.toLowerCase() === 'tr' ? 
                _self.htmlForm : 
                $('<tr class="auct-lot-param category-item"></tr>');
            conte.empty();
            conte.get(0).categoryItem = this;
            
            conte.append('<td class="cell35" style="' + style + '">' + this.params.Name + '</td>');
            
            var cell = $('<td class="cell30"><input type="text" class="cell-full-width"/></td>');
            cell.find('input').val(this.params.Caption || '').blur(function(){
                if ($(this).val() != _self.params.Caption) {
                    _self.update('Caption', $(this).val());
                }
            });
            conte.append(cell);
            
            var cell = $('<td class="cell20"><textarea rows="3" class="cell-full-width"></textarea></td>');
            cell.find('textarea').val(this.params.Comment || '').blur(function(){
                if ($(this).val() != _self.params.Comment) {
                    _self.update('Comment', $(this).val());
                }
            });
            conte.append(cell);
            
            var cell = $('<td class="cell10"><input class="negative-value" type="text" size="5"/></td>');
            var input = cell.find('input');
            input.val(this.params.OrderNum || '0').blur(function(){
                if (($(this).val() || '0') != _self.params.OrderNum) {
                    _self.update('OrderNum', $(this).val() || '0');
                }
            });
            input.keypress(lib.numericFields);
            conte.append(cell);
            
            var cell = $('<td class="cell5"></td>');
            conte.append(cell);
            var visibleBtn = null;
            this.params.Visible ? 
                (
                visibleBtn = $('<img class="param-icon" src="pict/visible_128.png" title="Скрыть параметр"/>'),
                conte.removeClass('unvisible')
                ) :
                (
                visibleBtn = $('<img class="param-icon" src="pict/unvisible_128.png" title="Сделать параметр доступным"/>'),
                conte.addClass('unvisible')
                );
            visibleBtn.click(function(){
                _self.update('Visible', !_self.params.Visible);
            });
            cell.append(visibleBtn);
            
            this.htmlForm = conte;
            
            return conte;
        },
        getCardHTMLForm: function(){
            /*Метод рисует форму для отображения аукциона на странице*/
            var _self = this;
            var style = '';
            var conte = _self.htmlForm || $('<div class="auct-lot-param category-item"></div>');
            conte.empty();
            conte.get(0).categoryItem = this;
            
            var topPanel = $('<div class="top-panel"></div>');
            conte.append(topPanel);
            
            var visibleBtn = null;
            this.params.Visible ? 
                (
                visibleBtn = $('<img src="pict/visible_128.png" title="Скрыть параметр"/>'),
                conte.removeClass('unvisible')
                ) :
                (
                visibleBtn = $('<img src="pict/unvisible_128.png" title="Сделать параметр доступным"/>'),
                conte.addClass('unvisible')
                );
            visibleBtn.click(function(){
                _self.update('Visible', !_self.params.Visible);
            });
            topPanel.append(visibleBtn);
            
            style = 'font-size: 140%; line-height: 140%; margin-bottom: 15px; word-wrap: break-word;';
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
            
            var block = $('<div class="auct-lot-param-field"><div class="param-label">Порядок сортировки при выводе</div><div class="param-value"><input class="negative-value" type="text" /></div></div>');
            var input = block.find('input');
            input.val(this.params.OrderNum || '0').blur(function(){
                if (($(this).val() || '0') != _self.params.OrderNum) {
                    _self.update('OrderNum', $(this).val() || '0');
                }
            });
            input.keypress(lib.numericFields);
            conte.append(block);
            
            this.htmlForm = conte;
            
            return conte;
        }
        
    });
};

$.extend(auct_lot_param, new base_new_class(), {
    entityName: 'auction_params', 
    saveCallback: function(classPrototype, data){
        var instance = new classPrototype(data);
        $(dataPanel).find('.table-category-items:first').append(instance.getHTMLForm('tablerow'));
        instance.reload();
    },
    'new': function(auctionInstance){
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
    getHTMLForm: function(formType){
        formType = !formType || formType.toLowerCase() !== 'card' ? 'table' : 'card';
        formType = formType.toLowerCase();
        /*Метод рисует форму для отображения аукциона на странице*/
        var _self = this;
        var style = '';
        var fixedCoverForm = _self.getFixedCoverForm();
        var _conte = fixedCoverForm.find('.fixed-cover-workarea:first');
        _conte.empty();
        var conte = $('<div class="auct-lot-param category-item"></div>');
        _conte.append(conte);
        
        conte.get(0).categoryItem = this;

        style = 'font-size: 140%; line-height: 140%; margin-bottom: 15px;';
        conte.append('<div style="' + style + '">Создание нового параметра</div>');
        
        
        var topPanel = $('<div class="top-panel"></div>');
        conte.append(topPanel);

        var visibleBtn = null;
        this.params.Visible ? 
            (
            visibleBtn = $('<img src="pict/visible_128.png" title="Скрыть параметр"/>'),
            conte.removeClass('unvisible')
            ) :
            (
            visibleBtn = $('<img src="pict/unvisible_128.png" title="Сделать параметр доступным"/>'),
            conte.addClass('unvisible')
            );
        visibleBtn.click(function(){
            _self.update('Visible', !_self.params.Visible);
            conte[_self.params.Visible ? 'removeClass': 'addClass']('unvisible');
            this.src = _self.params.Visible ? 'pict/visible_128.png' : 'pict/unvisible_128.png';
        });
        topPanel.append(visibleBtn);
        

        var block = $('<div class="auct-lot-param-field"><div class="param-label">Параметр</div><div class="param-value"><input type="text" /></div></div>');
        block.find('input').val(this.params.Name || '').blur(function(){
            var newVal = $(this).val();
            if (newVal != _self.params.Name) {
                if(newVal.replace(/\s+/g, '') && !(/^\s*[_a-z][_a-z0-9]*\s*$/i.test(newVal))) {
                    /*На пустую строку не ругаемся при blur-событии - просто не позволим ее сохранить при отправке на сервер*/
                    alert('Наименование параметра должно начинаться с символа подчеркивания или латинской буквы.' +
                        'Остальные символы могут быть символом подчеркивания, цифрами и латинскими буквами');
                    return;
                }
                _self.update('Name', $(this).val());
            }
        });
        conte.append(block);
        
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

        var block = $('<div class="auct-lot-param-field"><div class="param-label">Порядок сортировки при выводе</div><div class="param-value"><input class="negative-value" type="text" /></div></div>');
        var input = block.find('input');
        input.val(this.params.OrderNum || '0').blur(function(){
            if (($(this).val() || '0') != _self.params.OrderNum) {
                _self.update('OrderNum', $(this).val() || '0');
            }
        });
        input.keypress(this.numericFields);
        conte.append(block);
        
        var block = $('<div style="text-align: center;"></div>');
        var applyBtn = $('<a class="filter-apply-btn apply-btn" >Сохранить</a>');
        block.append(applyBtn);
        applyBtn.click(function(){
            if(!(/^\s*[_a-z][_a-z0-9]\s*$/i.test(_self.params.Name))) {
                alert('Наименование параметра может начинаться с символа подчеркивания или латинской буквы.' +
                    'Остальные символы могут быть символом подчеркивания, цифрами и латинскими буквами');
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

        this.htmlForm = fixedCoverForm;

        return fixedCoverForm;
    }
});