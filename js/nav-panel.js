

$(function(){
    $('.main-nav-menu').find('li').click(function(){
        var key = $(this).attr('key');
        var base_class = $(this).attr('base_class');
        var categoryName = $(this).text();
        $.ajax({
            url: 'index.php?mode=data&datakey=' + key,
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
                var dataPanel = $('.data-panel:first');
                dataPanel.empty();
                
                var funcCtgrItemAppend = function(data){
                    if (data.records && data.records instanceof Array) {
                        linq(data.records).foreach(function(record){
                            linq(record).foreach(function(v, k){
                                typeof v === typeof 'aaa' && (record[k] = Base64.decode(v));
                            });
                            var instance = new window[base_class](record);
                            dataPanel.append(instance.getHTMLForm());
                        });
                    }
                };
                
                /*Устанавливаем панель для фильтрации*/
                if (base_class === 'lot') {
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
                            },
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
                                    }
                                ]
                            }
                        ],
                        change: function(data){
                            if (!data || !linq(data).firstKey(function(v,k){return true;})) {
                                return;
                            }
                            data.KeyLot && (data.KeyLot = Base64.encode(data.KeyLot));
                            data.VIN && (data.VIN = Base64.encode(data.VIN));
                            $.ajax({
                                url: 'index.php?mode=data&datakey=' + key,
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
                                    var dataPanel = $('.data-panel:first');
                                    dataPanel.children('.category-item').remove();

                                    funcCtgrItemAppend(data);
                                }
                            });
                        }
                    });
                    dataPanel.append(lot_filter.htmlForm);
                }
                
                /*Отрисовываем данные*/
                dataPanel.append('<div class="category-name">' + categoryName + '</div>');
                funcCtgrItemAppend(data);
                
            }
        });
                
    });
});

