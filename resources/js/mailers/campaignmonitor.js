$(document).off().on('sproutModalBeforeRender', function (event, content) {
	$('.send-campaign', content).off().on('click', function(e) {

		var confirmed = !!confirm('Are you sure you want to send this campaign to all recipients?');

		if (confirmed !== true) {
			e.preventDefault();

			$(e.target).addClass('preventAction');
		}
	});
});
