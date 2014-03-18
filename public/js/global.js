var REG_EMAIL = /^[a-zA-Z0-9_+.-]+\@([a-zA-Z0-9-]+\.)+[a-zA-Z0-9]{2,4}$/;
var REG_PWD = /^.{6,}$/;
var REG_NAME = /^.{2,}$/;


function init() {
	windowScroll();
        enterClick();
        bindIntegerInput();
}
function bindIntegerInput() {
    $('.integer').change(function(e){
        var object = $(e.target);
        var val = object.val();
        var val_int = parseInt(val);
        if(isNaN(val_int)) {
            object.val(0);
        } else {
            object.val(val_int);
        }
    });
    
}
function findParentByClass(object, className) {
    var i = 0;
    if(object.hasClass(className)) {
        return object;
    }
    var par = object.parent();
    while (!par.hasClass(className)) {
        par = par.parent();
        i++;
        if (i >= 1000) {
            return false; 
        }
    }
    return par;
}
function listBindItemWrapper() {
	var itemwrapperClassName = 'itemwrapper';
	$('.' + itemwrapperClassName).mouseenter(function(event){
		var object = $(event.target);
		
		if(!object.hasClass(itemwrapperClassName)){
			object = findParentByClass(object, itemwrapperClassName);
		}
		var bar = object.find('.bar');
		var head = object.find('.head');

		if(bar.length > 0){
			head.animate({top:-70},200);
			bar.animate({bottom:0},200);
		}
	});
	$('.' + itemwrapperClassName).mouseleave(function(event){
		var object = $(event.target);
		
		if(!object.hasClass(itemwrapperClassName)){
			object = findParentByClass(object, itemwrapperClassName);
		}
		var head = object.find('.head');
		var bar = object.find('.bar');
		
		if(bar.length > 0){
			head.animate({top:0},200);
			bar.animate({bottom:-95},200);
		}
	});
}

function windowScroll() {
    if($('.gototopbt').length === 0) {
            var html = "<div class='gototopbt' onclick=\"$('html, body').animate({ scrollTop: 0 }, 300);\" visible='n'>";
            $('body').append(html);
    }
    $(window).scroll(function () {

        var validatedOffset = 600;
        var offset = $(this).scrollTop();
        var bt = $('.gototopbt');
        var attrValue = bt.attr('visible');
        if (attrValue === 'y') {
            // 检查偏移量是否过小需要隐藏
            if (offset < validatedOffset) {
                bt.attr('visible','n');
                bt.fadeOut();
            }
        } else {
            // 检查偏移量是否过大需要显示
            if (offset > validatedOffset) {
                bt.attr('visible','y');
                bt.fadeIn();
            }
        }
    });
}

function enterClick() {
    var object = $('.enterclick');
    if(object.length > 0){
        object.bind("keydown",function(event){    
            if(event.keyCode===13){    
                var _obj = $(event.target);
                var enterclickobjid = _obj.attr('enterclickobjid');
                $('#' + enterclickobjid).click();
            }
        });
    }
}

function inputCheckingEmail() {
    var result = true;
    var reg = REG_EMAIL;
    var email = emailObj.val();
    if (reg.test(email)) {
        $.post('/isemailexist/' + email, {email:email}, function(result){
            if(result === 0) {
                emailObj.validResultShow('right','该邮箱可以注册',100);
            } else {
                emailObj.validResultShow('error','该邮箱已经存在',100);
                result = false;
            }
        });
    } else {
            emailObj.validResultShow('error','请输入正确的邮箱',100);
            result = false;
    }
    return result;
}
function bindAttachFile() {
    var af = $('.attachfile');
    if(af.length === 0) return;
    var dels = af.find('.delete');
    dels.click(function(e){
        if(confirm('确定删除该文件？')){
            var a = findParentByClass($(e.target),'attachfile');
            a.fadeOut(100);
        }
        return false;
    });
}

