/**
 * User: j3nya
 * Date: 29.08.13
 * Time: 0:25
 */

(function ($) {

    $.tablesSetup = function () {
        $('table').customize();
    }

    $.fn.customize = function () {
        var id;
        $.each(this, function () {
            id = $(this).attr('id');
            if (customize.hasOwnProperty(id)) {
                customize[id].th.apply($(this).find('th'));
                customize[id].td.apply($(this).find('td'));
            }
            colorizeRows.apply($(this).find('tr:not(:has(th))'));
            if (id == 'services') {
                collapseTables.apply(this);
            }
        });
    }

    //align element in the middle of the screen
    $.fn.alignCenter = function () {
        //get margin left
        var marginLeft = Math.max(40, parseInt($(window).width() / 2 - $(this).outerWidth() / 2)) + 'px';
        //get margin top
        var marginTop = Math.max(40, parseInt($(window).height() / 2 - $(this).outerHeight() / 2)) + 'px';
        //return updated element
        return $(this).css({'margin-left': marginLeft, 'margin-top': marginTop});
    };

    var customize = {
        servers: {
            th: function () {
                var set = this;
                var header = set.parent('tr');
                header.after(header.clone());
                merge.apply(set, ['th', 'avg01', ['avg01', 'avg05', 'avg15']]);
                merge.apply(set, ['th', 'user', ['user', 'system', 'wait']]);
                rename.apply(set, ['avg01', 'load', 'load average']);
                rename.apply(set, ['user', 'cpu', 'cpu usage']);
                set = $(this.context).find('th');
                mergeV.apply(set, ['server']);
                mergeV.apply(set, ['count']);
                mergeV.apply(set, ['update']);
                mergeV.apply(set, ['collected']);
                mergeV.apply(set, ['systemmemorypercent']);
                mergeV.apply(set, ['systemmemorykilobyte']);
                mergeV.apply(set, ['systemswappercent']);
                mergeV.apply(set, ['systemswapkilobyte']);
                set = $(this.context).find('th');
                merge.apply(set, ['th', 'server', ['server', 'count']]);
                merge.apply(set, ['th', 'systemmemorypercent', ['systemmemorypercent', 'systemmemorykilobyte']]);
                merge.apply(set, ['th', 'systemswappercent', ['systemswappercent', 'systemswapkilobyte']]);
                rename.apply(set, ['systemmemorypercent', 'memory', 'memory']);
                rename.apply(set, ['systemswappercent', 'swap', 'swap']);
            },
            td: function () {
                format.apply(this, ['{text}%', 'avg01']);
                format.apply(this, ['{text}%', 'avg05']);
                format.apply(this, ['{text}%', 'avg15']);
                format.apply(this, ['{text}%', 'user']);
                format.apply(this, ['{text}%', 'system']);
                format.apply(this, ['{text}%', 'wait']);
                format.apply(this, ['[{text}%]', 'systemmemorypercent']);
                format.apply(this, ['{text} kB', 'systemmemorykilobyte']);
                format.apply(this, ['{text}%', 'systemswappercent']);
                format.apply(this, ['{text} kB', 'systemswapkilobyte']);
            }
        },
        services: {
            th: function () {
                if (isContent(this)) {
                    add.apply(this, ['th', 'host', '']);
                    add.apply(this, ['th', 'host', '']);
                    add.apply(this, ['th', 'host', '']);
                    add.apply(this, ['th', 'process', '']);
                    add.apply(this, ['th', 'directory', '']);
                    add.apply(this, ['th', 'filesystem', '']);
                    add.apply(this, ['th', 'filesystem', '']);
                    merge.apply(this, ['td', 'blockusage', ['blockusage', 'blockpercent']]);
                    merge.apply(this, ['td', 'inodeusage', ['inodeusage', 'inodepercent']]);
                    rename.apply(this, ['cpupercenttotal', 'cpu', 'cpu']);
                    rename.apply(this, ['memorypercenttotal', 'memory', 'memory']);
                    rename.apply(this, ['blockusage', 'block', 'block']);
                    rename.apply(this, ['inodeusage', 'inode', 'inode']);
                }
            },
            td: function () {
                if (isContent(this)) {
                    add.apply(this, ['td', 'host', '']);
                    add.apply(this, ['td', 'host', '']);
                    add.apply(this, ['td', 'host', '']);
                    add.apply(this, ['td', 'process', '']);
                    add.apply(this, ['td', 'filesystem', '']);
                    add.apply(this, ['td', 'directory', '']);
                    add.apply(this, ['td', 'filesystem', '']);
                    format.apply(this, ['{text} kB', 'size']);
//                    format.apply(this, ['{text} kB', 'blockusage']);
                    format.apply(this, ['{text}%', 'blockpercent']);
                    format.apply(this, ['{text} objects', 'inodeusage']);
                    format.apply(this, ['{text}%', 'inodepercent']);
                    format.apply(this, ['{text}%', 'cpupercenttotal']);
                    format.apply(this, ['{text}%', 'memorypercenttotal']);
                    formatTime.apply(this, ['{days}d {hours}h {minutes}m', 'uptime']);

                }
            }
        },
        parameters: {
            th: function () {
//                console.log(this);
            },
            td: function () {
                renameParams.apply(this, ['parameter', 'collected_sec', 'collected', 'data collected']);
                renameParams.apply(this, ['parameter', 'cpupercenttotal', 'cpu', 'cpu']);
                renameParams.apply(this, ['parameter', 'memorypercenttotal', 'memorypercent', 'memory, %']);
                renameParams.apply(this, ['parameter', 'memorykilobytetotal', 'memorykilobyte', 'memory, kB']);
                formatParams.apply(this, ['{text} %', 'value', 'blockpercent']);
                formatParams.apply(this, ['{text} Kb', 'value', 'blockusage']);
                formatParams.apply(this, ['{text} %', 'value', 'inodepercent']);
                formatParams.apply(this, ['{text} Kb', 'value', 'inodeusage']);
                formatParams.apply(this, ['{text} Kb', 'value', 'inodetotal']);
                formatParams.apply(this, ['{text} %', 'value', 'cpupercenttotal']);
                formatParams.apply(this, ['{text} %', 'value', 'memorypercenttotal']);
                formatParams.apply(this, ['{text} Kb', 'value', 'memorykilobytetotal']);
                formatTimeParams.apply(this, ['{days}d {hours}h {minutes}m', 'value', 'uptime']);
                formatTimeParams.apply(this, ['{days}d {hours}h {minutes}m', 'value', 'collected_sec']);
            }
        }
    }

    var mergeV = function (className) {
        var cells = filterByClass.apply(this, [className]);
        cells.replaceWith(cells.last().attr('rowspan', cells.length));
    }

    var merge = function (tagName, main, classes) {
        var cell = filterByClass.apply(this, [main]);
        cell.attr('colspan', classes.length);
        filterByClasses.apply(this, [classes]).replaceWith(cell);
    }

    var filterByClass = function (className) {
        return $(this).filter('.' + className);
    }

    var filterByClasses = function (classes) {
        return $(this).filter('.' + classes.join(', .'));
    }

    var createTag = function (params) {
        var tag = $('<' + params.name + '>');
        $.each(params.attrs, function (name, value) {
            tag.attr(name, value);
        });
        return (!params.content) ? tag : tag.html(params.content);
    }

    var blankTag = function (name) {
        return {name: name, attrs: {}};
    }

    var rename = function (className, newClassName, text) {
        filterByClass.apply(this, [className]).attr('class', newClassName).contents().text(text);
    }

    var renameParams = function (classColumn, className, newClassName, text) {
        findSpanByClass.apply(filterByClass.apply(this, [classColumn]), [className]).attr('class', newClassName).text(text);
    }

    var format = function (template, className) {
        var text;
        $.each(filterByClass.apply(this, [className]), function (i, td) {
            text = $(td).contents().text();
            text = (!text) ? '--' : template.replace('{text}', text);
            $(td).contents().text(text);
        });
    }

    var formatParams = function (template, classColumn, className) {
        var span = findSpanByClass.apply(filterByClass.apply(this, [classColumn]), [className]);
        var text = $(span).text();
        text = (!text) ? '--' : template.replace('{text}', text);
        $(span).text(text);
    }

    var move = function (className) {
        $.each(filterByClass.apply(this, [className]), function (i, cell) {
            $(cell).parent('tr').append($(cell).remove());
        });
    }

    var add = function (tagName, className, content) {
        var tr;
        var last;
        var span = blankTag('span');
        var tag = blankTag(tagName);
        span.content = content;
        content = !content ? 'empty' : content;
        span.attrs['class'] = content;
        tag.attrs['class'] = content;
        tag.content = createTag(span);
        $.each(filterByClass.apply(this, [className]), function (i, cell) {
            tr = $(cell).parent('tr');
            last = tr.find(tagName + ':last-child').remove();
            tr.append(createTag(tag)).append(last);
        });
    }

    var isContent = function (set) {
        return ($(set).parents('div').attr('id') == 'content');
    }

    var collapseTables = function () {
        var me = this;
        var tables = $.cookie('collapse-tables');
        if (tables) {
            $.each(tables.split('|'), function (i, table) {
                $(me).find('td.'+table).parent('tr').hide();
            });
        }
    }

    var colorizeRows = function () {
        var now = $.now() / 1000 | 0;
        var collected;
        var poll;
        var periods = $.getGlobal('periods');
        var className;
        $.each(this, function (i, tr) {
            if (poll = $(tr).attr('poll')) {
                poll = parseInt(poll);
                if (collected = $(tr).attr('collected')) {
                    className = null;
                    collected = parseInt(collected);
                    $.each(periods, function (key, value) {
                        if ((collected + poll * value) < now) {
                            className = key;
                        }
                    });
                    if (className) {
                        $(tr).addClass(className);
                    }
                }
                else {
                    console.log('no attribute "collected" in row');
                }
            }
            else {
                console.log('no attribute "poll" in row');
            }
        });
    }

    var formatTime = function (template, className) {
        var time;
        $.each(filterByClass.apply(this, [className]), function (i, td) {
            time = parseTime($(td).contents().text());
            $(td).contents().text(renderTime(template, time));
        });
    }

    var formatTimeParams = function (template, classColumn, className) {
        var span = findSpanByClass.apply(filterByClass.apply(this, [classColumn]), [className]);
        var time = parseTime(correctedTimeByClass($(span).text(), $(span).attr('class')));
        $(span).text(renderTime(template, time));
    }

    var correctedTimeByClass = function (time, className) {
        if (className == 'collected_sec') {
            time = time / 1000;
        }
        return time;
    }

    var parseTime = function (seconds) {
        var minutes = Math.floor(seconds / 60);
        var hours = Math.floor(minutes / 60);
        var days = Math.floor(hours / 24);
        hours = hours - (days * 24);
        minutes = minutes - (days * 24 * 60) - (hours * 60);
        seconds = seconds - (days * 24 * 60 * 60) - (hours * 60 * 60) - (minutes * 60);
        return {days: days, hours: hours, minutes: minutes, seconds: seconds};
    }

    var renderTime = function (template, time) {
        $.each(time, function (name, value) {
            var pattern = '{' + name + '}';
            if (template.indexOf(pattern) != -1) {
                template = template.replace(pattern, value);
            }
        });
        return template;
    }

    var findSpanByClass = function (className) {
        return $(this).find('span.' + className);
    }

})(jQuery);