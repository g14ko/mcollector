/**
 * User: j3nya
 * Date: 12.08.13
 * Time: 0:25
 */
(function ($) {

    $.fn.tooltip = function (message, params) {
        var instance = tooltip.create(message);
        $(this).before(instance);
        instance.css('margin-left', (20 - instance.width() + 'px'));
        if (!$.isEmptyObject(params) && params.hide) {
            setTimeout(function () {
                instance.hide('slow');
            }, params.hide);
        }

    }

    var tooltip = {
        instance: null,
        tag: 'span',
        class: 'bubble',
        create: function (message) {
            var me = this;
            me.instance = $('<' + me.tag + '>').addClass(me.class).text(message);
            return me.instance;
        },
        get: function (message) {
            var me = this;
            return !me.instance ? me.create(message) : me.instance;
        }
    }

})(jQuery);