BclPager = {    
    init : function() {
        $('.BclPager').each(function() {  
            $('body').on('click', '.BclPager a', function(e) {
                e.preventDefault();
                let parent = $(this).closest('.BclPager');                
                $('input#'+parent.attr('id'), parent).val($(this).data('value'));
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
    }
};

if (window.FormController) {
    FormController.register('init','BclPager.init',function(){
        BclPager.init();
    });
}