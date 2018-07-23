

lot_image = function(params){
    var _self = {
        params: params,
        htmlForm: null,
        mainFlagImg: null,
        getHTMLForm: function(){
            var imageConte = $('<div class="lot-image"></div>');
            var src = Base64.decode(this.params.FileName);
            imageConte.append('<a href="' + src + '" target="_blank"><img class="lot-img" src="' + src + '" /></a>');
            this.htmlForm = imageConte;
            /*Через данную ссылку можно будет снимать флаг IsMain у других изображений лота, если флаг установлен текущему*/
            imageConte.get(0).lot_image = this;
            this.mainFlagImg = document.createElement('IMG');
            imageConte.append(this.mainFlagImg);
            
            this.mainFlagImg.setState = function(isMain){
                
                if (isMain) {
                    $(this).removeClass('main-image-flag').addClass('slave-image-flag');
                    this.src = 'pict/checked_green.png';
                    this.title = 'Главное изображение';
                }
                else {
                    $(this).removeClass('slave-image-flag').addClass('main-image-flag');
                    this.src = 'pict/checked_grey.png';
                    this.title = 'Сделать главным изображением';
                }
            };
            
            this.mainFlagImg.setState(this.IsMain());
            var _self = this;
            $(this.mainFlagImg).click(function(){
                _self.main(!_self.IsMain());
            });
            
            return imageConte;
        },
        main: function(flagState){
            if (flagState) {
                /*Отключаем флаг у всех изображений кроме текущего*/
                linq(this.htmlForm.parent().find('.lot-image')).foreach(function(conte){
                    conte.lot_image.main(false);
                });
            }
            this.mainFlagImg.setState(flagState);
            this.update('IsMain', !!flagState);
        },
        visible: function(flagState){
            this.update('Visible', !!flagState);
        },
        update: function(field, val){
            this.params[field] = val;
            var _self = this;
            $.ajax({
                url: 'index.php?mode=data&datakey=lot_image_update',
                type: 'POST',
                data: this.params,
                success: function(data){
                    //_self.params = data;
                }
            });
        },
        IsMain: function(){
            return (typeof 'aaa' === typeof this.params.IsMain && this.params.IsMain === '1') || 
                (typeof true === typeof this.params.IsMain && this.params.IsMain);
        },
        IsVisible: function(){
            return (typeof 'aaa' === typeof this.params.Visible && this.params.Visible === '1') || 
                (typeof true === typeof this.params.Visible && this.params.Visible);
        }
    };
    return _self;
};
