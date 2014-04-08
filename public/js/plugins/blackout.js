/**
 * User: j3nya
 * Date: 31.08.13
 * Time: 18:36
 */
(function ($) {

    var blackout = {
        id: 'blackout',
        object: null,
        css: {
            property: {
                name: 'visibility',
                values: {
                    show: 'visible',
                    hide: 'hidden'
                }
            },
            set: function (property, value) {
                this.object.css(property, value);
            },
            get: function (property) {
                return this.object.css(property);
            }
        },
        init: function () {
            this.object = $('#' + this.id);
            if (this.object.length != 1) {
                this.object = null;
                this.error.log('No div#blackout');
                return false;
            }
            this.handler.click.apply(this);
            return true;
        },
        isInit: function () {
            return this.object != null
        },
        hide: function () {
            var property = this.css.property.name;
            var value = this.css.property.values.hide;
            this.css.set.apply(this, [property, value]);
        },
        show: function () {
            var property = this.css.property.name;
            var value = this.css.property.values.show;
            this.css.set.apply(this, [property, value]);
        },
        isHidden: function () {
            var property = this.css.property.name;
            var value = this.css.property.values.show;
            var current = this.css.get.apply(this, [property])
            return current != value;
        },
        handler: {
            event: {
                click: 'click'
            },
            click: function () {
                var me = this;
                var event = me.handler.event.click;
                this.object.live(event, function (event) {
                    event.preventDefault();
                    if (!me.isHidden()) {
                        me.hide();
                    }
                });
            }

        },
        error: {
            text: {
                texts: {
                    no_error_text: 'No error text',
                    not_initialized: 'Not_initialized object',
                    no_div: 'No div#' + this.id
                },
                get: function (alias) {
                    if (!this.texts.hasOwnProperty(alias)) {
                        return this.texts.no_error_text;
                    }
                    return  this.texts[alias];
                }
            },
            log: function (alias) {
                console.log(this.text.get(alias));
            }
        }
    }

    $.blackoutSetup = function () {
        if (!blackout.init()) {
            blackout.error.log('not_initialized');
            return false;
        }
        return true;
    }

    $.blackoutShow = function () {
        if (!blackout.isInit()) {
            blackout.error.log('not_initialized');
            return false;
        }
        blackout.show();
        return true;
    }

    $.blackoutHide = function () {
        if (!blackout.isInit()) {
            blackout.error.log('not_initialized');
            return false;
        }
        blackout.hide();
        return true;
    }

})(jQuery);