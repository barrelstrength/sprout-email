$(document).ready( function() {
	SproutEmail.init();
});

var SproutEmail = {

	init: function()
	{
		$('#notificationEvent').change(function(){
			SproutEmail.selectNotificationEvent();
		});

		$('#mailer').change(function(){
			SproutEmail.onCampaignMailerSelect();
		});

		SproutEmail.selectNotificationEvent();
		SproutEmail.onCampaignMailerSelect();
	},

	selectNotificationEvent: function()
	{
		$('.event-options').hide();
		//alert($('#notificationEvent').val());
		if($('#notificationEvent').val() != "")
		{
			var eventVal = $('#notificationEvent').val();

			$('.' + eventVal).show();
		}
	},

	/**
	 * Event handler for mailer selection on campaign settings
	 */
	onCampaignMailerSelect: function()
	{

	}
}
