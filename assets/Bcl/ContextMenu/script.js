BclContextMenu = 
{
    
    selectedItem : null,
    init : function()
    {
        $("body").on("contextmenu", ".BclContextMenuOrigin", function(e) {           
            BclContextMenu.selectedItem = $(this);
            let contextMenu = $('#' +  $(this).data('bclcontextmenuid'));            
            contextMenu.css({
                display: "block",
                left: e.pageX,
                top: e.pageY
            });
            let param = $(this).data('action-param') ? $(this).data('action-param') : '';
            contextMenu.data('action-param',param);            
            return false;
        });        
        $('.BclContextMenu').on("click", "a", function() {
            Osynapsy.action.remoteExecute($(this).data('action'));
            $(this).closest('.BclContextMenu').hide();
        });        
    }
}

if (window.FormController){    
    FormController.register('init','BclContextMenu_Init',function(){
        BclContextMenu.init();
    });
}
