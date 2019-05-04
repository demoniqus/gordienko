var sync = {
    parentWindow: null,
    lotParams: {},
    lotImages: {},
    errors: [],
    lot: null,
    auction_params: null,
    syncInstance: null,
    setParentWindow: function (data) {
        if (data.original && data.original.source && !this.syncInstance && !this.parentWindow) {
            this.lot = data.data.lot;
            this.auction_params = data.data.auction;
            /*Запомним идентификатор окна, в которое нужно отправлять полученную информацию*/
            this.parentWindow = data.original.source;
            /*Даем знать, что связь между окнами установлена*/
            this.parentWindow.postMessage({
                callback: this.getMainSynchronizerName() + '.stop_timer',
                contextCallback: this.getMainSynchronizerName(),
                timer: data.data.timerName
            }, '*');
            window.sync = new window['sync_' + this.auction_params.Name.toLowerCase()](this);
            window.sync.synchronize(data);
        }
    },
    getMainSynchronizerName: function () {
        return 'window.listSync.' + this.auction_params.Name.toLowerCase();
    },
    sendData: function (data) {
        if (data) {
            this.parentWindow.postMessage(data, '*');
        } else {
            this.savedData();
        }

    }
};


var sync_copart = function (instance) {
    instance.lotParamsUrls = ['/public/data/lotdetails/solr/', '/public/data/lotdetails/dynamic/'];
    /**
     * Адрес '/data/lotdetails/dynamic/' начал валиться с ошибкой Unauthorized, поэтому
     * необходимо подменить функции Copart'а, запрашивающие данные лота, чтобы иметь
     * возможность перехватить и обработать ошибки
     *
     */
    instance.lotParamsMethods = [
        {
            name: 'getLotDetails',
            action: function (lotNumber, callback) {
                jQuery.get('/public/data/lotdetails/solr/' + lotNumber, null, function(response){

                    callback && callback(response);
                })
            }
        },
        {
            name: 'getDynamicLotDetails',
            action: function (lotNumber, callback) {
                jQuery.ajax(
                    {
                        url: '/data/lotdetails/dynamic/' + lotNumber,
                        data: null,
                        type: "GET",
                        success: function(response) {

                            callback && callback(response);
                        }.bind(instance),
                        error: function(response) {
                            this.errors.push(response.responseJSON);
                            callback && callback(response);
                        }.bind(instance)
                    }
                );
            }
        }
    ];
    instance.index = 0;
    instance.angularService = null;
    instance.synchronize = function (data) {
        /*Аукцион Copart завязан за JS-framework Angular. Получим связь с этим framework'ом*/
        var to;
        var _self = this;
        var f = function () {
            clearTimeout(to);
            if (angular) {
                _self.angularService = angular
                    .element(document.querySelector('[ng-controller]'))
                    .injector()
                    .get('dataServiceG2');
            }
            if (!_self.angularService) {
                to = setTimeout(f, 150);
            } else {
                _self.nextLotParamsMethod();
            }
        };
        to = setTimeout(f, 0);
    };
    instance.nextLotParamsMethod = function () {
        var _self = this;
        if (this.index >= this.lotParamsUrls.length) {
            _self.loadImagesInfo();
        } else {
            this.lotParamsMethods[_self.index].action(
            // _self.angularService[this.lotParamsMethods[_self.index]](
                Base64.decode(_self.lot.Key),
                function (data) {
                    while ('data' in data) {
                        if (data.lotDetails) {
                            break;
                        }
                        data = data.data;
                    }
                    if (
                        data.lotDetails
                    ) {
                        linq(data.lotDetails).foreach(function (v, k) {
                            if (!(k in _self.lotParams)) {
                                _self.lotParams[k] = null;
                            }
                            if (_self.lotParams[k] === null || _self.lotParams[k] === undefined) {
                                _self.lotParams[k] = v;
                            }
                        });
                    }
                    else if (!data.responseJSON){
                        _self.errors.push({
                            error: 'Не найдены данные для импорта. Обратитесь к разработчику',
                            message: 'Не найдены данные для импорта. Обратитесь к разработчику',
                            path: window.location.href,

                        });
                    }
                    _self.index++;
                    _self.nextLotParamsMethod();
                }
            );
        }
    };
    instance.loadImagesInfo = function () {

        var _self = this;
        _self.angularService.getLotImages(
            Base64.decode(_self.lot.Key),
            function (data) {
                if (
                    data &&
                    data.data &&
                    data.data.data &&
                    data.data.data.imagesList &&
                    data.data.data.imagesList.FULL_IMAGE
                ) {
                    _self.lotImages = data.data.data.imagesList.FULL_IMAGE
                }
                /*Все данные собраны. Отдадим их на главную страницу для начала синхронизации лота*/
                _self.sendData();
            },
            null
        );
    };

    instance.savedData = function (callback) {
        /*
         * Выдергиваем дату аукциона. На начало мая 2017 она лежит с ключом ad и представляет 
         * из себя количество миллисекунд с 1970 года.
         */
        if (this.lotParams.ad) {
            try {
                var date = new Date(this.lotParams.ad);
                var m = date.getMonth() + 1;
                /*Пробелы использую, чтобы гарантировать, что не произойдет автоматического преобразования строковых значений в числа*/
                m < 10 && (m = '0' + '' + m);
                var d = date.getDate();
                d < 10 && (d = '0' + '' + d);
                var y = date.getFullYear();
                this.lotParams.auctiondate = m + '' + d + '' + y;
            } catch (e) {

            }
        }

        instance.sendData({
            auction: this.auction_params,
            lot: this.lot,
            lotParams: this.lotParams,
            lotImages: this.lotImages,
            errors: this.errors,
            callback: this.getMainSynchronizerName() + '.saveData',
            contextCallback: this.getMainSynchronizerName()

        });
    };

    return instance;
};

