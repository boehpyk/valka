function readPreviewURL(input) {

    if (input.files && input.files[0]) {
        var reader = new FileReader();

        reader.onload = function (e) {
            $('#c-preview').attr('src', e.target.result);
        	$('#c-preview').show();
        }

        reader.readAsDataURL(input.files[0]);
    }
}

$(function(){
	var preview_src = $('#c-preview').attr('src');
	if (preview_src != undefined && preview_src == '') {
		$('#c-preview').hide();
	}
	$(document).on('click', '.common-parameters-delete', function(e){
		e.preventDefault();
		if (confirm('Вы действительно хотите удалить этот параметер?')) {
			// console.log('Параметер удален');
			window.location = '/admin/common_parameters/?action=delete&id=' + $(this).attr('data-id');
		} else {
			// console.log('Удаление отменено');
		}
	});
	$(document).on('click', '.common-parameters-update', function(e) {
		e.preventDefault();
		var href = '/admin/common_parameters/?action=update&id=' + $(this).attr('data-id');
		window.location = href;
	});
	$( "#common-parameters-table tbody" ).sortable({
		helper: function(e, tr)
		  {
		    var $originals = tr.children();
		    var $helper = tr.clone();
		    $helper.children().each(function(index)
		    {
		      // Set helper cell sizes to match the original sizes
		      $(this).width($originals.eq(index).width());
		    });
		    return $helper;
		  },
	});
	$( "#common-parameters-table tbody" ).disableSelection();
	$( "#common-parameters-table tbody" ).on( "sortupdate", function( event, ui ) {
		var selid = ui.item.attr('data-id');
		// var sorted = $( "#common-parameters-table tbody" ).sortable( "serialize", { key: "params-sort[]", attribute: "data-id" } );
        var sorted = $( "#common-parameters-table tbody" ).sortable( "toArray", {attribute: "data-id" } );
		console.log(sorted);
		$.get('/admin/ajax/save_order/?type=catalog_fields&data=' + sorted.join(','));
		// console.log(ui.placeholder.html);
	} );
	$(document).on('change', '#c-fileinput',function(){
		readPreviewURL(this);
	});
});
