webpackJsonp(["app/js/classroom-manage/set-price/index"],{"1ccda3b6c64440a52b47":function(c,a){var r=$("#classroom-set-form"),e=r.validate({currentDom:"#classroom-save",rules:{price:{required:!0,currency:!0}}});$("#classroom-save").click(function(){e.form()}),$("#price").on("input",function(){var c=$("#price").val(),a=$("#coinPrice").data("rate"),r=$("#coinPrice").data("name");$("#coinPrice").text(Translator.trans("相当于")+c*a+r)})}},["1ccda3b6c64440a52b47"]);