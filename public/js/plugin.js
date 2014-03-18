(function ($) {
	var DURATION = 100;

	/*
	* 参数 	message 	字符串：		验证结果文字信息
	* 参数 	showEvent 	字符串（可选）	对象显示时的事件名（默认值是'focus'）
	* 参数 	outEvent 	字符串（可选）	对象消失时的事件名（默认值是'blur'）
	* 参数 	offset 		整数 （可选）	水平显示位置偏移量 （默认值是100，若希望显示位置靠左偏移，请设置大于默认值）
	* 参数 	direction	字符串（可选）	显示的提示板箭头朝向（left,top,right,bottom 默认值是left）
	* 参数 	position	字符串（可选）	显示位置模式absolute和fixed（默认值是absolute）
	* 描述	该方法用于输入时同步显示“提示信息”
	*/
	$.fn.inputTip = function (message, showEvent, outEvent, offset, direction, position) {
            var object = $(this);
            var getArrow = function(direction) {
                var div = "<span class='arrow_left'>";
                if(direction === 'top'){
                        div = "<span class='arrow_top'>";
                }
                div += "<span class='arrow1'></span><span class='arrow2'></span><span class='arrow3'></span><span class='arrow4'></span></span>";
                return div;
            };
            
            if(typeof(showEvent) != 'string' || showEvent.length == 0) {
                    showEvent = 'focus';
            }
            if(typeof(outEvent) != 'string' || outEvent.length == 0) {
                    outEvent = 'blur';
            }
            if(typeof(offset) != 'number') {
                    offset = 100;
            }
            // 标记鼠标是否悬浮在上
            object.mouseenter(function(){
                object.attr('on','yes');
            });
            object.mouseleave(function(){
                object.attr('on','no');
            });
            object.bind(showEvent, function() {
                var targetId = object.attr('id');
                if( $('.tipblock[targetid=' + targetId + ']').length > 0 ) {
                    return;
                }
                var top = object.offset().top
                if (position == 'fixed') {
                        top = object.offset().top - $(window).scrollTop();
                }
                var left = object.offset().left;
                var width = object.width();
                var height = object.height();

                
                if (typeof(targetId ) != 'string' || targetId.length == 0) {
                        alert('开发异常：目标对象ID不可为空(inputTip)' + targetId);
                }

                var html="<div class='tipblock' on='no' targetid='" + targetId + "'>" + message + "</div>";
                $('body').append(html);
                var div = $("div[targetid='" + targetId + "']");
                if (direction == 'top') {
                        div.css('left', left);
                        div.css('top', top + height + offset);
                } else {
                        div.css('left', left + width + offset);
                        div.css('top', top);
                }
                if (position == 'fixed') {
                        div.css('z-index',101);
                        div.css('position',position);
                }

                div.append(getArrow(direction));
                div.fadeIn(DURATION);
                div.mouseenter(function(){
                    div.attr('on','yes');
                });
                div.mouseleave(function(){
                    div.attr('on','no');
                    outfunc();
                });
                object.bind(outEvent, function(){
                    outfunc();
                });
                
                var outfunc = function() {
                    var clock = setInterval(function(){
                        var seconds = div.attr('seconds');
                        if(typeof(seconds) !== 'string') {
                            seconds = 0;
                            div.attr('seconds', seconds);
                        }                                
                        if(seconds > 0) {
                            clearInterval(clock);
                            // 1秒以后判断，如果鼠标均不在两者任意上方触发离开事件
                            if(object.attr('on') === 'no' && div.attr('on') === 'no') {
                                div.fadeOut(DURATION, function(){div.remove();});
                                div.attr('seconds', 0);
                            }
                            
                        } else {
                            seconds = seconds * 1 + 1;
                            div.attr('seconds', seconds);
                        }
                    }, 100);
                };
                
                
                
                
            });
	}
	
	/*
	* 参数 	type		字符串			type 结果类型（right:验证成功; error:验证失败）
	* 参数 	message		字符串			验证结果文字信息
	* 参数 	offset 		整数 （可选）		水平显示位置偏移量 （默认值是20，若希望显示位置靠右偏移，请设置大于默认值）
	* 参数 	topOffset 	整数 （可选）		垂直显示位置偏移量 （默认值是4，若希望显示位置靠下偏移，请设置大于默认值）
	* 描述	在输入框控件右侧显示验证结果
	*/
	$.fn.validResultShow = function (type, message, offset, topOffset) {
		var object = $(this);
		var RIGHT = 'right';
		var RIGHTCLASS = 'righticon';
		var ERROR = 'error';
		var ERRORCLASS = 'erroricon';
		var VALIDRESULTCLASS = 'validresult'; 
		var width = object.width();
		if (typeof(offset) != 'number') {
			offset = 20;
		}
		if (typeof(topOffset) != 'number') {
			topOffset = 4;
		}
		if (typeof(message) != 'string' || message.length === 0) {
			message = "&nbsp;";
		}
	
		var targetId = object.attr('id');
		if (typeof(targetId ) != 'string' || targetId.length == 0) {
			alert('开发异常：目标对象ID不可为空(validResultShow)：' + targetId);
		}
	
		var top = object.offset().top;
		var left = object.offset().left;
                
                        // 验证是否已有显示，隐藏
                        if($("label[targetid='" + targetId + "']").length > 0) {
                            this.validResultHide();
                        }
                
		var html = "<label targetid='" + targetId + "' class='" + VALIDRESULTCLASS + " ";
		if(type == RIGHT ) {
			html += RIGHTCLASS;
		} else if (type == ERROR ) {
			html += ERRORCLASS;
		} else {
			alert('开发异常：不正确的提示类型');
			return;
		}
		
		html += "'>" + message + "</label>";
                
                // 去除已有的label
                $(".validresult[targetid='" + targetId + "']").remove();
                
		$('body').append(html);
		var label = $("label[targetid='" + targetId + "']");
		label.css('left', left + width + offset);
		label.css('top', top + topOffset);
                
                // 记录参数用于 Reset
                label.attr('offset',offset);
                label.attr('topOffset',topOffset);
                label.attr('type',type);
                
		label.show();
	}
	/*
	* 描述	移除输入框右侧的验证结果
	*/
	$.fn.validResultHide = function() {
		var object = $(this);
		var targetId = object.attr('id');
		$("label[targetId='" + targetId + "']").remove();
	}
	$.validResultClear = function() {
            var VALIDRESULTCLASS = 'validresult';
            $('.' + VALIDRESULTCLASS).remove();
        }
        $.validResultReset = function() {
            var VALIDRESULTCLASS = 'validresult';
            var validObjects = $('.' + VALIDRESULTCLASS);
            validObjects.each(function(){
                var obj = $(this);
                var targetObj = $('#' + obj.attr('targetid'));
                var offset = obj.attr('offset') * 1;
                var topOffset = obj.attr('topOffset') * 1;
                var type = obj.attr('type');
                var message = obj.html();
                
                $(this).remove();
                targetObj.validResultShow(type, message, offset, topOffset);
                //alert(offset + " '" + topOffset + " " + obj.attr('targetid'));
            });
        }
	/*
	* 参数 	message		字符串		替换文字
	* 描述	移除输入框右侧的验证结果
	*/
	$.fn.processBtn = function(message){
		var object = $(this);
		object.attr('_oldmsg', $(this).val());
		object.val(message);
		object.addClass('processingbtn');
		object.attr('disabled','disabled');
	}
	$.fn.processBtnRestore = function(){
		var object = $(this);
		var val = object.attr('_oldmsg');
		if(typeof(val) != 'string' || val.length == 0) {
			return;
		}
		object.val(val);
		object.removeClass('processingbtn');
		object.attr('disabled', null);
	}
	
	/*
	* 参数 	maxOffset	number		触发偏移量（默认值100）
	* 参数 	stickyClass	string		固定布局引用的css class名
	* 描述	当页面滚动偏移量超过指定值时出现固定布局
	*/
            $.fn.stickyBox = function(maxOffset, stickyClass){
                var object = $(this);
                $(window).scroll(function () {

                    if(typeof(maxOffset) != 'number') {
                            maxOffset = 100;
                    }
                    if(typeof(stickyClass) != 'string' || stickyClass.length == 0) {
                            stickyClass = 'sticky';
                    }

                    var offset = $(window).scrollTop();
                    if(offset > maxOffset ) {
                            object.addClass(stickyClass);

                    } else {
                            object.removeClass(stickyClass);
                    }

                });
            }
        
            $.popup = function (options, templateid){
                var template = $('#' + templateid);
                options = $._popupOption(options);
                $._popupCall(template, options)
            };
	
	/*
	* 参数 	options		配置参数
	*	|	modal		boolean		是否modal窗口，默认值false
        *	|       event           string          触发事件
	*	|	speed		number		效果速度（毫秒），默认值参照DURATION
	*	|	title		string		弹出框标题
	*	|	left		number		左侧间距
	*	|	top		number		顶部间距
	*	|	bg		boolean		是否有背景遮罩层，默认值为true
        *	|       agpopupframe    boolean         是否有内框架，默认为true
	*	|	func		function	加载成功后调用函数
	* 描述	绑定弹出框
	*/
	$.fn.popup = function(options){
            var trigger = $(this);
            var template = $('#' + trigger.attr('popuptargetid'));
            options = $._popupOption(options);
            trigger.bind(options.event,function(){
                $._popupCall(template, options)
            });
	};
        $._popupOption = function(options) {
            options = options || {};
            if(typeof(options.event) != 'string'){
                    options.event = 'click';
            }
            if(typeof(options.speed) != 'number'){
                    options.speed = DURATION;
            }
            if(typeof(options.bg) != 'boolean') {
                    options.bg = true;
            }
            if(typeof(options.agpopupframe) != 'boolean') {
                    options.agpopupframe = true;
            }
            if(typeof(options.title) != 'string'){
                    options.title = '';
            }
            return options
        };
        $._popupCall = function(template, options) {
            if((template.length) === 0 ) {
                alert('开发异常：未找到弹出框内容模版对象');
                return;
            }
            // 所有子元素复制到新的框架中，连同子元素绑定的所有事件
            var contenthtml = template.children().clone(true);
            
            var overlay = "";
            if (options.agpopupframe) {
                    overlay = "<div class='agpopupframe' onclick='event.stopPropagation()'></div>";
            } else {
                    overlay = "<div class='agpopupframe agpopupframehide' onclick='event.stopPropagation()'></div>";
            }
            if (options.bg) {
                    overlay = "<div class='agpopup'>" + overlay + "</div>";
            }
            overlay = $(overlay);
            var closebtn = $("<div class='agpopupclose' onclick='$.popupClose(" + options.speed + ")'></div>");
            var winHeight = $(window).height();
            var winWidth = $(window).width();

            $('body').prepend(overlay);

            var popObj = $('.agpopupframe');
            if(!options.modal) {
                    popObj.append(closebtn);
            }
            if(options.title.length > 0) {
                    var titleblock = $("<h1 class='agpopuptitle'>" + options.title + "</h1>");
                    popObj.append(titleblock);
            }

            popObj.append(contenthtml);

            popObj.css('max-height', winHeight);
            popObj.css('max-width', winWidth);

            overlay.fadeIn(options.speed, function() {
                    if(typeof(options.top) != 'number') {
                            options.top = (winHeight - popObj.height())/2;
                    }
                    if(typeof(options.left) != 'number') {
                            options.left = (winWidth - popObj.width())/2;	
                    }

                    popObj.hide();
                    popObj.css('visibility', 'visible');
                    popObj.css('left',options.left);
                    popObj.css('top',options.top);

                    popObj.fadeIn(DURATION, function(){
                            if(typeof(options.func) != 'undefined') {
                                    options.func();
                            }
                    });
            });
        };
        
        /*
	* 参数      speed       number          退出效果速度（毫秒），默认值参照DURATION
	* 描述	绑定弹出框
	*/
	$.popupClose = function(speed){
		var wrapperObj;
		if( $('.agpopup').length > 0 ) {
			wrapperObj = $('.agpopup');
		} else {
			wrapperObj = $('.agpopupframe')
		}
                if(typeof(speed) != 'number'){
			speed = DURATION;
		}
		wrapperObj.fadeOut(speed, function(){
			wrapperObj.remove();
		});
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
(function (factory) {
	if (typeof define === 'function' && define.amd) {
		// AMD. Register as anonymous module.
		define(['jquery'], factory);
	} else {
		// Browser globals.
		factory(jQuery);
	}
}(function ($) {

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
		} catch(er) {}
	}

	var config = $.cookie = function (key, value, options) {

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
				options.path    ? '; path=' + options.path : '',
				options.domain  ? '; domain=' + options.domain : '',
				options.secure  ? '; secure' : ''
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

	$.removeCookie = function (key, options) {
		if ($.cookie(key) !== undefined) {
			// Must not alter options, thus extending a fresh object...
			$.cookie(key, '', $.extend({}, options, { expires: -1 }));
			return true;
		}
		return false;
	};

}));
