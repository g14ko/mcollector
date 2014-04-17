/**
 * User: j3nya
 * Date: 8/5/13
 * Time: 2:47 PM
 */
(function ($) {

    var globals = new Array();

    var methods = {
        load: function (vars) {
            $.each(vars, function (name, value) {
                globals[name] = value;
            });
        },
        get: function (name) {
            if (globals[name] !== undefined) {
                return globals[name]
            }
            return false;
        },
        set: function (name, value) {
            globals[name] = value;
        }
    }

    $.loadGlobals = function (vars) {
        return methods.load.apply(this, arguments);
    },
        $.getGlobal = function (name) {
            return methods.get.apply(this, arguments);
        },
        $.setGlobal = function (name, value) {
            return methods.set.apply(this, arguments);
        }

})(jQuery);