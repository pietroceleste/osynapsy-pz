BclPager = {
    pendentCommand : '',
    init : function() {
        $('.BclPager').each(function() {
            if ($(this).hasClass('infinitescroll')) {
                $(document).imagesLoaded(function() {
                    BclPager.infiniteScrollInit($('.infinitescroll'));
                });
            }
            $('body').on('click','.BclPager a',function(e){
                var parent = $(this).closest('.BclPager');
                e.preventDefault();
                $('input[type=hidden]', parent).val($(this).data('value'));
                if ($(parent).hasClass('refreshParent')) {
                    Osynapsy.refreshComponents([$(parent).data('parent')]);
                    return;
                }
                if ($(parent).attr('action') === 'refreshComponent' || $(parent).hasClass('refreshParent')) {
                    Osynapsy.refreshComponents([$(parent).data('target')]);
                    return;
                }                
                $(this).closest('form').submit();
            });
        });
    },
    infiniteScrollInit : function(obj) {
        var oid = $(obj).attr('id');
        var cnt = $(obj).data('container');
        $(cnt).wookmark({offset : 0,
            resizeDelay: 250,
            outerOffset : -10,
            align : 'center',
            itemWidth : 0,
            autoResize: true
        });
        $(obj).hide();
        $(window).scroll($.debounce( 50, function(){
            BclPager.infiniteScroll('#'+oid);
        }));
    },
    infiniteScroll : function(oid) {
        var hdn = $('input[type=hidden]',$(oid));
        console.log($(oid).attr('id'));
        console.log('startPage : ' + hdn.val());
        var cnt = $($(oid).data('container'));
        if (cnt.hasClass('infinite-loading-pending')) {
            return;
        }
        var scrollPercent = 100 * $(window).scrollTop() / ($(document).height() - $(window).height());
        if (scrollPercent < 99 && BclPager.pendentCommand == '') {
            return;
        }
        var curPage = hdn.val() ? parseInt(hdn.val()) : 1;
        //var curPage = hdn.val();
        console.log('curPage : ' + curPage);
        var nxtPage = curPage + 1;
        console.log('nxtPage : ' + nxtPage);
        if (nxtPage > parseInt($(oid).data('page-max'))) {
            console.log('max page arrived');
            return;
        }
        hdn.val(nxtPage);
        console.log('nextCurPage : ' + hdn.val() +"\n\n");
        var dat = $(oid).closest('form').serialize();
        $.ajax({
            type : 'post',
            data : dat,
            dataType : 'html',
            context : cnt,
            success : function(resp) {
                //console.log(resp);
                switch (BclPager.pendentCommand) {
                    case 'clear':
                        $('.item-isotope').remove();
                        BclPager.pendentCommand = '';
                        var pid = '#'+$('.BclPager').attr('id');
                        var max = $(pid,resp).data('page-max');
                        $(pid).data('page-max',max);
                        break;
                }
                var containerId = '#' + $(this).attr('id');
                $items = $(containerId, resp).html();
                $(this).append($items);
                if (!$(this).hasClass('index-isotope')) {
                    return;
                }
                $('.infinite-loading-pending').imagesLoaded(function(){
                    $('.infinite-loading-pending').wookmark( {offset : 0, resizeDelay: 0, outerOffset : -10, align : 'center', itemWidth : 0, autoResize: true});
                    $('.infinite-loading-pending').removeClass('infinite-loading-pending');
                });
            },
            beforeSend: function(){
                $(this).addClass('infinite-loading-pending');
            },
            error : function(){
                $(this).removeClass('infinite-loading-pending');
            }
        });
    },
    reloadInfinite : function(oid)
    {
        $('input[type=hidden]',$(oid)).val(0);
        BclPager.pendentCommand = 'clear';
        BclPager.infiniteScroll($(oid));
    }
}

if (window.FormController) {
    FormController.register('init','BclPager.init',function(){
        BclPager.init();
    });
}