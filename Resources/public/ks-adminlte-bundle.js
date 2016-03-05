function show_error(msg)
{
	var flash_msg = $('<div class="alert alert-danger alert-dismissible fade in" role="alert" style="margin-top:20px;margin-bottom:0px;"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>'+msg+'</div>');
	$('#flashbag').append(flash_msg);
}