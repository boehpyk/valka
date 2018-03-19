$(function()
{
	$( ".cite" ).each(function( index ) {
		var maxLength = $(this).attr('maxlength');
		var id = $(this).attr('rel');
	    $(this).keyup(function()
		//$(this).change(function()
	    {
	        var curLength = $(this).val().length;         //(2)
	        $(this).val($(this).val().substr(0, maxLength));     //(3)
	        var remaning = maxLength - curLength;
	        if (remaning < 0) remaning = 0;
	        $('#textareaFeedback_' + id).html('Осталось символов: ' + remaning); //(4)
	        if (remaning < 10)                                           //(5)
	        {
	            $('#textareaFeedback_' + id).addClass('warning')
	        }
	        else
	        {
	            $('#textareaFeedback_' + id).removeClass('warning')
	        }
	    });
	});
})