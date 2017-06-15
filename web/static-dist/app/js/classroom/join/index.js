webpackJsonp(["app/js/classroom/join/index"],{"6a20cd61187c3c5ca840":function(e,t,n){"use strict";function a(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}Object.defineProperty(t,"__esModule",{value:!0});var s=function(){function e(e,t){for(var n=0;n<t.length;n++){var a=t[n];a.enumerable=a.enumerable||!1,a.configurable=!0,"value"in a&&(a.writable=!0),Object.defineProperty(e,a.key,a)}}return function(t,n,a){return n&&e(t.prototype,n),a&&e(t,a),t}}(),i=function(){function e(t){a(this,e),this.$element=t,this.selectedDate=null,this.inited=!1,this.daysInMonth=[31,28,31,30,31,30,31,31,30,31,30,31],this.signedRecordsUrl=null,this.signUrl=null,this.initEvent(),this.setup()}return s(e,[{key:"initEvent",value:function(){var e=this;this.$element.on("click","[data-role=sign]",function(){return e.sign()}),this.$element.on("mouseenter",'[data-role="signed"]',function(){return e.signedIn()}),this.$element.on("mouseleave",'[data-role="signed"]',function(){return e.signedOut(event)}),this.$element.on("mouseenter",".sign_main",function(){return e.keep()}),this.$element.on("mouseleave",".sign_main",function(){return e.remove()}),this.$element.on("click","[data-role=previous]",function(){return e.previousMonth()}),this.$element.on("click","[data-role=next]",function(){return e.nextMonth()})}},{key:"setup",value:function(){this.selectedDate=this.$element.find("#title-month").data("time");var e=this.$element.data("records"),t=this.$element.data("signurl");this.signedRecordsUrl=e,this.signUrl=t}},{key:"keep",value:function(){this.$element.find(".sign_main").addClass("keepShow")}},{key:"remove",value:function(){this.$element.find(".sign_main").removeClass("keepShow"),this.hiddenSignTable()}},{key:"getDaysInMonth",value:function(e,t){return 1!=e||t%4!=0||t%100==0&&t%400!=0?this.daysInMonth[e]:29}},{key:"getWeekByDate",value:function(e,t,n){return new Date(e+"/"+t+"/"+n).getDay()}},{key:"sign",value:function(){var e=this,t=(new Date).getDate();$.ajax({url:this.signUrl,dataType:"json",success:function(n){$("#sign").html('<div  class="sign-area" data-role="signed" onclick="return false;" ><a class="btn-signin after" >'+Translator.trans("已签到")+"<br>"+Translator.trans("连续")+n.keepDays+Translator.trans("天")+"</a></div>"),e.showSignTable(),e.initTable(!0),e.$element.find(".d-"+t).addClass("signed_anime_day")},error:function(e){}})}},{key:"signedIn",value:function(){this.inited||this.initTable(),this.showSignTable()}},{key:"signedOut",value:function(e){var t=this;this.$element.find(".sign_main").removeClass("keepShow"),setTimeout(function(){t.$element.find(".sign_main").hasClass("keepShow")||t.hiddenSignTable()},1e3)}},{key:"showSignTable",value:function(){this.$element.find(".sign_main").addClass("keepShow"),this.$element.find(".sign_main").attr("style","display:block")}},{key:"hiddenSignTable",value:function(){this.$element.find(".sign_main").removeClass("keepShow"),this.$element.find(".sign_main").attr("style","display:none")}},{key:"initTable",value:function(e){var t=this,n=this.selectedDate;n=n.split("/");var a=parseInt(n[0]),s=parseInt(n[1]),i=this.getDaysInMonth(s-1,a),r=this.$element.find("tbody"),l="<tr><td class='t-1-0 '></td><td class='t-1-1 '></td><td class='t-1-2 '></td><td class='t-1-3 '></td><td class='t-1-4 '></td><td class='t-1-5 '></td><td class='t-1-6 '></td></tr>",o=this.signedRecordsUrl+"?startDay="+a+"-"+s+"-1&endDay="+a+"-"+s+"-"+i;r.append(l);for(var d=1,c=((new Date).getDate(),1);c<=i;c++){var u=this.getWeekByDate(a,s,c);r.find(".t-"+d+"-"+u).html(c),r.find(".t-"+d+"-"+u).addClass("d-"+c),6==u&&c!=i&&(d++,l='<tr><td class="day t-'+d+'-0 "></td><td class="day t-'+d+'-1 "></td><td class="day t-'+d+'-2 "></td><td class="day t-'+d+'-3 "></td><td class="day t-'+d+'-4 "></td><td class="day t-'+d+'-5 "></td><td class="day t-'+d+'-6 "></td></tr>',r.append(l))}if($.ajax({url:o,dataType:"json",async:!0,success:function(e){for(var n=0;n<e.records.length;n++){var a=parseInt(e.records[n].day);r.find(".d-"+a).addClass("signed_day").attr("title",Translator.trans("于")+e.records[n].time+Translator.trans("签到,第")+e.records[n].rank+Translator.trans("个签到."))}t.$element.find(".today-rank").html(e.todayRank),t.$element.find(".signed-number").html(e.signedNum),t.$element.find(".keep-days").html(e.keepDays)}}),this.inited=!0,e){var h=this.$element.find("[data-role=sign]");h.data("role","signed"),h.on("mouseenter",function(){this.signedIn()}),h.on("mouseleave",function(){this.signedOut()}),h.on("click",!1),h.addClass("sign-btn"),h.find(".sign-text").html(Translator.trans("已签"))}}},{key:"previousMonth",value:function(){var e=this.selectedDate;e=e.split("/");var t=parseInt(e[0]),n=parseInt(e[1]),a=0,s=t;1==n?(a=12,s=t-1):a=n-1,a=a<10?"0"+a:a,this.selectedDate=s+"/"+a,this.$element.find("tbody").html(""),this.$element.find("[data-role=next]").removeClass("disabled-next"),this.$element.find("#title-month").html(s+Translator.trans("年")+a+Translator.trans("月")),this.initTable()}},{key:"nextMonth",value:function(){var e=this.selectedDate;e=e.split("/");var t=parseInt(e[0]),n=parseInt(e[1]),a=0,s=t;n==(new Date).getMonth()+1&&t==(new Date).getFullYear()||(12==n?(a=1,s=t+1):a=n+1,a==(new Date).getMonth()+1&&t==(new Date).getFullYear()&&this.$element.find("[data-role=next]").addClass("disabled-next"),a=a<10?"0"+a:a,this.selectedDate=s+"/"+a,this.$element.find("tbody").html(""),this.$element.find("#title-month").html(s+Translator.trans("年")+a+Translator.trans("月")),this.initTable())}}]),e}();t.default=i},bbe1f1e10924ccc8bdb1:function(e,t,n){"use strict";$("body").on("click",".es-qrcode",function(){var e=$(this);e.hasClass("open")?e.removeClass("open"):$.ajax({type:"post",url:e.data("url"),dataType:"json",success:function(t){e.find(".qrcode-popover img").attr("src",t.img),e.addClass("open")}})})},e4b343a75505a8b3c1dd:function(e,t,n){"use strict";var a=n("6a20cd61187c3c5ca840"),s=function(e){return e&&e.__esModule?e:{default:e}}(a);n("bbe1f1e10924ccc8bdb1");var i=!1;if($(".buy-btn").click(function(){return i||($(".buy-btn").addClass("disabled"),i=!0),!0}),$(".cancel-refund").on("click",function(){if(!confirm(Translator.trans("真的要取消退款吗？")))return!1;$.post($(this).data("url"),function(){window.location.reload()})}),$("#quit").on("click",function(){if(!confirm(Translator.trans("确定退出班级吗？")))return!1;$.post($(this).data("url"),function(){window.location.reload()})}),$("#classroom-sign").length>0){new s.default($("#classroom-sign"))}$(".icon-vip").length>0&&$(".icon-vip").popover({trigger:"manual",placement:"auto top",html:"true",container:"body",animation:!1}).on("mouseenter",function(){$(this).popover("show")}).on("mouseleave",function(){var e=$(this);setTimeout(function(){$(".popover:hover").length||e.popover("hide")},100)})}},["e4b343a75505a8b3c1dd"]);