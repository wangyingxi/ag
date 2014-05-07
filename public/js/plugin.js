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
                phototypeUrl: '/manage/phototype/list',
                thumbnailOnly: true
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
            $this.append('<div class="gy-photo-selected"></div>');
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
            style += ".modal-dialog {width:85%;}";
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
            style += ".modal-menu .nav {background:#F8F8F8; padding: 10px 20px 0 20px;}";
            style += ".modal-menu .nav * {font-size:smaller;}";
            style += ".gallery .label-warning {font-size:smaller; position:absolute; right:0; top:0}";
            style += "</style>";
            return style;
        },
        renderMenu: function(resource) {
            var $this = $(this), settings = $this.data('settings'), modalId = settings.modalId, $modal = $('#' + modalId);
            var modalMenu = $modal.find('.modal-menu');
            modalMenu.empty();
            var addLi = function(containerUl, name, phototypeId, description, liCls) {
                var $li = $("<li>").attr('phototype-id', phototypeId);
                if (liCls)
                    $li.addClass(liCls);
                var $a = $("<a>").attr("href", "javascript:void(0)")
                        .attr('title', description)
                        .html(name);
                $li.append($a);
                $li.click(function() {
                    var obj = $(this).closest('li');
                    var cls = "active";
                    if (!obj.hasClass(cls)) {
                        obj.siblings('li').removeClass(cls);
                        obj.addClass(cls);
                        // request
                        var pid = obj.attr('phototype-id');
                        $modal.attr('loaded', null);
                        $this.photoSelector('request', {phototype: pid});
                    }
                });
                containerUl.append($li);
            };
            var $ul = $("<ul>").addClass("nav nav-tabs");
            addLi($ul, "全部", null, null);
            $.each(resource, function() {

                var item = $(this);
                var name = item[0].name;
                var description = item[0].description;
                var phototypeId = item[0].id;
                addLi($ul, name, phototypeId, description);
            });
            modalMenu.append($ul);
            $($ul.find('li').get(0)).click();
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
                    var thumbnail = item[0].thumbnail;
                    var img = $('<img>')
                            .addClass('gallery-img');
                    var gallery = $('<div>')
                            .addClass('gallery')
                            .attr('name', name)
                            .attr('type', type);
                    if (!thumbnail) {
                        img.attr('src', item[0].path.orig);
                        gallery.append("<label class='label label-warning'>无缩略</label>");
                    } else {
                        img.attr('src', item[0].path.main);
                        gallery.attr('thumbnail', true);
                    }
                    gallery.append(img);
                    gallery.append($("<span>").addClass('label label-success').html('已选择'));
                    gallery.click(function() {
                        if (settings.thumbnailOnly && !$(this).attr('thumbnail')) {
                            alert("抱歉，不能选择该图片（因为没有缩略图）");
                            return false;
                        }

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
                        var data = {page: page};
                        // 请求对应的phototype id
                        var phototype = $modal.find('.modal-menu .active').attr('phototype-id');
                        if (phototype)
                            data.phototype = phototype;
                        $this.photoSelector('request', data);
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
        request: function(param) {
            var $this = $(this), settings = $this.data('settings'), modalId = settings.modalId, $modal = $('#' + modalId);
            if (!param)
                param = {};
            if (!param.page)
                param.page = 1;
            var url = settings.url;
            var data = {format: 'json', page: param.page};
            if (param.phototype)
                data.phototype = param.phototype;
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
                            $this.photoSelector('renderPagebar', {page: param.page, count: count});
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
            if (!settings.thumbnailOnly) {
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
    $.fn.integerInput = function() {
        var $this = $(this);
        $this.bind("keydown", function(event) {
            if (event.keyCode > 57 || event.keyCode < 48) {
                if (event.keyCode != 8) {
                    return false;
                }
            }
        });
        $this.bind('contextmenu', function(e) {
            return false;
        });
    }
    $.fn.xCenter = function() {
        var $this = $(this);
        var parent = $this.parent();
        var pw = parent.width();
        if ($this.css('position') != 'absolute' || $this.css('position') != 'fixed') {
            $this.css('position', 'absolute');
        }
        $this.css('left', (pw - $this.width()) / 2);
    }


    $.currency = {
        option: {
            cookie_name: 'currency',
            currency_option: {expires: 365, path: '/'},
            ddl_id: 'currency-ddl',
            ddl_itm_cls: 'currency-ddl-itm',
            selector: '.price-option'
        },
        init: function(selector) {
            if (!selector)
                selector = this.option.selector;
            // write css
            var style = "<style>";
            style += ".price-option {display:none}";
            style += ".price-option:first-child {display:inline;}";
            style += "#" + this.option.ddl_id + " {background:#FFF;box-shadow:0 0 10px rgba(0,0,0,0.2);display:none;position:absolute;width:90px;z-index:1}";
            style += "#" + this.option.ddl_id + " ." + this.option.ddl_itm_cls + " {cursor:pointer;display:block;padding:5px 20px;text-align:center;border-bottom:1px solid #F2F2F2;}";
            style += "#" + this.option.ddl_id + " ." + this.option.ddl_itm_cls + ":hover {background:#F8F8F8;}";
            style += "#" + this.option.ddl_id + " ." + this.option.ddl_itm_cls + ".selected {background:#F8F8F8 !important}"
            style += "</style>";
            $('body').append(style);
            var cookie_name = this.option.cookie_name;
            var price_option = $(selector);
            var option = this.option.currency_option;
            var ddl_id = this.option.ddl_id;
            var cookie_value = $.cookie(cookie_name);
            var html = $("<div>").attr('id', ddl_id).addClass('auto-hide');
            var base_cls = this.option.ddl_itm_cls;
            $.each(price_option, function() {
                var item = $(this);
                var ddl_item = $("<div>").addClass(base_cls).html(item.attr('currency-symbol') + " " + item.attr(cookie_name)).attr(cookie_name, item.attr(cookie_name));
                ddl_item.click(function() {
                    // select currency
                    var $this = $(this).closest('.' + base_cls);
                    var currency = $this.attr(cookie_name);
                    $('.price').hide();
                    $('.price[currency=' + currency + ']').show();
                    var ddl = $('#' + ddl_id);
                    if (!$this.hasClass('selected')) {
                        $.cookie(cookie_name, currency, option);
                        $this.siblings().removeClass('selected');
                        $this.addClass('selected');
                    }
                    ddl.hide();
                    price_option.hide();
                    $(selector + "[currency=" + currency + "]").show();
                });
                html.append(ddl_item);
                // item click
                item.click(function() {
                    // toggle ddl board
                    var $this = $(this);
                    var x = $this.offset().left;
                    var y = $this.offset().top + 24;
                    var ddl = $('#' + ddl_id);
                    ddl.css('left', x).css('top', y);
                    ddl.toggle();
                });
            });
            $('body').append(html);
            this.refresh();
        },
        refresh: function() {
            var cookie_name = this.option.cookie_name;
            var cookie_value = $.cookie(cookie_name);
            var currency_ddl_itm = $('#' + this.option.ddl_id + ' .' + this.option.ddl_itm_cls);
            if (!cookie_value) {
                // 将第一个置为选中状态
                currency_ddl_itm.first().click();
            } else {
                var target = $('.' + this.option.ddl_itm_cls + '[currency=' + cookie_value + ']');
                target.addClass('selected');
                target.click();
            }
        }
    };
    $.initCurrency = function(selector) {
        if (!selector)
            selector = '.price-option';
// write css
        var style = "<style>";
        style += ".price-option {display:none}";
        style += ".price-option:first-child {display:inline;}";
        style += "#currency-ddl {background:#FFF;box-shadow:0 0 10px rgba(0,0,0,0.2);display:none;position:absolute;width:90px;z-index:1}";
        style += "#currency-ddl .currency-ddl-itm {cursor:pointer;display:block;padding:5px 20px;text-align:center;border-bottom:1px solid #F2F2F2;}";
        style += "#currency-ddl .currency-ddl-itm:hover {background:#F8F8F8;}";
        style += "#currency-ddl .currency-ddl-itm.selected {background:#F8F8F8 !important}"
        style += "</style>";
        $('body').append(style);
        var cookie_name = 'currency';
        var price_option = $(selector);
        var option = {expires: 365, path: '/'};
        var ddl_id = 'currency-ddl';
        var cookie_value = $.cookie(cookie_name);
        var html = $("<div>").attr('id', ddl_id).addClass('auto-hide');
        $.each(price_option, function() {
            var item = $(this);
            var ddl_item = $("<div>").addClass("currency-ddl-itm").html(item.attr('currency-symbol') + " " + item.attr(cookie_name)).attr(cookie_name, item.attr(cookie_name));
            ddl_item.click(function() {
                // select currency
                var $this = $(this).closest('.currency-ddl-itm');
                var currency = $this.attr(cookie_name);
                $('.price').hide();
                $('.price[currency=' + currency + ']').show();
                var ddl = $('#' + ddl_id);
                if (!$this.hasClass('selected')) {
                    $.cookie(cookie_name, currency, option);
                    $this.siblings().removeClass('selected');
                    $this.addClass('selected');
                }
                ddl.hide();
                price_option.hide();
                $(selector + "[currency=" + currency + "]").show();
            });
            html.append(ddl_item);
            // item click
            item.click(function() {
                // toggle ddl board
                var $this = $(this);
                var x = $this.offset().left;
                var y = $this.offset().top + 24;
                var ddl = $('#' + ddl_id);
                ddl.css('left', x).css('top', y);
                ddl.toggle();
            });
        });
        $('body').append(html);
        var currency_ddl_itm = html.find('.currency-ddl-itm');
        if (!cookie_value) {
            // 将第一个置为选中状态
            currency_ddl_itm.first().click();
        } else {
            var target = $('.currency-ddl-itm[currency=' + cookie_value + ']');
            target.addClass('selected');
            target.click();
        }
    };
    $.cart = {
        option: {
            cart_name: 'cart',
            cart_option: {
                expires: 365,
                path: '/'
            }
        },
        parse: function() {
            var c = $.cookie(this.option.cart_name);
            if (!c)
                c = "";
            try {
                c = JSON.parse(c);
            } catch (e) {
                c = {};
            }
            return c;
        },
        query: function(id) {
            var c = this.parse();
            var val = c[id];
            if (!val) {
                return false;
            } else {
                return val;
            }
        },
        set: function(id, qty) {
            if (typeof qty === 'number') {
                var c = this.parse();
                c[id] = qty;
                this.write(JSON.stringify(c));
            } else {
                return false;
            }
        },
        add: function(id, qty) {
            if (typeof qty === 'number') {
                var c = this.parse();
                var hasIt = false;
                if (c) {
                    // update it
                    $.each(c, function(k, v) {
                        if (k === id) {
                            v = v + qty;
                            if (v > 0) {
                                c[k] = v;
                            } else {
                                delete c[k];
                            }
                            hasIt = true;
                        }
                    });
                }
                if (!hasIt) {
                    // new one
                    c[id] = qty;
                }
                this.write(JSON.stringify(c));
            } else {
                return false;
            }
        },
        write: function(val) {
            $.cookie(this.option.cart_name, val, this.option.cart_option);
        },
        empty: function() {
            this.write(null);
        },
        total: function() {
            var c = this.parse();
            var sum = 0;
            if (c) {
                $.each(c, function(k, v) {
                    sum++;
                });
            }
            return sum;
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