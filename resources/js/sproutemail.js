$(document).ready( function() {
	SproutEmail.init();
});

var SproutEmail = {

	init: function()
	{
		$('#notificationEvent').change(function(){
			SproutEmail.selectNotificationEvent();
		});

		$('.save-and-continue').click(function(){
			$(this).closest('form').find('input[name=continue]').val($(this).attr('id'));
			$(this).closest('form').submit();
		});

		$('input[name=useRecipientLists]').change(function(){
			SproutEmail.toggleRecipientList();
		});

		$('#mailer').change(function(){
			SproutEmail.onCampaignMailerSelect();
		});

		SproutEmail.selectNotificationEvent();
		SproutEmail.setRecipientButtons();
		SproutEmail.toggleRecipientList();
		SproutEmail.onCampaignMailerSelect();
	},

	toggleRecipientList: function()
	{
		var checkBox = $('input[name=useRecipientLists]');

		if (checkBox.is(':checked'))
		{
			checkBox.closest('div').find('.field').show();
		}
		else
		{
			checkBox.closest('div').find('input[type=checkbox]').attr('checked', false);
			checkBox.closest('div').find('.field').hide();
		}
	},

	setRecipientButtons: function()
	{
		if ($('.recipients-not-defined').length == 0)
		{
			$('#recipient-btns').removeClass('hidden');
		}
	},

	selectNotificationEvent: function()
	{
		$('.event-options').hide();
		//alert($('#notificationEvent').val());
		if($('#notificationEvent').val() != "")
		{
			var eventVal = $('#notificationEvent').val();
					eventVal = eventVal.replace(":", "x-x");

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
