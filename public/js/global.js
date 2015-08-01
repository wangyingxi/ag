
// jquery.rotate.js
(function($) {
    function initData($el) {
        var _ARS_data = $el.data('_ARS_data');
        if (!_ARS_data) {
            _ARS_data = {rotateUnits: 'deg', scale: 1, rotate: 0};
            $el.data('_ARS_data', _ARS_data);
        }
        return _ARS_data;
    }

    function setTransform($el, data) {
        $el.css('transform', 'rotate(' + data.rotate + data.rotateUnits + ') scale(' + data.scale + ',' + data.scale + ')');
    }

    $.fn.rotate = function(val) {
        var $self = $(this), m, data = initData($self);
        if (typeof val === 'undefined') {
            return data.rotate + data.rotateUnits;
        }
        m = val.toString().match(/^(-?\d+(\.\d+)?)(.+)?$/);
        if (m) {
            if (m[3]) {
                data.rotateUnits = m[3];
            }
            data.rotate = m[1];
            setTransform($self, data);
        }
        return this;
    };
    $.fn.scale = function(val) {
        var $self = $(this), data = initData($self);
        if (typeof val === 'undefined') {
            return data.scale;
        }
        data.scale = val;
        setTransform($self, data);
        return this;
    };
    var curProxied = $.fx.prototype.cur;
    $.fx.prototype.cur = function() {
        if (this.prop === 'rotate') {
            return parseFloat($(this.elem).rotate());
        } else if (this.prop === 'scale') {
            return parseFloat($(this.elem).scale());
        }
        return curProxied.apply(this, arguments);
    };
    $.fx.step.rotate = function(fx) {
        var data = initData($(fx.elem));
        $(fx.elem).rotate(fx.now + data.rotateUnits);
    };
    $.fx.step.scale = function(fx) {
        $(fx.elem).scale(fx.now);
    };
    var animateProxied = $.fn.animate;
    $.fn.animate = function(prop) {
        if (typeof prop['rotate'] != 'undefined') {
            var $self, data, m = prop['rotate'].toString().match(/^(([+-]=)?(-?\d+(\.\d+)?))(.+)?$/);
            if (m && m[5]) {
                $self = $(this);
                data = initData($self);
                data.rotateUnits = m[5];
            }
            prop['rotate'] = m[1];
        }
        return animateProxied.apply(this, arguments);
    };
})(jQuery);




/*
 * sku是否可用
 * @param {type} url
 * @returns {integer}
 */
function isSkuExist(url) {
    $('#is-sku-exist').click(function() {
        var okmsg = '该SKU可以使用';
        var sorrymsg = '抱歉，已经存在，无法使用';
        var pleasemsg = "请输入SKU值";
//                $('#sku').attr('data-content', 'content').attr('data-original-title', 'title');
//                $('#sku').popover('show');
        var sku = $('#sku').val();
        if (sku) {
            var os = $(this).attr('origin-sku');
            if (sku === os) {
                alert(okmsg);
                return;
            }

            $.ajax({
                url: url,
                dataType: 'json',
                type: 'POST',
                data: {sku: sku},
                success: function(response) {
                    if (response === 1) {
                        // existing
                        alert(sorrymsg);
                    } else {
                        // not existing
                        alert(okmsg);
                    }
                }
            });
        } else {
            alert(pleasemsg);
        }
    });
}

/*
 * manage-product-create
 * manage-product-save
 * 根据基本价格以及汇率计算出所有货币对应价格
 */
function calculatePrice() {
    $('#calculate').click(function() {
        var baseprice = $('#base-rmb').val();
        $.each($('.curreny-rate'), function() {
            var rate = $(this).val();
            var result = baseprice / rate;
            if (result) {
                $('#' + $(this).attr('for')).val(result.toFixed(2) * 1);
            }
        });
    });
}
function checkPriceFixed() {
    var valid = true;
    $('.price').each(function() {
        if ($(this).val() <= 0) {
            valid = false;
        }
    });
    if (!valid) {
        alert('您打算免费卖给鬼佬吗？亲～');
        return false;
    } else {
        return true;
    }
}

/* 
 * manage-product-create
 * manage-product-save
 * 保存按钮前对选中图片信息的保存
 * 保存按钮前对选中仓储信息的保存
 */
function productSaveBtn(separator) {
    $('#save-btn').click(function() {
        // 保存选中的图片
        var save = $('#gy-photo').attr('save');
        if (!save || save === '{}') {
            $('#photo').val('');
        } else {
            $('#photo').val(save);
        }

        // 保存选中的location
        var location = $('.location-ul input[type="checkbox"]:checked');
        if (location.length) {
            var locations = new Array();
            $.each(location, function() {
                var c = $(this).attr('id');
                if (c)
                    locations.push($(this).attr('id'));
            });
            location = locations.join(separator);
        } else {
            location = '';
        }
        $('#location').val(location);
    });
}

