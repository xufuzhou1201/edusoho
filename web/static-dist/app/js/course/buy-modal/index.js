webpackJsonp(["app/js/course/buy-modal/index"],{"10937d5cdf1e407295a9":function(e,a,t){"use strict";var n=t("b843b6d59bfac301cf77"),r=function(e){return e&&e.__esModule?e:{default:e}}(n),o=$("#course-buy-form"),s=o.closest(".modal"),i=new r.default(o);$("#show-coupon-input").on("click",function(){var e=$(this).parents("form");"hide"==$(this).data("status")?(e.find(".coupon-input-group").removeClass("hide"),e.find("#show-coupon").addClass("hide"),e.find("#hide-coupon").removeClass("hide"),$(this).data("status","show")):"show"==$(this).data("status")&&(e.find(".coupon-input-group").addClass("hide"),e.find("#show-coupon").removeClass("hide"),e.find("#hide-coupon").addClass("hide"),$(this).data("status","hide"))}),$("input[role='payTypeChoices']").on("click",function(){$("#password").prop("type","password"),"chargeCoin"==$(this).val()?($("#screct").show(),$('[name="password"]').rules("add",{required:!0,remote:!0}),parseFloat($("#leftMoney").html())<parseFloat($("#neededMoney").html())&&($("#notify").show(),s.find("[type=submit]").addClass("disabled"))):"zhiFuBao"==$(this).val()&&($('[name="password"]').rules("remove"),$("#screct").hide(),$("#notify").hide(),s.find("[type=submit]").removeClass("disabled"))}),$(".btn-use-coupon").on("click",function(){coupon_code=$("[name=coupon]").val(),$.post($(this).data("url"),{code:coupon_code},function(e){if("yes"==e.useable){var a='<span class="control-text"><strong class="money">'+e.afterAmount+'</strong><span class="text-muted">'+Translator.trans("元")+'</span> - <span class="text-muted">'+Translator.trans("已优惠")+"</span><strong>"+e.decreaseAmount+'</strong><span class="text-muted">'+Translator.trans("元")+"</span></span>";$(".money-text").html(a),"0.00"===e.afterAmount&&$("#course-pay").text(Translator.trans("去学习")),$(".coupon-error").html(""),$("[name=coupon]").attr("readonly",!0),$(".btn-use-coupon").addClass("disabled")}else{var t='<span class="text-danger">'+e.message+"</span>";$(".coupon-error").html(t).show(),$("[name=coupon]").val("")}})}),$("#course-pay").click(function(){i.validator.form()&&$()})},b843b6d59bfac301cf77:function(e,a,t){"use strict";function n(e,a){if(!(e instanceof a))throw new TypeError("Cannot call a class as a function")}Object.defineProperty(a,"__esModule",{value:!0});var r=function(){function e(e,a){for(var t=0;t<a.length;t++){var n=a[t];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(e,n.key,n)}}return function(a,t,n){return t&&e(a.prototype,t),n&&e(a,n),a}}(),o=function(){function e(a){n(this,e),this.validator=null,this.$element=a,this.setup()}return r(e,[{key:"setup",value:function(){this.createValidator(),this.initComponents()}},{key:"initComponents",value:function(){$(".date").each(function(){$(this).datetimepicker({autoclose:!0,format:"yyyy-mm-dd",minView:2})})}},{key:"createValidator",value:function(){this.validator=this.$element.validate({rules:{email:{required:!0,email:!0,remote:{url:$("#email").data("url"),type:"get",data:{value:function(){return $("#email").val()}}}},mobile:{required:!0,phone:!0},truename:{required:!0,chinese_alphanumeric:!0,minlength:2,maxlength:36},qq:{required:!0,qq:!0},idcard:{required:!0,idcardNumber:!0},gender:{required:!0},company:{required:!0},job:{required:!0},weibo:{required:!0,url:!0},weixin:{required:!0}},messages:{gender:{required:Translator.trans("请选择性别")},mobile:{phone:"请输入有效手机号(仅支持中国大陆手机号)"}}}),this.getCustomFields()}},{key:"getCustomFields",value:function(){for(var e=1;e<=5;e++)$('[name="intField'+e+'"]').rules("add",{required:!0,int:!0}),$('[name="floatField'+e+'"]').rules("add",{required:!0,float:!0}),$('[name="dateField'+e+'"]').rules("add",{required:!0,date:!0});for(var e=1;e<=10;e++)$('[name="varcharField'+e+'"]').rules("add",{required:!0}),$('[name="textField'+e+'"]').rules("add",{required:!0})}}]),e}();a.default=o}},["10937d5cdf1e407295a9"]);