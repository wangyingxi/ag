(function($) {
    var DURATION = 100;

    var photoSelectorMethods = {
        init: function(options) {
            var $this = $(this);
            $this.prop('disabled', true);
            if (!options)
                options = {};

            // 初始化Setting默认值
            var settings = {
                multi: true,
                url: '/manage/photo/list'
            };

            // 生成modal框
            var generateModalId = function() {
                var modalId = 'gy' + new Date().getTime();
                // 避免重复id
                if ($('#' + modalId).length > 0)
                    modalId += new Date().getTime();

                var modalHtml = '<div class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">';
                modalHtml += '<div class="modal-dialog">';
                modalHtml += '<div class="modal-content">';
                modalHtml += '<div class="modal-header">';
                modalHtml += '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>';
                modalHtml += '<h4 class="modal-title">选择图片</h4>';
                modalHtml += '</div>';
                modalHtml += '<div class="modal-body"></div>';
                modalHtml += '<div class="modal-page"></div>';
                modalHtml += '<div class="modal-footer">';
                modalHtml += '<button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>';
                modalHtml += '<button type="button" class="btn btn-primary select-btn">选择</button>';
                modalHtml += '</div>';
                modalHtml += '</div>';
                modalHtml += '</div>';
                modalHtml += '</div>';

                var modalPopup = $(modalHtml).attr('id', modalId);
                $('body').append(modalPopup);

                return modalId;
            };

            // 合并settings
            settings.modalId = generateModalId();
            $.extend(settings, options);
            $this.data('settings', settings);

            // “选择图片”按钮
            var launchBtn = $("<input>")
                    .attr('type', 'button')
                    .attr('data-toggle', 'modal')
                    .attr('data-target', '#' + settings.modalId)
                    .addClass('btn btn-success btn-sm')
                    .val('选择图片');
            launchBtn.click(function() {
                $this.photoSelector('start');
            });

            $this.append(launchBtn);
            $this.prop('disabled', false);
        },
        renderPhoto: function(resource) {
            var $this = $(this), settings = $this.data('settings'), modalId = settings.modalId, $modal = $('#' + modalId);
            var modalBody = $modal.find('.modal-body');
            modalBody.empty();
            console.log(resource.length);
            if (resource) {
                $.each(resource, function(i, j) {
                    var item = $(j);
                    console.log(item[0].id);
//                    alert(item.id);
                    // 调整selected属性
                    $modal.attr('selected');
                });
            }

        },
        renderPagebar: function(param) {
            var $this = $(this), settings = $this.data('settings'), modalId = settings.modalId, $modal = $('#' + modalId);

            // 显示pagebar
            var pagebar = $modal.find('.modal-page');
            pagebar.empty();
            for (var i = 1; i <= param.count; i++) {
                var ACTIVECLS = 'active';
                var pageBtn = $("<input>").attr('type', 'button').attr('page', i).addClass('btn btn-default btn-xs').val(i);
                if (param.page === i) {
                    pageBtn.addClass(ACTIVECLS);
                }
                pageBtn.click(function() {
                    var selfBtn = $(this);
                    if (!selfBtn.hasClass(ACTIVECLS)) {
                        // 调整class
                        selfBtn.siblings('.btn-default').removeClass(ACTIVECLS);
                        selfBtn.addClass(ACTIVECLS);
                        // 刷新图片
                        var pgn = selfBtn.attr('page');
                        $this.photoSelector('request', pgn);
                    }
                });
                pagebar.append(pageBtn);
            }
        },
        renderSelected: function(param) {
//            alert(resource.page);
        },
        request: function(page) {
            var $this = $(this), settings = $this.data('settings'), modalId = settings.modalId, $modal = $('#' + modalId);

            var url = settings.url;
            var data = {format: 'json', 'page': page};
            $.ajax({
                url: url,
                dataType: 'json',
                data: data,
                success: function(response) {
                    if (response.code === 200) {
//                        var pageNo = response.page;
                        var count = response.count;
                        var resource = response.data;
                        // 显示图片
                        $this.photoSelector('renderPhoto', resource);

                        if (!$modal.attr('loaded')) {
                            // 显示pagebar
                            $this.photoSelector('renderPagebar', {page: page, count: count});
                            $modal.attr('loaded', true);
                        }
                    }
                }
            });
        },
        start: function() {
            var $this = $(this), settings = $this.data('settings'), modalId = settings.modalId, $modal = $('#' + modalId);
            $this.photoSelector('request', 1);
//            var $this = $(this);
//            var settings = $this.data('settings');
//            var modalId = settings.modalId;
//            var $modal = $('#' + modalId);
//            if (!$modal.attr('loaded')) {
//                var page = 1;
//                var $this = $(this);
//                var settings = $this.data('settings');
//                var url = settings.url;
//                var data = {format: 'json', 'page': page};
//                $.ajax({
//                    url: url,
//                    dataType: 'json',
//                    data: data,
//                    success: function(response) {
//                        if (response.code === 200) {
//
//                            var pageNo = response.page;
//                            var count = response.count;
//                            var resource = response.data;
//                            // 显示图片
//                            $this.photoSelector('refresh', {page: 1, data: resource});
//                            // 显示pagebar
//                            for (var i = 1; i <= count; i++) {
//                                var ACTIVECLS = 'active';
//                                var pageBtn = $("<input>").attr('type', 'button').attr('page', i).addClass('btn btn-default btn-xs').val(i);
//                                if (pageNo === i) {
//                                    pageBtn.addClass(ACTIVECLS);
//                                }
//                                pageBtn.click(function() {
//                                    var selfBtn = $(this);
//                                    if (!selfBtn.hasClass(ACTIVECLS)) {
//                                        // 调整class
//                                        selfBtn.siblings('.btn-default').removeClass(ACTIVECLS);
//                                        selfBtn.addClass(ACTIVECLS);
//                                        // 刷新图片
//                                        var pgn = selfBtn.attr('page');
//                                        $this.photoSelector('refresh', {page: pgn});
//                                    }
//                                });
//                                $modal.find('.modal-page').append(pageBtn);
//                            }
//                        }
//
//                    }
//                });
//            }
        }
    }

    $.fn.photoSelector = function(method) {

        if (photoSelectorMethods[method]) {
            return photoSelectorMethods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return photoSelectorMethods.init.apply(this, arguments);
        } else {
            $.error('The method ' + method + ' does not exist in $.uploadify');
        }

    }

    $.queryString = {
        data: {},
        initial: function() {
            var aPairs, aTmp;
            var queryString = new String(window.location.search);
            queryString = queryString.substr(1, queryString.length); //remove   "?"     
            aPairs = queryString.split("&");
            for (var i = 0; i < aPairs.length; i++) {
                aTmp = aPairs[i].split("=");
                this.data[aTmp[0]] = aTmp[1];
            }
        },
        getValue: function(key) {
            return this.data[key];
        }
    };
})(jQuery);

