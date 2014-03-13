jQuery(document).ready(function(){
	mb.init();
});

var mb = {
	init: function(){
		jQuery('select[name=notificationEvent]').change(function(){
			mb.select_notification_event();
		});
		jQuery('#tab-email-template').find('input[type=radio]').change(function(){
			mb.select_email_tpl();
		});
		jQuery('.save-and-continue').click(function(){
			jQuery(this).closest('form').find('input[name=continue]').val(jQuery(this).attr('id'));
			jQuery(this).closest('form').submit();
		});
		mb.select_notification_event();
	},
	select_email_tpl: function(){
		jQuery('.tpl_options').hide();

		switch(jQuery('input[name="templateOption"]:checked').val())
		{
			case '1':
				jQuery('#tpl_html, #tpl_text').show();
				break;
			case '2':
				jQuery('#tpl_text').show();
				break;
			case '3':
				jQuery('#tpl_section').show();
				break;
			default:
			break;
		}
	},
	select_notification_event: function(){
		jQuery('.event_options').hide();
		var event = jQuery('select[name=notificationEvent]').val();
		jQuery('.'+event).show();
	}
}