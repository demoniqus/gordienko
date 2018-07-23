

calendar = function(params){
    var _self = {
        params: params,
        months: {
            1: 'Январь',
            2: 'Февраль',
            3: 'Март',
            4: 'Апрель',
            5: 'Май',
            6: 'Июнь',
            7: 'Июль',
            8: 'Август',
            9: 'Сентябрь',
            10: 'Октябрь',
            11: 'Ноябрь',
            12: 'Декабрь'
        },
        htmlForm: null,
        getHTMLForm: function(){
            /*Метод рисует форму для отображения аукциона на странице*/
            var _self = this;
            
            var conte = $('<div class="calendar"></div>');
            var sDay = $('<select class="day"></select>');
            var sMonth = $('<select class="month"></select>');
            var sYear = $('<select class="year"></select>');
            conte.append(sDay);
            conte.append(sMonth);
            conte.append(sYear);
            
            var d = new Date();
            var currentDate = this.params.date || {
                d: d.getDate(),
                m: d.getMonth() + 1,
                y: d.getFullYear()
            };
            var minYear = this.params.minYear || d.getFullYear() - 20;
            var maxYear = this.params.maxYear || d.getFullYear() + 50;
            
            sYear.append('<option value="0"></option>');
            for (var y = minYear; y <= maxYear; ++y) {
                sYear.append('<option value="' + y + '">' + y + '</option>');
            }
            sYear.val(currentDate.y);
            
            sMonth.append('<option value="0"></option>');
            for (var m = 1; m < 13; ++m) {
                sMonth.append('<option value="' + m + '">' + this.months[m] + '</option>');
            }
            sMonth.val(currentDate.m);

            this.fillDaysSelector(sDay, currentDate.y, currentDate.m)
            sDay.val(currentDate.d);
            
            var changeEvt = function(){
                if (this.lockChangeTrigger) {
                    return;
                }
                if ($(this).val() == '0') {
                    this !== sMonth.get(0) && (sMonth.get(0).lockChangeTrigger = true, sMonth.val(0), sMonth.get(0).lockChangeTrigger = false);
                    this !== sYear.get(0) && (sYear.get(0).lockChangeTrigger = true, sYear.val(0), sYear.get(0).lockChangeTrigger = false);
                    sDay.get(0).lockChangeTrigger = true;
                    sDay.val(0);
                    sDay.get(0).lockChangeTrigger = false;
                    var newDate = {
                        d: 0,
                        m: 0,
                        y: 0
                    };
                }
                else {
                    var newDate = {
                        d: parseInt(sDay.val()),
                        m: parseInt(sMonth.val()),
                        y: parseInt(sYear.val())
                    };
                    _self.fillDaysSelector(sDay, newDate.y, newDate.m);
                }
                typeof _self.params.change === typeof function(){} && _self.params.change(newDate);
            };
            $(sMonth).change(changeEvt);
            $(sYear).change(changeEvt);
            
            sDay.change(function(){
                if (this.lockChangeTrigger) {
                    return;
                }
                if ($(this).val() == '0') {
                    sMonth.get(0).lockChangeTrigger = true;
                    sMonth.val(0);
                    sMonth.get(0).lockChangeTrigger = false;
                    sYear.get(0).lockChangeTrigger = true;
                    sYear.val(0);
                    sYear.get(0).lockChangeTrigger = false;
                    var newDate = {
                        d: 0,
                        m: 0,
                        y: 0
                    };
                }
                else {
                    var newDate = {
                        d: parseInt(sDay.val()),
                        m: parseInt(sMonth.val()),
                        y: parseInt(sYear.val())
                    };
                }
                typeof _self.params.change === typeof function(){} && _self.params.change(newDate);
            });
            
            this.htmlForm = conte;
            
            return conte;
        },
        fillDaysSelector: function(select, y, m) {
            var val = select.val();
            select.empty();
            select.append('<option value="0"></option>');
            var maxDay = m == 2 ? (y % 4 === 0 ? 29 : 28) : (m == 4 || m == 6 || m == 9 || m == 11 ? 30 : 31);
            for (var d = 1; d <= maxDay; ++d) {
                select.append('<option value="' + d + '">' + d + '</option>');
            }
            typeof val === typeof 'aaa' && parseInt(val) <= maxDay && select.val(val);
        }
    };
    return _self;
};
