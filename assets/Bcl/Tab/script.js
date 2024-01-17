BclTab = {
    init : function()
    {        
        $('.nav-tabs').each(function() {            
           let tabSelected = $('input[type=hidden]', this).val();           
           let aSelected = (tabSelected !== '') ? 'a[href="'+tabSelected+'"]' : 'a:first';           
           $(aSelected, this).tab('show');           
           $('a', this).click(function() {
               let tabId = $(this).attr('href');
               let hdnId = $(this).closest('ul').attr('id').replace('_nav','');
               $('#'+hdnId).val(tabId);
           });
        });
    }
};

if (window.FormController){
    FormController.register('init','BclTab_Init',function(){
        BclTab.init();
    });
}