function removeObject($this, url, containerSelector, valSelector) {
    if (!confirm('确认删除该对象？')) {
        return;
    }
    if (!containerSelector) {
        containerSelector = '.itm';
    }
    if (!valSelector) {
        valSelector = '.tmp';
    }
    var container = $this.closest(containerSelector);
    var id = container.find(valSelector).val();
    var data = {id: id};
    $.ajax({
        url: url,
        dataType: 'json',
        data: data,
        type: 'POST',
        success: function(response) {
            if (response) {
                container.fadeOut(400, function() {
                    container.remove();
                });
            } else {
                alert('删除失败');
            }
        }
    });
}

function switchLoginWindowTo(obj) {
    var cls = "selected";

    obj = $(obj);
    var type = obj.attr('tag');
    if (!type)
        throw "tag attribute is undefined";

    var container = obj.closest('.login-window');
    var btns = container.find('.btn-group .btn');
    btns.removeClass(cls);
    $.each(btns, function() {
        var $this = $(this);
        if ($this.attr('tag') === type) {
            $this.addClass(cls);
        }
        return;
    });
    var board = container.find('.' + type + '-board');
    board.siblings('.frm').hide();
    board.show();
}

function login(obj, url) {
    var container = $(obj).closest('.frm');
    var valid_result = container.find('.valid-result');
    valid_result.hide();
    var email = container.find('.email_val').val();
    if (!email.isEmail()) {
        valid_result.find('span').html('Please input correct email');
        valid_result.show();
        return;
    }
    var password = container.find('.password_val').val();
    if (!password) {
        valid_result.find('span').html('Please input password');
        valid_result.show();
        return;
    }

    $(obj).prop('disabled', true).addClass('half-opacity').attr('alt', $(obj).html()).html('sending ...');
    $.ajax({
        url: url,
        dataType: 'json',
        data: {email: email, password: password, format: 'json'},
        method: 'post',
        success: function(response) {
            if (response.code === 200) {
                location.reload();
            } else {
                valid_result.html(response.msg).show();
            }
        },
        error: function() {
            valid_result.html('network error, please try again').show();
        },
        complete: function() {
            $(obj).prop('disabled', false).removeClass('half-opacity').html($(obj).attr('alt'));
        }
    });
}
function register(obj, url) {
    $.popupclose();

    var container = $(obj).closest('.frm');
    var valid_result = container.find('.valid-result');
    valid_result.hide();
    var email = container.find('.email_val').val();
    if (!email.isEmail()) {
        valid_result.find('span').html('Please input correct email');
        valid_result.show();
        return;
    }
    var username = container.find('.nick_name_val').val();
    if (!username) {
        valid_result.find('span').html('Please input Nick Name, we will know how to call you');
        valid_result.show();
        return;
    }
    var password = container.find('.password_val').val();
    if (!password) {
        valid_result.find('span').html('Please input password');
        valid_result.show();
        return;
    }
    $(obj).prop('disabled', true).addClass('half-opacity').attr('alt', $(obj).html()).html('sending ...');
    $.ajax({
        url: url,
        dataType: 'json',
        data: {email: email, username: username, password: password, format: 'json'},
        method: 'post',
        success: function(response) {
            if (response.code === 200) {
                // 弹框提示注册成功，并且自动登录后刷新
                $.popupclose();
                var html = "<p class='marginbottom5px green'><i class='glyphicons circle_ok'></i> Congratulation! Successfully registered.</p><button class='sm-btn black-btn' onclick='location.reload();'>Login and Continue</button>";
                $.alertbox({msg: html, width: 400, closebtn: false});

            } else {
                valid_result.find('span').html(response.msg);
                valid_result.show();
            }
        },
        error: function() {
            valid_result.html('network error, please try again').show();
        },
        complete: function() {
            $(obj).prop('disabled', false).removeClass('half-opacity').html($(obj).attr('alt'));
        }
    });
}

function switchTopC() {
    var DURA = 200;
    $.each($('.ftb'), function() {
        var $this = $(this).closest('.ftb');
        var top_c = $this.find('.top-c');
        var input = $this.find('.nothing-txt');
        if (top_c.length && input.length) {
            top_c.html(input.attr('placeholder'));
            if (input.val()) {
                top_c.fadeIn(DURA);
            } else {
                top_c.fadeOut(DURA);
            }
        }
    });

}

String.prototype.isEmail = function() {
    var reg = /^(\w)+(\.\w+)*@(\w)+((\.\w+)+)$/;
    return reg.test(this);
}