function deleteUploadedDoc(e, url, doctype, company_id, doc_id) {
    if(confirm('确定删除该文件？')) {
        var object = $(e);
        // 提交Ajax
        var data = { doctype:doctype, company_id:company_id, doc_id:doc_id };
        $.post(url, data, function(response){
            if(response === 1) {
                var wrapper = findParentByClass(object,'uploadedwrapper');

                var items = wrapper.find('.fileitem');

                if(items.length === 1) {
                    wrapper.find('.uploadInput').val(0);
                }

                var itemwrapper = findParentByClass(object,'fileitem');
                itemwrapper.remove();
                // 提示成功
                checkAllValid();
                $.validResultReset();
            } else {
                // 提示错误
            }
        });
    }
    
}

function checkAllValid() {
    var result = true;
    var invalidObjCollection = new Array();
    $('.infoblock').each(function(){

        var blockid = $(this).find('.pagetitle').attr('id');
        var resultObj = validBlockByBlockId(blockid);
        var tag = $("li[for=" + resultObj.blockId + "]").find('a');
        if(resultObj.result){
            tag.addClass('saved');
        } else {
            tag.removeClass('saved');
            result = false;
            invalidObjCollection = invalidObjCollection.concat(resultObj.invalidId);
        }
    });
    var data = {'result':result, 'invalidId':invalidObjCollection};
    return data;
}

function validBlockByBlockId(blockid) {
    var infoblock = findParentByClass($('#' + blockid),'infoblock');
    var requiredfield = infoblock.find("[requiredfield='yes']");
    var invalidObject = new Array();
    var passed = true;
    if(requiredfield.length === 0) {
        passed = false;
    }
    requiredfield.each(function(){
        var tagName = $(this).get(0).tagName;
        var val = $(this).val();
        switch(tagName) {
            case "SELECT":
                if (val * 1 <= 0) {
                    // 不通过
                    passed = false;
                    var iid = $(this).attr('id');
                    invalidObject.push(iid);
                }
                break;
            case "INPUT":

                var type = $(this).attr('type');
                if(type === 'hidden') {
                    if(val * 1 < 1) {
                        passed = false;
                        var iid = $(this).attr('for');
                        invalidObject.push(iid);
                    }
                } else if (type === 'text') {
                    if(!(val.length > 0)) {
                        passed = false;
                        var iid = $(this).attr('id');
                        invalidObject.push(iid);
                    }
                } else {
                    alert("开发异常：预期外的控件类型");
                }
                break;
            case "TEXTAREA":
                if(!(val.length > 0)) {
                    passed = false;
                    var iid = $(this).attr('id');
                    invalidObject.push(iid);
                }
                break;
            default:
                alert("开发异常：预期外的控件类型");
                break;
        }
    });
    return {'result':passed, 'invalidId':invalidObject, blockId:blockid};
}

function validBlockByObject(object) {
    var infoblock = findParentByClass(object, 'infoblock');
    var blockid = infoblock.find('.pagetitle').attr('id');

    return validBlockByBlockId(blockid);
}
function bindTabItem(blocks, tabClass){
    $('.' + tabClass).click(function(e){
        var object = $(e.target);
        var blockClass = object.attr('for');
        var block = $('.' + blockClass);
        blocks.hide();
        block.show();

        $('.' + tabClass).removeClass('selected');
        object.addClass('selected');
    });
}

function bindPhotoPage() {
    var photoPageClassName = 'photopage';
    $('.' + photoPageClassName).click(function(e){
        var object = $(e.target);
        object = findParentByClass(object, photoPageClassName);

        var isVideo = object.hasClass('videopage') ? true : false;

        if(isVideo) {
            $('#bigphotowrapper').hide();
            $('#bigvideowrapper').show();
        } else {
            $('#bigphotowrapper').show();
            $('#bigvideowrapper').hide();
            var origUrl = object.find('.photopagelink').attr('src');
            $('#bigphoto').attr('src', origUrl);
        }
        $('.photopages .dot').removeClass('selected');
        object.find('.dot').addClass('selected');

    });
}