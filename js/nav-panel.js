

$(function(){
    $('.main-nav-menu').find('li').click(function(){
        var key = $(this).attr('key');
        var base_class = $(this).attr('base_class');
        var categoryName = $(this).text();
        var acceptData = function(data){
            if (!data) {
                return null;
            }
            try {
                return JSON.parse(data.replace(/^\s+/ig, '').replace(/\s+$/ig, ''));
            }
            catch (e) {
                return null;
            }
        };
        var funcCtgrItemAppend = function(data, bc, appendToDOM){
            if (data.records && data.records instanceof Array) {
                linq(data.records).foreach(function(record){
                    linq(record).foreach(function(v, k){
                        typeof v === typeof 'aaa' && (record[k] = Base64.decode(v));
                    });
                    var instance = new window[bc](record);
                    var to = setTimeout(function(){
                        clearTimeout(to);
                        appendToDOM && $(dataPanel).append(instance.getHTMLForm());
                    }, 0);
                });
            }
        };
        switch (base_class.toLowerCase()) {
            case 'lot':
                /*Для списка лотов требуется и информация об аукционах*/
                $.ajax({
                    url: 'index.php?mode=data&datakey=auctiones_list',
                    type: 'POST',
                    success: function(data){
                        data = acceptData(data);
                        if (!data) {
                            return;
                        }
                        $(dataPanel).empty();
                        
                        funcCtgrItemAppend(data, 'auction', false);
                        
                        /*А теперь загрузим лоты*/
                        $.ajax({
                            url: 'index.php?mode=data&datakey=' + key,
                            type: 'POST',
                            success: function(data){
                                data = acceptData(data);
                                if (!data) {
                                    return;
                                }
                                $(dataPanel).empty();

                                /*Устанавливаем панель для фильтрации*/
                                allLotsListFilter({
                                    key: key,
                                    funcCtgrItemAppend: funcCtgrItemAppend,
                                    base_class: base_class
                                });

                                /*Отрисовываем данные*/
                                $(dataPanel).append('<div class="category-name">' + categoryName + '</div>');
                                funcCtgrItemAppend(data, base_class, true);
                                dataPanel.setViewMode();

                            }
                        });

                    }
                });
                break;
            default:
                
                $.ajax({
                    url: 'index.php?mode=data&datakey=' + key,
                    type: 'POST',
                    success: function(data){
                        data = acceptData(data);
                        if (!data) {
                            return;
                        }
                        $(dataPanel).empty();
                        
                        /*Отрисовываем данные*/
                        $(dataPanel).append('<div class="category-name">' + categoryName + '</div>');
                        funcCtgrItemAppend(data, base_class, true);

                    }
                });
                break;
        }
                
    });
    
    window.dataPanel = $('.data-panel:first').get(0);
    window.dataPanel._viewMode = 'table';
    window.dataPanel.getItems = function(){
        return linq($(this).find('.category-item')).select(function(elem){ return elem.categoryItem}).collection;
    };
    window.dataPanel.setViewMode = function(mode, item){
        switch (mode || this._viewMode) {
            case 'tail':
                linq(item ? [item] :dataPanel.getItems()).foreach(function(item){
                    item.htmlForm.removeClass('view-table');
                });
                break;
            case 'table':
                linq(item ? [item] :dataPanel.getItems()).foreach(function(item){
                    item.htmlForm.addClass('view-table');
                });
                break;
        }
        dataPanel._viewMode = mode || dataPanel._viewMode;
    };
    /*Запускаем очистку временных файлов*/
    $.ajax({
        url: 'index.php?mode=data&datakey=empty_tempfiles',
        type: 'POST',
        success: function(){}
    });
    
});

/*Для поддержания активной сессии при отсутствии активности пользователя поставим таймаут*/
sessionTimeout = function(){
    sessionTimeout.to && clearTimeout(sessionTimeout.to);
    $.ajax({
        url: 'index.php?mode=data&datakey=session',
        type: 'POST',
        success: function(){}
    });
    sessionTimeout.to = setTimeout(sessionTimeout, 15000);
};
sessionTimeout.to = setTimeout(sessionTimeout, 15000);