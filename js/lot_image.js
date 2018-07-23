

lot_image = function(params){
    return $.extend(new base_class(), {
        params: params,
        reloadURL: 'index.php?mode=data&datakey=lot_image',
        updateURL: 'index.php?mode=data&datakey=lot_image_update',
        mainFlagImg: null,
        getHTMLForm: function(){
            var flag = false;
            !(this.htmlForm) && (this.htmlForm = $('<div class="lot-image"></div>'), flag = true);
            var imageConte = this.htmlForm;
            if (flag) {
                /*Для изображения нет смысла заново переделывать форму - достаточно изменить состояние некоторых флагов*/
                imageConte.empty();

                var src = this.params.FileName;
                imageConte.append('<a href="' + src + '" target="_blank"><img class="lot-img" src="' + src + '" /></a>');
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

                
                var _self = this;
                $(this.mainFlagImg).click(function(){
                    _self.main(!_self.IsMain());
                });

                var deleteImgIcon = document.createElement('IMG');
                deleteImgIcon.src = 'pict/trash_red_round_128.png';
                deleteImgIcon.title = 'Удалить изображение';
                imageConte.append(deleteImgIcon);
                $(deleteImgIcon).addClass('delete-img-button').click(function(){
                    if (confirm('Вы уверены, что хотите удалить изображение без возможности восстановления?')) {
                        $.ajax({
                            url: 'index.php?mode=data&datakey=delete_objects',
                            type: 'POST',
                            data: {ObjectType: 'lot_images', ObjectsIdList: [_self.params.IdImage]},
                            success: function(data){
                                if (typeof 'aaa' === typeof data) {
                                    data = JSON.parse(data);
                                }
                                if (data && data.success) {
                                    /*Удалим из лота информацию об изображении*/
                                    var lot = _self.htmlForm.parents('.lot.category-item:first').get(0).categoryItem;
                                    lot.reload(function(lot){
                                        lot.getFormImagesList();
                                    });
                                }
                            }
                        });
                    }
                });
            }
            this.mainFlagImg.setState(this.IsMain());
            return imageConte;
        },
        main: function(flagState){
            if (flagState) {
                /*Отключаем флаг у всех изображений кроме текущего*/
                linq(this.htmlForm.parent().find('.lot-image')).foreach(function(conte){
                    conte.lot_image.main(false);
                });
                /*У лота изменяем главное изображение*/
                this.htmlForm.parents('.lot.category-item:first').get(0).categoryItem.updateMainImg(this.params.FileName);
            }
            this.mainFlagImg.setState(flagState);
            this.update('IsMain', !!flagState);
            
        },
        visible: function(flagState){
            this.update('Visible', !!flagState);
        },
        IsMain: function(){
            return (typeof 'aaa' === typeof this.params.IsMain && this.params.IsMain === '1') || 
                (typeof true === typeof this.params.IsMain && this.params.IsMain);
        },
        IsVisible: function(){
            return (typeof 'aaa' === typeof this.params.Visible && this.params.Visible === '1') || 
                (typeof true === typeof this.params.Visible && this.params.Visible);
        }
    });
    
};


lot_image_upPreview = function(params){
    !('IsMain' in params) && (params.IsMain = false);
    var src = params.FileName.replace(/^[\s\.\\\/]+/g, '');
    var ext = src.split('.');
    ext = ext[ext.length - 1].toLowerCase();
    var rejected = false;
    if (!(ext in {jpg: true, jpeg: true, png: true, bmp: true, gif: true})) {
        rejected = true;
    }
    return $.extend(new base_new_class(), {
        params: params,
        rejected: rejected,//Метка, указывающая, что данный тип файла недопустим
        mainFlagImg: null,
        getHTMLForm: function(){
            var _self = this;
            var src = _self.params.FileName.replace(/^[\s\.\\\/]+/g, '');
            var imageConte = $('<div class="lot-image"></div>');
            if (!_self.rejected) {
                imageConte.append('<img class="lot-img" src="' + src + '" />');
            }
            else {
                imageConte.append('<img class="lot-img" src="pict/rejected_256.png" title="Недопустимый тип файла ' + _self.params.OrigName + '"/>');
            }
            
            this.htmlForm = imageConte;
            
            imageConte.get(0).lot_image = this;
            
            if (!_self.rejected) {
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
                $(this.mainFlagImg).click(function(){
                    _self.main(!_self.IsMain());
                });
            }
            var deleteImgIcon = document.createElement('IMG');
            deleteImgIcon.src = 'pict/trash_red_round_128.png';
            deleteImgIcon.title = _self.params.IdImage > 0 ? 'Удалить / восстановить файл' : 'Удалить файл';
            imageConte.append(deleteImgIcon);
            $(deleteImgIcon).addClass('delete-img-button').click(function(){
                if (_self.params.IdImage > 0) {
                    if (_self.params.__deleted) {
                        delete _self.params._deleted;
                        _self.htmlForm.removeClass('deleted');
                    }
                    else {
                        if (confirm('Вы уверены, что хотите удалить файл ' + _self.params.FileName.match(/[^\\\/]+$/)[0] + ' без возможности восстановления?')) {
                            _self.params._deleted = true;
                            _self.htmlForm.addClass('deleted');
                            /*Удаленное изображение не может быть главным*/
                            _self.params.IsMain = false;
                        }
                    }
                }
                else {
                    if (confirm('Вы уверены, что хотите удалить файл ' + _self.params.OrigName + ' ?')) {
                        _self.htmlForm.remove();
                    }
                }
            });
        
            if (_self.rejected) {
                var to = setTimeout(function(){
                    clearTimeout(to);
                    _self.htmlForm.remove();
                }, 10000);
            }
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
        IsMain: function(){
            return (typeof 'aaa' === typeof this.params.IsMain && this.params.IsMain === '1') || 
                (typeof true === typeof this.params.IsMain && this.params.IsMain);
        }
    });
    
};