/*!
 * jQuery Cookie Plugin v1.3.1
 * https://github.com/carhartl/jquery-cookie
 *
 * Copyright 2013 Klaus Hartl
 * Released under the MIT license
 */
(function(factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as anonymous module.
        define(['jquery'], factory);
    } else {
        // Browser globals.
        factory(jQuery);
    }
}(function($) {

    var pluses = /\+/g;

    function raw(s) {
        return s;
    }

    function decoded(s) {
        return decodeURIComponent(s.replace(pluses, ' '));
    }

    function converted(s) {
        if (s.indexOf('"') === 0) {
            // This is a quoted cookie as according to RFC2068, unescape
            s = s.slice(1, -1).replace(/\\"/g, '"').replace(/\\\\/g, '\\');
        }
        try {
            return config.json ? JSON.parse(s) : s;
        } catch (er) {
        }
    }

    var config = $.cookie = function(key, value, options) {

        // write
        if (value !== undefined) {
            options = $.extend({}, config.defaults, options);

            if (typeof options.expires === 'number') {
                var days = options.expires, t = options.expires = new Date();
                t.setDate(t.getDate() + days);
            }

            value = config.json ? JSON.stringify(value) : String(value);

            return (document.cookie = [
                config.raw ? key : encodeURIComponent(key),
                '=',
                config.raw ? value : encodeURIComponent(value),
                options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
                options.path ? '; path=' + options.path : '',
                options.domain ? '; domain=' + options.domain : '',
                options.secure ? '; secure' : ''
            ].join(''));
        }

        // read
        var decode = config.raw ? raw : decoded;
        var cookies = document.cookie.split('; ');
        var result = key ? undefined : {};
        for (var i = 0, l = cookies.length; i < l; i++) {
            var parts = cookies[i].split('=');
            var name = decode(parts.shift());
            var cookie = decode(parts.join('='));

            if (key && key === name) {
                result = converted(cookie);
                break;
            }

            if (!key) {
                result[name] = converted(cookie);
            }
        }

        return result;
    };

    config.defaults = {};

    $.removeCookie = function(key, options) {
        if ($.cookie(key) !== undefined) {
            // Must not alter options, thus extending a fresh object...
            $.cookie(key, '', $.extend({}, options, {expires: -1}));
            return true;
        }
        return false;
    };

}));