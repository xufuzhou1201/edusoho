webpackJsonp(["app/js/classroom/detail/teacher-list/index"],{"7840d638cc48059df0fc":function(t,e,c){"use strict";$("body").on("click",".teacher-item .follow-btn",function(){var t=$(this);$.post(t.data("url"),function(){1===t.data("loggedin")&&(t.hide(),t.closest(".teacher-item").find(".unfollow-btn").show())})}).on("click",".unfollow-btn",function(){var t=$(this);$.post(t.data("url"),function(){}).always(function(){t.hide(),t.closest(".teacher-item").find(".follow-btn").show()})})},"90e9ad253c46311b63e8":function(t,e,c){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var o=c("7840d638cc48059df0fc");c.n(o)}},["90e9ad253c46311b63e8"]);