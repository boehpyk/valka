  function makeServiceName(src, trgt)
  {
      var _trgt = document.getElementById(trgt);
      _trgt.value = rusToTrans(src.value);
  }

 
  function rusToTrans(str)
  {
    var rus = new Array('щ','Щ','ш','Ш','ё','Ё','ж','Ж','ч','Ч','э','Э','ю','Ю','я','Я','а','б','в','г','д','е','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ъ','ы','ь','А','Б','В','Г','Д','Е','З','И','Й','К','Л','М','Н','О','П','Р','С','Т','У','Ф','Х','Ц','Ъ','Ы','Ь',' ');
    var trans = new Array('sch','SCH','sh','SH','yo','YO','zh','ZH','ch','CH','e','E','yu','YU','ya','YA','a','b','v','g','d','e','z','i','j','k','l','m','n','o','p','r','s','t','u','f','h','c',"\"",'y',"",'A','B','V','G','D','E','Z','I','J','K','L','M','N','O','P','R','S','T','U','F','H','C',"\"",'Y',"","_");
    for(i=0;i<rus.length;i++){
       y=eval('/'+rus[i]+'/ig')
       str=str.replace(y,trans[i])
    }
    str=str.replace(/[^a-zA-Z0-9_\-]/gi,'');
    return str;
  }
  
    jQuery(function($) {
        var panelList = $('#draggablePanelList');
        var type = $('#draggablePanelList').data('type');
        panelList.sortable({
            // Only make the .panel-heading child elements support dragging.
            // Omit this to make the entire <li>...</li> draggable.
            handle: '.panel-heading',
            update: function() {
                var res_arr = new Array();
                $('.panel', panelList).each(function(index, elem) {
                     var $listItem = $(elem),
                         newIndex = $listItem.index();
                         res_arr.push($listItem.attr("id"));
                });
                $('#sortdata').val(res_arr.join(","));
                // console.log($('#sortdata').val());
                // $.ajax({
                //     data: 'data=' + res_arr.join(",") + '&type=' + type,
                //     type: 'POST',
                //     url: '/admin/ajax/save_order/'
                // });
            }
        });
        
        var panelList2 = $('#draggablePanelList2');
        var type2 = $('#draggablePanelList2').data('type');
        panelList2.sortable({
            // Only make the .panel-heading child elements support dragging.
            // Omit this to make the entire <li>...</li> draggable.
            handle: '.panel-heading', 
            update: function() {
                var res_arr2 = new Array();
                $('.panel', panelList2).each(function(index, elem) {
                    var $listItem2 = $(elem),
                    newIndex = $listItem2.index();
                         res_arr2.push($listItem2.attr("id"));
                });
                $('#sortdata').val(res_arr2.join(","));
                // $.ajax({
                //     data: 'data=' + res_arr2.join(",") + '&type=' + type2,
                //     type: 'POST',
                //     url: '/admin/ajax/save_order/'
                // });
            }
        });
        
    });  
    
  function ConfirmDelete() {
    var delete_exists = false;

    $('.subdep-manage').each(function( index ) {
      if ($(this).data("role") == 'delete' && $(this).prop('checked')) {
          delete_exists = true;
      }
    });

    if (delete_exists) {
        return window.confirm("Вы уверены что хотите удалить материалы?");
    }
    return true;
  };

  function confirmDeleteSimple() {
        return window.confirm("Вы уверены что хотите удалить материалы?");
  }
    

$().ready(function() {
    
    $("a.thickbox").fancybox({
    	'titlePosition'	: 'inside'
    });    
    
    $('#submit_btn').click(function(){
        $('#mainForm').submit();
    	// Preventing the default action triggered by clicking on the link
    	//e.preventDefault();
    });
    $('#submit_btn2').click(function(){
        $('#mainForm').submit();
    	// Preventing the default action triggered by clicking on the link
    	//e.preventDefault();
    });
    
    
    
    $('button.expand-btn').click(function() {
        $(this).text(function(i,old){
            return old=='+' ?  '-' : '+';
        });
    });    
    
    $('.toggle-block').click(function() {
        var trgt = $(this).attr("data-params");
        var old_class = $("#" + trgt + "Caret").attr("class");
        $("#" + trgt + "Caret").removeClass(old_class);
        if($("#"+trgt).attr("class") == 'panel-body collapse') {
            $("#" + trgt + "Caret").addClass('caret');
        }
        else {
            $("#" + trgt + "Caret").addClass('caret-right');
        }
    });  
    
    $('.select-all').click(function() {
        var trgt = $(this).data("target");
        var state = $(this).prop("checked");
        $('.subdep-manage').each(function( index ) {
          if ($(this).data("role") == trgt) {
              $(this).prop("checked", state);
          }
        });                
        
    });      
    
    
});