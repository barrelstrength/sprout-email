$(document).on('sproutModalBeforeRender', function(event, content) {
	$('.btnSelectAll', content).off().on('click', function(event) {

		event.preventDefault();

		$this    = $(event.target);
		$target  = '#' + $this.data('clipboard-target-id');
		$message = $this.data('success-message');

		$content = $($target).select();

		// Copy our selected text to the clipboard
		document.execCommand("copy");

		Craft.cp.displayNotice($message);
	});

});