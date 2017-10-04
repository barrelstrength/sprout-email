$(document).on('sproutModalBeforeRender', function(event, content)
{
	$('.btnSelectAll', content).off().on('click', function(e) {
		e.preventDefault();

		var $this = $(e.target);

		$($this.data('subject')).select();
	});
});
