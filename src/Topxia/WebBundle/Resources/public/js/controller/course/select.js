define(function(require, exports, module) {

    var Widget = require('widget');

    exports.run = function() {

        var ids=[];
        $('#sure').on('click',function(){

            $('#sure').button('submiting').addClass('disabled');

            $.ajax({
                type : "post",
                url : $('#sure').data('url'),
                data : "ids="+ids,
                async : false,
                success : function(data){
                    
                    $('.modal').modal('hide');
                    window.location.reload();
                }

             });

        });

        $('.course-wide-list').on('click',".course-item ",function(){

            var id=$(this).data('id');

            if($(this).hasClass('select')){

                $(this).removeClass('select');
                $('.course-metas-'+id).hide();

                ids = $.grep(ids, function(val, key) {

                    if(val != id )
                        return true;
                }, false);

            }else{
                $(this).addClass('select');
                
                $('.course-metas-'+id).show();

                ids.push(id);

            }

        });


    };

    
});