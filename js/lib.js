

lib = {
    lotSyncDataUrl: 'index.php?mode=data&datakey=lots_sync',
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
    },
    MySQLDateToDate: function(strDate){
        var d = strDate.split(/[^\d]+/);
        return new Date(d[0], d[1] - 1, d[2], d[3], d[4], d[5]);
    }
};