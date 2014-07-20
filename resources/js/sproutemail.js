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
		jQuery('input[name=useRecipientLists]').change(function(){
			mb.toggle_recipient_list();
		});
		jQuery('select[name=emailProvider]').change(function(){
			mb.select_email_provider();
		});
		mb.select_notification_event();
		mb.set_recipient_btns();
		mb.toggle_recipient_list();
		mb.select_email_provider();
	},
	toggle_recipient_list: function(){
		var check_box = jQuery('input[name=useRecipientLists]');
		if(check_box.is(':checked')){
			check_box.closest('div').find('.field').show();
		}else{
			check_box.closest('div').find('input[type=checkbox]').attr('checked', false);
			check_box.closest('div').find('.field').hide();
		}
	},
	set_recipient_btns: function(){
		if(jQuery('.recipients-not-defined').length == 0){
			jQuery('#recipient-btns').removeClass('hidden');
		}
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
	},
	select_email_provider: function(){
		var selected = jQuery('select[name=emailProvider]').val();
		jQuery('.generalsettings').hide();
		jQuery('.generalsettings').find('input, select').attr('disabled', true);
		jQuery('#' + selected + '-generalsettings-template').show();
		jQuery('#' + selected + '-generalsettings-template').find('input, select').attr('disabled', false);
	}
}