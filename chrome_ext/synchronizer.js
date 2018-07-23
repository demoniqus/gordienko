


var sync = {
    parentWindow: null,
    lotParams: {},
    lotParamsUrls: ['/public/data/lotdetails/solr/', '/public/data/lotdetails/dynamic/'],
    lotParamsMethods: ['getLotDetails', 'getDynamicLotDetails'], 
    index: 0,
    lotImages: {},
    lot: null,
    angularService: null,
    setParentWindow: function(data) {
        if (data.original && data.original.source) {
            /*Запомним идентификатор окна, в которое нужно отправлять полученную информацию*/
            this.parentWindow = data.original.source;
            /*Даем знать, что связь между окнами установлена*/
            this.parentWindow.postMessage({callback: 'synchronizer.stop_timer', timer: 'windows_connect'}, '*');
            this.lot = data.data.lot;
            /*Раз есть идентификатор окна, есть смысл собирать данные и отправлять их в хранилище*/
            /*Дождемся появления jQuery*/
            var to;
            var _self = this;
            var f = function(){
                clearTimeout(to);
                if (angular) {
                    _self.angularService = angular
                        .element(document.querySelector('[ng-controller]'))
                        .injector()
                        .get('dataServiceG2');
                }
                if (!_self.angularService) {
                    to = setTimeout(f, 150);
                }
                else {
                    _self.nextLotParamsMethod();
                }
            };
            to = setTimeout(f, 0);
            
        }
    },
    nextLotParamsMethod: function(){
        var _self = this;
        if (this.index >= this.lotParamsUrls.length) {
            _self.loadImagesInfo();
        }
        else {
            _self.angularService[this.lotParamsMethods[_self.index]](
                Base64.decode(_self.lot.Key), 
                function(data){
                    if (
                            data && 
                            data.data && 
                            data.data.data && 
                            data.data.data.lotDetails
                        ) {
                        linq(data.data.data.lotDetails).foreach(function(v, k){
                            if (!(k in _self.lotParams)) {
                                _self.lotParams[k] = null;
                            }
                            if (_self.lotParams[k] === null || _self.lotParams[k] === undefined) {
                                _self.lotParams[k] = v;
                            }
                        });
                    }
                    _self.index++;
                    _self.nextLotParamsMethod();
                }
            );
        }
    },
    loadImagesInfo: function(){
        
        var _self = this;
        _self.angularService.getLotImages(
            Base64.decode(_self.lot.Key), 
            function(data){
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
        
    },
    sendData: function(){
        this.parentWindow.postMessage({
            lot: this.lot,
            lotParams: this.lotParams,
            lotImages: this.lotImages,
            callback: 'synchronizer.saveData'
        }, '*');
    }
};



