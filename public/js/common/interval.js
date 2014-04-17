/**
 * User: j3nya
 * Date: 8/7/13
 * Time: 11:10 AM
 */
(function ($) {

    var globals = {
        id: 'intervalID',
        interval: 'interval',
        lastModified: 'lastModified'
    }

    var interval = {
        default: 60000,
        set: function (url) {
            return setInterval(
                function () {
                    $.loadContent(url);
                },
                this.time());
        },
        time: function () {
            var time = $.getGlobal(globals.interval);
            return (!time) ? this.default : time;
        }
    }

    var timeout = {
        set: function (url) {
            return setTimeout(
                function () {
                    $.loadContent(url);
                    $.setInterval(url);
                },
                this.time()
            );
        },
        time: function () {
            var time = $.getGlobal(globals.interval);
            if (!time) {
                time = interval.default;
            }
            time = time - ($.now() - ($.getGlobal(globals.lastModified) + 1000));
            return (time < 0) ? 0 : time;
        }
    }

    var set = {
        timeout: function (url) {
            var id = $.getGlobal(globals.id);
            if (id) {
                clearInterval(id);
            }
            $.setGlobal(globals.id, timeout.set(url));
        },
        interval: function (url) {
            clearInterval($.getGlobal(globals.id));
            $.setGlobal(globals.id, interval.set(url));
        }
    }

    $.setTimeout = function (url) {
        return set.timeout.apply(null, arguments);
    }

    $.setInterval = function (url) {
        return set.interval.apply(null, arguments);
    }

})(jQuery);