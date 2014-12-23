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

		$('#emailProvider').change(function(){
			SproutEmail.selectEmailProvider();
		});

		SproutEmail.selectNotificationEvent();
		SproutEmail.setRecipientButtons();
		SproutEmail.toggleRecipientList();
		SproutEmail.selectEmailProvider();
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
		$('.event-options-block').hide();
		$('.' + $('#notificationEvent').val()).show();
	},

	selectEmailProvider: function()
	{
		var selected = $('#emailProvider').val();
		$('.generalsettings').hide();
		$('.generalsettings').find('input, select').attr('disabled', true);
		$('#' + selected + '-generalsettings-template').show();
		$('#' + selected + '-generalsettings-template').find('input, select').attr('disabled', false);
	}
}
