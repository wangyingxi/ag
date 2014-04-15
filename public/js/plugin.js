(function($) {
    var DURATION = 100;

    $.fn.setSelectValue = function(options) {
        if (!options) {
            options = {};
        }
        var settings = {
            cls: "",
            tagName: "select"
        };
        $.extend(settings, options);

        var $this = $(this);
        if ($this.get(0).tagName.toLowerCase() === settings.tagName) {
            var val = $this.attr('value');
            if (val) {
                $.each($this.find('option'), function() {
                    if (val === $(this).attr('value')) {
                        $(this).prop('selected', true);
                    } else {
                        $(this).prop('selected', false);
                    }
                });
            }
        } else {
            throw("dev exception : wrong tagname");
        }
    };

    var photoSelectorMethods = {
        init: function(options, save) {
            var $this = $(this);
            $this.prop('disabled', true);
            if (!options) {
                options = {};
            }
            // 初始化Setting默认值
            var settings = {
                separator: ';',
                multi: true,
                url: '/manage/photo/list',
                phototypeUrl: '/manage/phototype/list'
            };

            // 生成modal框
            var generateModal = function($this) {
                var modalId = 'gy' + new Date().getTime();
                // 避免重复id
                if ($('#' + modalId).length > 0)
                    modalId += new Date().getTime();

                var modalHtml = '<div class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">';
                modalHtml += '<div class="modal-dialog">';
                modalHtml += '<div class="modal-content">';
                modalHtml += '<div class="modal-header">';
                modalHtml += '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>';
                modalHtml += '<h4 class="modal-title">添加图片</h4>';
                modalHtml += '</div>';
                modalHtml += '<div class="modal-menu"></div>';
                modalHtml += '<div class="modal-body"></div>';
                modalHtml += '<div class="modal-page"></div>';
                modalHtml += '<div class="modal-footer">';
                modalHtml += '<button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>';
                modalHtml += '</div>';
                modalHtml += '</div>';
                modalHtml += '</div>';
                modalHtml += '</div>';

                var modalPopup = $(modalHtml).attr('id', modalId);
                var selectBtn = $("<button>")
                        .attr('type', 'button')
                        .attr('class', 'btn btn-primary select-btn')
                        .html('选择');
                selectBtn.click(function() {
                    $this.photoSelector('save');
                });
                modalPopup.find('.modal-footer').append(selectBtn);
                $('body').append(modalPopup);
                return modalId;
            };

            // 合并settings
            settings.modalId = generateModal($this);
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

            // 设置save初始化图片
            if (typeof save === 'object') {
                save = JSON.stringify(save);
            }
            if (save) {
                $this.attr('save', save);
                $this.photoSelector('display');
            }

            $('body').append($this.photoSelector('style'));
        },
        style: function() {
            // write css
            var style = "<style>";
            style += ".modal-page {margin:15px; text-align:center;}";
            style += ".gallery {display:inline-block; margin:5px; padding:5px;position:relative;}";
            style += ".gallery:hover {background-color:skyblue;cursor:pointer;}";
            style += ".gallery.choosen {background:#FFF !important; cursor:default !important;}";
            style += ".gallery .checkbt {display:inline-block; margin:5px;}";
            style += ".gallery-img {height:90px; width:90px;}";
            style += ".gallery.selected {background-color:lightgreen !important;}";
            style += ".modal-page input {margin:0 2px;}";
            style += ".gy-sd {border:1px solid #eee;display:inline-block;padding:10px;margin:0 5px 5px 0;position:relative;}";
            style += ".gy-sd img {height:90px;width:90px}";
            style += ".gy-sd .rm, .gy-sd .lt, .gy-sd .rt {background:#FFF;border:none;color:#888;border:0 0 0 2px;height:24px;line-height:24px;margin:0;right:0; top:0;padding:0;position:absolute;width:24px;}";
            style += ".gy-sd .lt, .gy-sd .rt {left:0; right:auto; top:42px;}";
            style += ".gy-sd .rt {left:auto; right:0;}";
            style += ".gallery .label-success {display:none;position:absolute;left:0;top:0;}";
            style += ".gallery.choosen {opacity:0.5}";
            style += ".gallery.choosen .label-success {display:block;}";
            style += ".modal-menu .nav {margin-top:10px; padding: 0 20px;}";
            style += "</style>";
            return style;
        },
        renderMenu: function(resource) {
            var $this = $(this), settings = $this.data('settings'), modalId = settings.modalId, $modal = $('#' + modalId);
            var modalMenu = $modal.find('.modal-menu');
            modalMenu.empty();

            var addLi = function(containerUl, name, phototypeId, description, liCls) {
                var $li = $("<li>");
                if (liCls)
                    $li.addClass(liCls);
                var $a = $("<a>").attr("href", "javascript:void(0)")
                        .attr('phototype-id', phototypeId)
                        .attr('title', description)
                        .html(name);
                $li.append($a);
                $li.click(function() {
                    var $this = $(this).closest('li');
                    var cls = "active";
                    if (!$this.hasClass(cls)) {
                        $this.siblings('li').removeClass(cls);
                        $this.addClass(cls);
                    }
                });
                containerUl.append($li);
            };

            var $ul = $("<ul>").addClass("nav nav-tabs");
            addLi($ul, "全部", null, null, 'active');
            if (resource) {
                $.each(resource, function() {

                    var item = $(this);
                    var name = item[0].name;
                    var description = item[0].description;
                    var phototypeId = item[0].id;
                    addLi($ul, name, phototypeId, description);
                    modalMenu.append($ul);
                });
            }
        },
        renderPhoto: function(resource) {
            var $this = $(this), settings = $this.data('settings'), modalId = settings.modalId, $modal = $('#' + modalId);
            var modalBody = $modal.find('.modal-body');
            modalBody.empty();
            if (resource) {
                var selected = $modal.attr('selected');
                var arr = false;
                if (selected) {
                    arr = selected.split(settings.separator);
                }
                $.each(resource, function() {
                    var item = $(this);
                    var name = item[0].name;
                    var type = item[0].type;
                    var img = $('<img>')
                            .addClass('gallery-img')
                            .attr('src', item[0].path.small);
                    var gallery = $('<div>')
                            .addClass('gallery')
                            .attr('name', name)
                            .attr('type', type);
                    gallery.append(img);
                    gallery.append($("<span>").addClass('label label-success').html('已选择'));
                    gallery.click(function() {
                        if (!gallery.hasClass('choosen')) {
                            if (!settings.multi) {
                                $(this).closest('.modal-body')
                                        .find('.gallery.selected')
                                        .removeClass('selected');
                            }
                            $(this).closest('.gallery')
                                    .toggleClass('selected');
                        }
                    });
                    if ($.inArray(name, arr) >= 0) {
                        gallery.addClass('selected');
                    }
                    modalBody.append(gallery);
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
                var pageBtn = $("<input>").attr('type', 'button').attr('page', i).addClass('btn btn-default btn-sm').val(i);
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
                        var page = selfBtn.attr('page');
                        $this.photoSelector('request', page);
                    }
                });
                pagebar.append(pageBtn);
            }
        },
        renderChoosen: function() {
            var $this = $(this), settings = $this.data('settings'), modalId = settings.modalId, $modal = $('#' + modalId);
            var s = $this.attr('save');
            if (s && s !== "{}") {
                s = JSON.parse(s);
            }
            var galleries = $modal.find('.gallery');
            $.each(galleries, function() {
                var name = $(this).attr('name');
                if (s && s[name]) {
                    // 标为已选择状态
                    $(this).addClass('choosen');
                } else {
                    // 取消掉的照片恢复可选状态
                    $(this).removeClass('choosen');
                }
            });
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
                        var count = response.count;
                        var resource = response.data;
                        // 显示图片
                        $this.photoSelector('renderPhoto', resource);

                        if (!$modal.attr('loaded')) {
                            // 显示pagebar
                            $this.photoSelector('renderPagebar', {page: page, count: count});
                            $modal.attr('loaded', true);
                        }
                        $this.photoSelector('renderChoosen');
                    }
                }
            });
        },
        requestMenu: function() {
            var $this = $(this), settings = $this.data('settings'), modalId = settings.modalId, $modal = $('#' + modalId);

            var phototypeUrl = settings.phototypeUrl;
            if (!phototypeUrl)
                return;
            var data = {format: 'json'};
            $.ajax({
                url: phototypeUrl,
                dataType: 'json',
                data: data,
                success: function(response) {
                    if (response.code === 200) {
                        var resource = response.data;
                        if (!$modal.attr('loaded')) {
                            // 显示menu
                            $this.photoSelector('renderMenu', resource);
                        }
                    }
                }
            });
        },
        start: function() {
            var $this = $(this), settings = $this.data('settings'), modalId = settings.modalId, $modal = $('#' + modalId);

            if (!$modal.attr('loaded')) {
                $this.photoSelector('request', 1);
                $this.photoSelector('requestMenu');
            } else {
                $this.photoSelector('renderChoosen');
            }
        },
        save: function() {
            var $this = $(this), settings = $this.data('settings'), modalId = settings.modalId, $modal = $('#' + modalId);
            var arr = $modal.find('.selected');
            // 检查图片是否已经存在
            var se = $this.attr('save');
            var sn = new Array();
            if (se) {
                se = JSON.parse(se);
                $.each(se, function(name, path) {
                    sn.push(name);
                });
            } else {
                se = {};
            }
            $.each(arr, function() {
                var name = $(this).attr('name');
                if ($.inArray(name, sn) >= 0) {
                    // 忽略
                } else {
                    se[name] = $(this).find('.gallery-img').attr('src');
                }

                $(this).removeClass('selected');
            });
            $this.attr('save', JSON.stringify(se));
            $modal.modal('hide');
            $this.photoSelector('display');
        },
        display: function() {
            // 选中图片显示在gy-photo container中
            var $this = $(this), settings = $this.data('settings'), modalId = settings.modalId, $modal = $('#' + modalId);
            var save = $this.attr('save');
            var gyPhotoSelected = $this.find('.gy-photo-selected');
            gyPhotoSelected.empty();
            if (save) {
                save = JSON.parse(save);

                // 重组save值
                var redisplay = function(trigger) {
                    var result = "";
                    var gs = $this.find('.gy-sd');
                    $.each(gs, function() {
                        if (!result)
                            result = {};
                        var n = $(this).attr('name');
                        var p = $(this).find('img').attr('src');
                        result[n] = p;
                    });
                    if (result)
                        result = JSON.stringify(result);
                    $this.attr('save', result);
                };

                $.each(save, function(name, path) {
                    var item = $('<div>').addClass('gy-sd')
                            .attr('name', name);
                    var rm = $("<button>").attr('type', 'button').html('&times;').addClass('rm');
                    rm.click(function() {
                        // 删除
                        var gysdItem = $(this).closest('.gy-sd');
                        gysdItem.remove();
                        redisplay($(this));
                        $this.photoSelector('renderChoosen');
                    });
                    item.append(rm);
                    var lt = $("<button>").attr('type', 'button').html('&lt;').addClass('lt');
                    lt.click(function() {
                        var gysdItem = $(this).closest('.gy-sd');
                        var ltElem = gysdItem.prev();
                        if (ltElem.length > 0) {
                            // 左移
                            var gysdItemCopy = gysdItem.clone(true);
                            gysdItem.remove();
                            ltElem.before(gysdItemCopy);

                            redisplay($(this));
                        }
                    });
                    item.append(lt);
                    var rt = $("<button>").attr('type', 'button').html('&gt;').addClass('rt');
                    rt.click(function() {
                        var gysdItem = $(this).closest('.gy-sd');
                        var rtElem = gysdItem.next();
                        if (rtElem.length > 0) {
                            // 右移
                            var gysdItemCopy = gysdItem.clone(true);
                            gysdItem.remove();
                            rtElem.after(gysdItemCopy);

                            redisplay($(this));
                        }
                    });
                    item.append(rt);
                    item.append($("<img>").attr('src', path));
                    gyPhotoSelected.append(item);
                });
            }
        }
    };

    /*
     * 启动方法
     * @param {type} method     传递字符串，将被识别为方法名; 传递对象，将作为init方法的参数（一般是options）, 并调用init方法
     * @returns {unresolved}
     */
    $.fn.photoSelector = function(method) {
        if (photoSelectorMethods[method]) {
            return photoSelectorMethods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return photoSelectorMethods.init.apply(this, arguments);
        } else {
            $.error('The method ' + method + ' does not exist in $.uploadify');
        }
    };

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