
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
    if(!containerSelector) {
        containerSelector = '.itm';
    }
    if(!valSelector) {
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