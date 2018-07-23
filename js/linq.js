

/*
 * Набор методов для работы с коллекциями: массивами, объектами, коллекциями jQuery,
 * различными NodesList, HTMLCollection и т.п., имеющими схожее с массивами поведение, но не являющиеся таковыми
 * Все методы кроме orderBy приводят к созданию новой коллекции, при этом исходная коллекция не изменяется.
 * Метод orderBy изменяет исходную коллекцию.
 * Метод reduce возвращает "сжатое" значение. Все остальные методы возвращают ссылку на 
 * текущий экземпляр linq для обработки коллекции по цепочке вызовов.
 * @param {type} collection - исходная коллекция
 * @returns {linq._self}
 * 
 */
var linq = function (collection) {
    collection = collection === null || typeof collection === typeof void null ? [] : collection;
    if (typeof collection !== typeof {} && !(collection instanceof Array)) {
        throw new Exception('LINQ может работать только с коллекциями!');
    }
    var _self = {
        collection: null,
        mode: null,
        /*
         * Везде, где потенциально требуется возврат всей имеющейся коллекции, возвращаем ссылку на текущий экземпляр linq, 
         * а доступ к измененной коллекции осуществляем напрямую через свойство collection
         */
        first: function (f) { // f(элемент, ключ)
            var el = null;
            if (this.mode === 'a') {
                for (var i = 0; i < this.collection.length; ++i) {
                    if (f(this.collection[i], i) === true) {
                        el = this.collection[i];
                        break;
                    }
                }
            }
            else {
                for (var key in this.collection) {
                    if (f(this.collection[key], key) === true) {
                        el = this.collection[key];
                        break;
                    }
                }
            }
            return el;
        },
        firstKey: function (f) { //f(ключ)
            var el = null;
            if (this.mode === 'a') {
                for (var i = 0; i < this.collection.length; ++i) {
                    if (f(this.collection[i]) === true) {
                        el = i;
                        break;
                    }
                }
            }
            else {
                for (var key in this.collection) {
                    if (f(this.collection[key]) === true) {
                        el = key;
                        break;
                    }
                }
            }
            return el;
        },
        reduce: function(f, startVal){//f(очередной "сжатый" результат, элемент коллекции, ключ, коллекция)
            var res = typeof void null === typeof startVal ? null: startVal;
            if (this.mode !== 'a') {
                for (var key in this.collection) {
                    res = f(res, this.collection[key], key, this.collection);
                }
            }
            else {
                for (var i = 0; i < this.collection.length; ++i) {
                    res = f(res, this.collection[i], i, this.collection);
                }
            }
            /*Результат может иметь любой тип значения, в т.ч. и не быть какой-либо коллекцией*/
            return res;
        },
        where: function (f) {//f(элемент, ключ)
            var conte;
            if (this.mode === 'a') {
                conte = [];
                for (var i = 0; i < this.collection.length; ++i) {
                    if (f(this.collection[i], i) === true) {
                        conte.push(this.collection[i]);
                    }
                }
            }
            else {
                conte = {};
                for (var key in this.collection) {
                    if (f(this.collection[key], key) === true) {
                        conte[key] = this.collection[key];
                    }
                }
            }
            this.collection = conte;
            return this;
        },
        valuesToArray: function () {
            if (this.mode !== 'a') {
                var c = [];
                for (var key in this.collection) {
                    c.push(this.collection[key]);
                }
                this.collection = c;
            }
            this.mode = 'a';
            return this;
        },
        keysToArray: function () {
            var c = [];
            if (this.mode !== 'a') {
                for (var key in this.collection) {
                    c.push(key);
                }
            }
            else {
                /*Из-за infragistic для массива применим особый подход*/
                for (var key in this.collection) {
                    if (typeof 111 === typeof key) {
                        c.push(key);
                    }
                }
            }
            this.collection = c;
            this.mode = 'a';
            return this;
        },
        max: function (f) {//f(элемент)
            var el = 0;
            var res = undefined;
            if (this.mode === 'a') {
                for (var i = 0; i < this.collection.length; ++i) {
                    typeof 111 === typeof (res = f(this.collection[i])) && res > el && (el = res);
                }
            }
            else {
                for (var key in this.collection) {
                    typeof 111 === typeof (res = f(this.collection[key])) && res > el && (el = res);
                }
            }
            return res === undefined ? null : el;
        },
        min: function (f) {//f(элемент)
            var el = 0;
            var res = undefined;
            if (this.mode === 'a') {
                for (var i = 0; i < this.collection.length; ++i) {
                    typeof 111 === typeof (res = f(this.collection[i])) && res < el && (el = res);
                }
            }
            else {
                for (var key in this.collection) {
                    typeof 111 === typeof (res = f(this.collection[key])) && res < el && (el = res);
                }
            }
            return res === undefined ? null : el;
        },
        orderBy: function (f) {//f(элемент1, элемент2). Э1 < Э2 => -1, Э1 > Э2 => 1, Э1 === Э2 => 0.
            if (this.mode === 'a') {
                this.collection.sort(f);
            }
            return this;
        },
        groupBy: function (f) {//f(элемент)
            var groupKey = null;
            var o = {};
            var a = [];
            var func = function (el) {
                groupKey = f(el);
                if (groupKey === null || typeof void null === typeof groupKey) {
                    return;
                }
                !(groupKey in o) && (o[groupKey] = [], a.push(groupKey), o[groupKey].groupKey = groupKey);
                o[groupKey].push(el);
            };
            if (this.mode === 'a') {
                for (var i = 0; i < this.collection.length; ++i) {
                    func(this.collection[i]);
                }
            }
            else {
                for (var key in this.collection) {
                    func(this.collection[key]);
                }
            }
            a.sort();
            var r = [];
            for (var i = 0; i < a.length; ++i) {
                r.push(o[a[i]]);
            }
            this.collection = r;
            this.mode = 'a';
            return this;
        },
        select: function (f) {//f(элемент)
            if (this.mode === 'a') {
                var a = [];
                for (var i = 0; i < this.collection.length; ++i) {
                    a.push(f(this.collection[i]));
                }
                this.collection = a;
            }
            else {
                var o = {};
                for (var key in this.collection) {
                    o[key] = f(this.collection[key]);
                }
                this.collection = o;
            }
            return this;
        },
        foreach: function (f) {//f(элемент, ключ)
            if (this.mode === 'a') {
                for (var i = 0; i < this.collection.length; ++i) {
                    f(this.collection[i], i);
                }
            }
            else {
                for (var key in this.collection) {
                    f(this.collection[key], key);
                }
            }
            return this;
        },
        toDict: function (f) {//f(элемент)
            var o = {};
            var func = function (el) {
                var dictKey = f(el);
                dictKey !== null && typeof dictKey !== typeof void null && (o[dictKey] = el);
            };
            if (this.mode === 'a') {
                for (var i = 0; i < this.collection.length; ++i) {
                    func(this.collection[i]);
                }
            }
            else {
                for (var key in this.collection) {
                    func(this.collection[key]);
                }
            }
            this.collection = o;
            this.mode = 'o';
            return this;
        }
    };
    /*
     * Пока единственная функция, которая приводит к изменению исходной коллекции - orderBy.
     * Результатом всех остальных функций становится либо простое значение, либо новая коллекция,
     * что исключает внесение нежелательных изменений в исходную коллекцию.
     */
    if (
            (window.jQuery && collection instanceof window.jQuery) ||
            collection instanceof Array ||
            'length' in collection//Различные NodesList, HTMLCollection и т.п., имеющие схожее с массивами поведение, но не являющиеся таковыми
        ) {
        _self.mode = 'a';
    }
    else {
        _self.mode = 'o';
    }
    _self.collection = collection;

    return _self;
};