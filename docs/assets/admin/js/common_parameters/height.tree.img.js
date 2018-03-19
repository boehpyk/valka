function readPreviewURL(input) {

    if (input.files && input.files[0]) {
        var reader = new FileReader();


        reader.onload = function (e) {
        	console.log(123);
            $('#h-preview').attr('src', e.target.result);
        	$('.wrapper-img').show();
        }
        reader.readAsDataURL(input.files[0]);
    }
}

$(function(){
	var preview_src = $('#h-preview').attr('src');
	if (preview_src != undefined && preview_src == '') {
		$('.wrapper-img').hide();
	}
	$(document).on('change', '#h-fileinput',function(){
        	console.log(2222);
		readPreviewURL(this);
	});

	$(document).on('click', '.close-img', function(e) {
		e.preventDefault();
		if (confirm('Вы действительно хотите удалить это изображение?')) {
			$('.wrapper-img').hide();
			$('#h-preview').attr('src', '');
			$('#h-fileinput').val('');
			$.get('/admin/catalog/delete.height.tree.img.php?action=delete&id=' + $(this).attr('data-id'));
		}
	});
});
