/**
 * User: j3nya
 * Date: 8/7/13
 * Time: 10:42 AM
 */
(function ($) {

    var request = {
        type: 'json',
        data: {},
        execute: function (url) {
            $.post(url, this.data, response.display, this.type);
        },
        query: function (url) {
            $.post(url);
        },
        update: function (url) {
            var me = this;
            $.post(url, me.data, response.update, me.type);
        },
        action: function (url) {
            var me = this;
            $.post(url, me.data, response.action, me.type);
        },
        send: function (url, callback) {
            var me = this;
            callback = response[callback];
            $.post(url, me.data, callback, me.type);
        }
    }

    var response = {
        update: function (data) {
            if (!$.isEmptyObject(data.services)) {
                var collected;
                $.each(data.services, function (id, service) {
                    if (collected = $('#' + service['name']).parents('tr').attr('collected')) {
                        if (service['collected'] > collected) {
                            $.loadContent(location.href);
                            return false;
                        }
                    }
                });
                $('#servers #update-' + data['server']).tooltip('data is not updated on the server', {hide: 2000});
            }
            else if (!$.isEmptyObject(data.service)) {
                if (collected = $('#services #' + data.service['name']).parents('tr').attr('collected')) {
                    if (data.service['collected'] > collected) {
                        $.loadContent(location.href);
                        setTimeout(function () {
                            if ($('#services #' + data.service['name']).parents('tr').attr('status') == 'initialization') {
                                $.updateRequest(
                                    [location.protocol + '//' + location.hostname, 'update', data['server']].join('/'),
                                    {
                                        type: $('#' + data.service['name']).parents('td').attr('class'),
                                        service: data.service['name'],
                                        action: data['action']
                                    }
                                );
                            }
                        }, 3000);
                    }
                    else {
                        setTimeout(function () {
                            $.updateRequest(
                                [location.protocol + '//' + location.hostname, 'update', data['server']].join('/'),
                                {
                                    type: $('#' + data.service['name']).parent('span').parent('td').attr('class'),
                                    service: data.service['name'],
                                    action: data['action']
                                }
                            );
                        }, 5000);
                    }
                    return false;
                }
            }
        },
        action: function (data) {
            if (!$.isEmptyObject(data.message)) {
                var link = $('#' + data['service']).parents('tr').find('td.actions span a.' + data['action']);
                link.removeClass('action-wait');
                link.tooltip(data['message'], {hide: 2000});
                $.updateRequest(
                    [location.protocol + '//' + location.hostname, 'update', data['server']].join('/'),
                    {
                        type: $('#' + data['service']).parent('span').parent('td').attr('class'),
                        service: data['service'],
                        action: data['action']
                    }
                );
            }
            else {
                $.eventRequest(
                    [location.protocol + '//' + location.hostname, 'event', data['server']].join('/'),
                    {
                        service: data['service'],
                        action: data['action']
                    }
                );
            }
        },
        event: function (data) {
            if ($.cookie('wait') === null) {
                $.cookie('wait', 5);
            }
            if (!$.isEmptyObject(data.message)) {
                var link = $('#' + data['service']).parents('tr').find('td.actions span a.' + data['action']);
                link.removeClass('action-wait');
                link.tooltip(data['message'], {hide: 2000});
                $.cookie('wait', null);
                $.updateRequest(
                    [location.protocol + '//' + location.hostname, 'update', data['server']].join('/'),
                    {
                        type: $('#' + data['service']).parent('span').parent('td').attr('class'),
                        service: data['service'],
                        action: data['action']
                    }
                );
            }
            else if ($.cookie('wait') > 0) {
                $.cookie('wait', $.cookie('wait') - 1);
                setTimeout(function () {
                    $.eventRequest(
                        [location.protocol + '//' + location.hostname, 'event', data['server']].join('/'),
                        {
                            service: data['service'],
                            action: data['action']
                        }
                    );
                }, 1000);
            }
            else {
                $('#' + data['service']).parents('tr').find('td.actions span.actions a.' + data['action']).tooltip('no message yet received', {hide: 2000});
                $.cookie('wait', null);
                $.updateRequest(
                    [location.protocol + '//' + location.hostname, 'update', data['server']].join('/'),
                    {
                        servers: $('#servers').parents('div').attr('id'),
                        services: $('#services').parents('div').attr('id')
                    }
                );
            }
        },
        display: function (data) {
            $.each(data, function (id, html) {
                if (id && html) {
                    $('#' + id).html(html);
                }
            });
            $.tablesSetup();
            $.collapseSetup();
            $.blackoutHide();
        }
    }

    var methods = {
        load: function (url) {
            request.execute(url);
        },
        send: function (url) {
            request.query(url);
        },
        update: function (url, data) {
            if (data) request.data = data;
            request.send(url, 'update');
        },
        action: function (url) {
            request.send(url, 'action');
        },
        event: function (url, data) {
            if (data) request.data = data;
            request.send(url, 'event');
        }
    }

    $.loadContent = function (url) {
        return methods.load.apply(this, arguments);
    }

    $.sendRequest = function (url) {
        return methods.send.apply(this, arguments);
    }

    $.actionRequest = function (url) {
        return methods.action.apply(this, arguments);
    }

    $.updateRequest = function () {
        return methods.update.apply(this, arguments);
    }

    $.eventRequest = function () {
        return methods.event.apply(this, arguments);
    }

})(jQuery);