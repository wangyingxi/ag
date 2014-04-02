(function($) {
    var DURATION = 100;

    var methods = {
        init: function(options) {
            var trigger = $(this);
            trigger.prop('disabled', true);
            if (!options)
                options = {};
            // options.multiSelect
            var multiSelect = false;
            if (options.multiSelect)
                multiSelect = true;

            alert(trigger.modalId());

            var launchBtn = $("<input>")
                    .attr('type', 'button')
                    .attr('data-toggle', 'modal')
                    .attr('data-target', '#' + modalId)
                    .addClass('btn btn-success btn-sm')
                    .val('选择图片');


            trigger.append(launchBtn);

            trigger.prop('disabled', false);
        },
        modalId: function() {
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
            modalHtml += '<div class="modal-footer">';
            modalHtml += '<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>';
            modalHtml += '<button type="button" class="btn btn-primary">Save changes</button>';
            modalHtml += '</div>';
            modalHtml += '</div>';
            modalHtml += '</div>';
            modalHtml += '</div>';

            var modalPopup = $(modalHtml).attr('id', modalId);
            $('body').append(modalPopup);

            return modalId;
        }
    }

    $.fn.photoSelector = function(method) {

        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
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