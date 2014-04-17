/**
 * User: j3nya
 * Date: 8/5/13
 * Time: 2:46 PM
 */
(function ($) {

    var update = {
        page: function (url) {
            var isBaseUrl = function () {
                var baseUrl = location.protocol + '//' + location.hostname;
                return (baseUrl == url.replace(/\/$/, ''));
            }
            !isBaseUrl() ? $.loadContent(url) : setTimeout('location.reload()', 100);
            $.setTimeout(url);
        },
        history: function (url) {
            history.pushState({url: url}, null, url);
        }
    }

    var setup = {
        init: function (url) {
            $.blackoutSetup();
            $.tablesSetup();
            $.collapseSetup();
            $.setInterval(url);
            this.handler.init();
        },
        handler: {
            id: {
                navigate: 'navigate',
                aSide: 'aside',
                content: 'content'
            },
            init: function () {
                this.links();
            },
            links: function () {
                $.each(this.id, function (name, id) {
                    var container = $('#' + id);
                    container.find('a:not(.action):not(.update)').onClick();
                    container.find('a.update').onUpdate();
                    container.find('a.action').onAction();
                    container.find('img.warning').onHover();
                    container.find('#services tr.header').onHeaderClick();
                });
            }
        }
    }

    var methods = {
        onHeaderClick: function () {
            $(this).live('click', function (event) {
                event.preventDefault();
                var table = $(this).find('th:first:not(separator) span:first-child').text();
                var rows = $(this).parents('table').find('td.' + table).parent('tr');
                var cookie = $.cookie('collapse-tables');
                if (!rows.is(':visible')) {
                    rows.show();
                    if (cookie) {
                        var pos = cookie.indexOf(table);
                        if (pos != -1) {
                            cookie = pos != 0 ?
                                     cookie.substr(0, --pos) + cookie.substr(++pos + table.length) :
                                     cookie.substr(++table.length);
                        }
                    }
                }
                else {
                    rows.hide();
                    cookie = !cookie ? table : cookie + '|' + table;
                }
                $.cookie('collapse-tables', cookie, {'path': '/'});
            });
        },
        onHover: function () {
            $(this).live('hover',function (event) {
                var box = $(this).parent('span');
                var tooltip = box.find('span.bubble');
                if (tooltip.length == 0) {
                    var message = box.find('span.warning')
                    $(this).tooltip(message.text());
                    message.remove();
                }
                else {
                    if (!tooltip.is(':visible')) {
                        $(this).tooltip(tooltip.text());
                        tooltip.remove();
                    }
                }
            }).live('mouseout', function (event) {
                    var box = $(this).parent('span');
                    var tooltip = box.find('span.bubble');
                    setTimeout(function () {
                        tooltip.hide('slow');
                    }, 2000);
                });
        },
        onClick: function () {
            $(this).live('click', function (event) {
                event.preventDefault();
                $.blackoutShow();
                var url = this.href;
                update.history(url);
                update.page(url);
            });
        },
        onUpdate: function () {
            $(this).live('click', function (event) {
                event.preventDefault();
                var url = this.href;
                if ($(this).hasClass('active')) {
                    $.updateRequest(url, {
                        servers: $('#servers').parents('div').attr('id'),
                        services: $('#services').parents('div').attr('id')
                    });
                }
            });
        },
        onAction: function () {
            $(this).live('click', function (event) {
                event.preventDefault();
                var url = this.href;
                if ($(this).hasClass('active')) {
                    var me = this;
                    $.blackoutShow();
                    $.actionRequest(url);
                    setTimeout(function () {
                        // todo: move this somewhere
                        var action = url.split('/').pop();
                        $(me).parents('tr').find('td.status span.status').after(' - ' + action + ' pending');
                        $(me).parent('span').find('a').removeClass('active').addClass('inactive').css('opacity', '.3');
                        $(me).addClass('action-wait').css('opacity', '.7');
                        $(me).find('img').css('opacity', '.7');

                        $.blackoutHide();
                    }, 500);
                }
            });
        },
        setup: function () {
            var url = location.href;
            setup.init(url);
            update.history(url);
            addEventListener('popstate', function (e) {
                if (e.state && e.state.url) {
                    update.page(e.state.url);
                }
            }, false);
        }
    }

    $.fn.onClick = function (containerID) {
        return methods.onClick.apply(this, arguments);
    }

    $.fn.onAction = function (containerID) {
        return methods.onAction.apply(this, arguments);
    }

    $.fn.onUpdate = function (containerID) {
        return methods.onUpdate.apply(this, arguments);
    }

    $.fn.onHover = function (containerID) {
        return methods.onHover.apply(this, arguments);
    }

    $.fn.onHeaderClick = function (containerID) {
        return methods.onHeaderClick.apply(this, arguments);
    }

    $.setUp = function () {
        return methods.setup.apply(this);
    }

})(jQuery);