/**
 * User: j3nya
 * Date: 8/9/13
 * Time: 12:41 PM
 */

(function ($) {

    var collapse = {
        table: {
            object: null,
            set: function () {
                this.state.set(this.cookie.get());
                this.rows.set(this.rowCount());
                this.separator.set(this);
                this.width.set(this.outerWidth());
            },
            setBySeparator: function (td) {
                this.object = $(td).parents(this.html.tag);
                if (this.object.length != 1) {
                    this.object = null;
                    console.log('No set table');
                    return false;
                }
                this.set();
                return true;
            },
            setByAside: function () {
                var id = this.aside.id;
                this.object = this.findTable(id);
                if (this.object.length != 1) {
                    this.object = null;
                    return false;
                }
                var cookie = (this.cookie.get() != 'use') ? 'use' : 'hidden';
                this.cookie.set(cookie);
                this.set();
                return true;
            },
            findTable: function (id) {
                return $('#' + id).find(this.html.tag);
            },
            add: function (end) {
                var content = this.separator.get();
                var row = this.rows.getFirst(this);
                (!end) ? row.prepend(content) : row.append(content);
            },
            move: function () {
                this.css.set();
            },
            rowCount: function () {
                return this.object.find(this.rows.tag).length;
            },
            outerWidth: function () {
                return this.object.outerWidth();
            },
            show: function () {
                this.add(false);
                this.offset.set();
                this.move();
                this.cookie.set('use');
            },
            hide: function () {
                this.add(true);
                this.offset.set(this.width.get());
                this.move();
                this.cookie.set('hidden');
            },
            shift: function () {
                (this.state.get() != 1) ? this.show() : this.hide();
            },
            html: {
                tag: 'table'
            },
            css: {
                property: 'margin-left',
                unit: 'px',
                set: function () {
                    var table = collapse.table;
                    var property = this.property;
                    var value = table.offset.get() + this.unit;
                    table.object.css(property, value);
                    var marginLeft = table.offset.get() > 0 ?
                                     table.offset.get() + $('#aside').outerWidth() :
                                     $('#aside').outerWidth();
                    $('#main').css('margin-left', marginLeft);
                }
            },
            width: {
                value: 0,
                set: function (width) {
                    this.value = width;
                },
                get: function () {
                    return this.value;
                }
            },
            state: {
                value: null,
                set: function (value) {
                    this.value = value;
                },
                get: function () {
                    return (this.value != 'use') ? 0 : 1;
                }
            },
            aside: {
                id: 'aside'
            },
            rows: {
                count: null,
                tag: 'tr',
                filter: {
                    first: 'first'
                },
                set: function (count) {
                    if (!count) {
                        console.log('No set row count: ' + count);
                        return false;
                    }
                    this.count = count;
                    return true;
                },
                getFirst: function (table) {
                    return table.object.find(this.tag + ':' + this.filter.first);
                }
            },
            separator: {
                object: null,
                html: {
                    tag: 'td',
                    class: 'separator'
                },
                css: {
                    property: 'rowspan'
                },
                selector: function () {
                    return this.html.tag + '.' + this.html.class;
                },
                cut: function (sp) {
                    this.object = $(sp).remove();
                    if (this.object.length != 1) {
                        this.object = null;
                        return false;
                    }
                    return true;
                },
                set: function (table) {
                    var sp = table.object.find(this.selector());
                    if (sp.length != 1) {
                        var rowspan = table.rowCount();
                        this.create(rowspan);
                    }
                    else {
                        this.cut(sp);
                    }
                    var state = table.state.get();
                    this.text.set(this, state);
                },
                get: function () {
                    return this.object;
                },
                create: function (rowspan) {
                    var angleBracket = ['<', '>'];
                    this.object = $(angleBracket.join(this.html.tag));
                    this.object.attr(this.css.property, rowspan);
                    this.object.addClass(this.html.class);
                },
                text: {
                    texts: ['<', '>'],
                    set: function (separator, state) {
                        separator.object.html(this.texts[state]);
                    }
                }
            },
            offset: {
                value: null,
                margin: 0,
                border: 1,
                set: function (width) {
                    this.value = (width != undefined) ?
                                 this.margin - (width + this.border) :
                                 this.margin;
                },
                get: function () {
                    return this.value;
                }
            },
            cookie: {
                name: 'aside',
                options: {path: '/'},
                set: function (value) {
                    if (value != undefined) {
                        $.cookie(this.name, value, this.options);
                    }
                    else {
                        $.cookie(this.name, '', this.options);
                    }
                },
                get: function () {
                    return $.cookie(this.name);
                }
            }
        },
        handle: {
            event: {
                click: 'click'
            },
            initialized: false,
            init: function () {
                var selector = this.table.separator.selector();
                var event = this.handle.event.click;
                $(selector).live(event, function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    var table = collapse.table;
                    table.setBySeparator(this);
                    table.shift();
                });
                this.handle.initialized = true;
            },
            isInitialized: function () {
                return this.initialized;
            }

        }
    }

    $.collapseSetup = function () {
        var table = collapse.table;
        if (table.setByAside.apply(table)) {
            table.shift();
            if (!collapse.handle.isInitialized()) {
                collapse.handle.init.apply(collapse);
            }
        }
    }

})(jQuery);