var sync_iaai = function (instance) {

    instance.synchronize = function (data) {
        /*Проверим, есть ли реальная активная авторизованная сессия*/
        if (/^[^?]+login/i.test(window.location.href)) {
            this.parentWindow.postMessage({
                callback: this.getMainSynchronizerName() + '.needAuthorize',
                contextCallback: this.getMainSynchronizerName()
            }, '*');
            return;
        }
        if (
            /^[^?]+vehicle/i.test(window.location.href) &&
            /^[^?]+\?.*itemid/i.test(window.location.href)
        ) {
            this.sendData();
        } else if (/^[^?]+purchase/i.test(window.location.href)) {
            this.getLotList();
        }
    };
    instance.getLotList = function () {
        var _self = this;
        var listLot = [];
        /*
         * Получим список лотов для синхронизации из текста скрипта из текста страницы, 
         * т.к. при изменении фильтра это самый актуальный список
         */
        var listLot = linq(_self.getMainTable().find('tr'))
            .where(function (row) {
                return /stockitem/i.test(row.id);
            })
            .select(function (row) {
                return _self.extractLotInfoFromTableRow(row);
            })
            .where(function (lot) {
                return !!lot.StockNo && !!lot.OaAuctionItemId;
            })
            .collection;
        /*Теперь нужно собрать данные по фильтрам*/
        var filters = [
            {
                id: 'filter-date',
                type: 'radio',
                elements: linq($('#filter-date').children())
                    .select(function (li) {
                        var input = $(li).find('input:first').get(0);
                        var label = $(li).find('label:first').get(0);
                        var fElem = {
                            id: input.id,
                            name: input.name,
                            value: input.value,
                            label: label.innerHTML,
                            checked: input.checked
                        };
                        return fElem;
                    })
                    .collection
            }
        ];
        /*Собираем информацию о пагинации*/
        var pagesContainer = $('#dvPurchasehistoryList .pagination');
        var pagesInfo = [];
        if (pagesContainer.length > 0) {
            pagesInfo = linq(pagesContainer.children())
                .select(function (p) {

                    var a = $(p).find('a:first').get(0);
                    if (!a) {
                        return {};
                    }
                    var c = a.innerHTML;
                    var i = c;
                    var pIndex = a.attributes['onclick'];
                    if (pIndex) {
                        pIndex = pIndex.value.match(/\(\s*\d+\s*\)/);
                        if (pIndex) {
                            i = pIndex[0].replace(/[^\d]+/ig, '');
                        }
                    }
                    return {
                        caption: c,
                        index: i,
                        active: $(p).hasClass('active')
                    };
                })
                .collection;
        }

        /*Данные собрали - отправляем их на основную страницу*/
        this.parentWindow.postMessage({
            listLot: listLot,
            filters: filters,
            callback: this.getMainSynchronizerName() + '.previewLotList',
            contextCallback: this.getMainSynchronizerName(),
            pages: pagesInfo
        }, '*');
    };
    instance.savedData = function () {
        var sData = {
            callback: this.getMainSynchronizerName() + '.saveData',
            contextCallback: this.getMainSynchronizerName(),
            lotParams: window.vm,
            lotImages: null,
            _dataURL: Base64.encode(window.location.href)
        };
        /*Получим список изображений*/
        var indicator = /deepzoomind/i;
        var script = linq($('script')).first(function (scr) {
            return scr.childNodes.length > 0 &&
                linq(scr.childNodes).first(function (cn) {
                    return cn.nodeType === 3 &&
                        cn.nodeValue &&
                        indicator.test(cn.nodeValue);
                }) !== null;
        });
        if (script) {
            var script_text = linq(script.childNodes).first(function (cn) {
                return cn.nodeType === 3 &&
                    cn.nodeValue &&
                    indicator.test(cn.nodeValue);
            }).nodeValue;
            script_text = script_text.split(/\s*var\s+[a-z_][a-z_0-9]*\s*=/i);
            script_text = linq(script_text).first(function (row) {
                return indicator.test(row);
            });
            if (script_text) {
                eval('sData.lotImages = ' + script_text);
                if (!sData.lotImages || typeof {} !== typeof sData.lotImages || !('keys' in sData.lotImages)) {
                    sData.lotImages = null;
                }
            }
            instance.sendData(sData);
        } else if (
            window.IMAGEMODULE &&
            window.IMAGEMODULE.GetImageSection &&
            typeof function () {
            } === typeof window.IMAGEMODULE.GetImageSection &&
            window.GetDimensions
        ) {
            window.IMAGEMODULE.GetImageSection = function (stockNumber, branchCode, salvageId, imgUrl) {
                var data = {
                    stockNumber: stockNumber,
                    branchCode: branchCode,
                    salvageId: salvageId
                };
                $.ajax({
                    url: window.GetDimensions,
                    type: 'GET',
                    async: false,
                    cache: false,
                    contentType: 'application/json; charset=utf-8',
                    data: {"json": JSON.stringify(data)},
                    success: function (data) {
                        if (data && data.keys && data.keys[0]) {
                        } else {
                            data = {keys: []};
                        }
                        sData.lotImages = data;
                        instance.sendData(sData);
                    }
                });
            };
            window.IMAGEMODULE.GetImageSection(
                window.vm.VehicleDetailsViewModel.StockNo,
                window.vm.VehicleDetailsViewModel.BranchCode,
                window.vm.VehicleDetailsViewModel.SalvageID,
                'https://vis.iaai.com:443/'
            );
        }

    };
    instance.getMainTable = function () {
        return jQuery('#dvPurchasehistoryList .table');
    };
    instance.extractLotInfoFromTableRow = function (row) {
        /*Получим ссылку на превьюху изображения*/
        var img = $(row).find('img.lazy:first').get(0);
        var imgSrc = null;
        if (img) {
            imgSrc = img.src;
            if (!imgSrc) {
                var attr = img.attributes['data-original'];
                attr && (imgSrc = attr.value);
            }
            if (imgSrc && !(/^http/i.test(imgSrc))) {
                imgSrc = window.location.protocol + '//' + (window.location.host || window.location.hostname) + (imgSrc[0] in {
                    '/': true,
                    '\\': true
                } ? '' : '/') + imgSrc;
            }
        }
        /*Получим идентификатор, по которому можно загрузить страницу лота*/
        var a = linq($(row).find('a')).first(function (link) {
            return /vehicledetail/i.test(link.attributes['onclick'].value);
        });
        var OaAuctionItemId = null;
        if (a) {
            var matches = a.attributes['onclick'].value.match(/^[^'"]*['"]([^'"]+)/);
            if (matches && matches.length > 1) {
                OaAuctionItemId = matches[1];
            }
        }
        /*Получим ключ лота и Caption для показа пользователю*/
        var StockNo = null;
        var Caption = '';

        var el = linq($(row).find('*')).first(function (e) {
            return linq(e.childNodes).first(function (child) {
                return child.nodeType === 3 && child.nodeValue && /stock\s*[#:][#:]?/i.test(child.nodeValue);
            }) !== null;
        });
        if (el) {
            StockNo = linq(el.childNodes).first(function (child) {
                return child.nodeType === 3 && child.nodeValue && /stock\s*[#:][#:]?/i.test(child.nodeValue);
            }).nodeValue
                .replace(/stock/ig, '')
                .replace(/[:#-]/g, '')
                .replace(/^\s+/g, '')
                .replace(/\s+$/g, '');
            Caption = linq($(el).parents('td:first').find('*')).reduce(function (res, domel) {
                return res + linq(domel.childNodes)
                    .where(function (child) {
                        return child.nodeType === 3 && child.nodeValue && child.nodeValue.replace(/^\s+/g, '').replace(/\s+$/g, '') !== '';
                    })
                    .select(function (child) {
                        return '<p>' + child.nodeValue.replace(/^\s+/g, '').replace(/\s+$/g, '') + '</p>';
                    }).collection.join('');
            }, Caption);
        }
        return {
            __previewLotImg: imgSrc,
            OaAuctionItemId: OaAuctionItemId,
            StockNo: StockNo,
            Caption: Caption
        };
    };
    instance.changeFilterState = function (data) {
        var _self = this;
        var filter = data.data;
        /*Чтобы понять, что новые данные загрузились, предварительно удалим старые*/
        _self.getMainTable().empty();
        jQuery('#' + filter.IdFilter).find('#' + filter.elemId).prop('checked', filter.checked);
        /*На всякий случай принудительно вызываем событие изменения*/
        jQuery('#' + filter.IdFilter).find('#' + filter.elemId).change();
        /*А теперь ждем, пока не произведется загрузка данных по новому фильтру*/
        _self.waitSyncAfterReload();
    };
    instance.changePage = function (data) {
        if (window.gotoPage && typeof function () {
        } === typeof window.gotoPage) {
            var pageInfo = data.data;
            var _self = this;
            /*Чтобы понять, что новые данные загрузились, предварительно удалим старые*/
            _self.getMainTable().empty();
            /*Вызываем перезагрузку списка лотов с учетом выбранной страницы*/
            window.gotoPage(+pageInfo.index);
            /*А теперь ждем, пока не произведется загрузка данных по новому фильтру*/
            _self.waitSyncAfterReload();
        }
    };
    instance.waitSyncAfterReload = function () {
        var _self = this;
        var to;
        var f = function () {
            clearTimeout(to);
            /*
             * Ожидаем до тех пор, пока в главной таблице не появятся дочерние элементы 
             * !!! Здесь можно сделать проверку, что количество дочерних элементов перестало 
             * изменяться на случай, если они будут довольно медленно создаваться.
             */
            if (_self.getMainTable().children().length > 0) {
                /*Данные загрузились и можно заново их синхронизировать*/
                _self.synchronize();
            } else {
                to = setTimeout(f, 500);
            }
        };
        to = setTimeout(f, 500);

    };


    return instance;
};

//
//
// setTimeout(function(){
//     if (window.parent !== window) {
//         console.log(window.parent.crossDomainQuery)
//     }
//     // let item = document.createElement('DIV');
//     // item.classList.add('DEMONIQUS');
//     // document.body.appendChild(item);
// }, 1500);