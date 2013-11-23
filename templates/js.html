{% set js %}

jQuery(document).ready(function(){
	mb.init();
	jQuery('#tab-email-template').find('input[type=radio]').change(function(){
		mb.select_email_tpl();
	});	
	jQuery('#emailProvider').change(function(){
		mb.select_email_provider();
	});
	jQuery('input[name=recipientOption]').change(function(){
		mb.select_recipients();
	});	
	jQuery('select[name=notificationEvent]').change(function(){
		mb.select_notification_event();
	});
});

var mb = {
	init: function(){
		mb.select_email_tpl();
		mb.select_email_provider();
		mb.select_recipients();
		mb.check_errors();
		mb.select_notification_event();
	},
	select_email_provider: function(){
		jQuery('.recipient_templates').find('input').attr('disabled',true);
		jQuery('.recipient_templates').hide();
		jQuery('#'+jQuery('#emailProvider').val()+'_recipients_template').find('input').attr('disabled',false);
		jQuery('#'+jQuery('#emailProvider').val()+'_recipients_template').show();
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
	select_recipients: function(){
		jQuery('.recipient_options').hide();

		switch(jQuery('input[name="recipientOption"]:checked').val())
		{
			case '1':
				jQuery('#recipients_from_list').show();
				break;
			case '2':
				jQuery('#create_recipients').show();
				break;
			default:
			break;
		}
	},
	check_errors: function(){ // tag tabs containing errors
		var err=false;
		jQuery('.tab-content').each(function(){
			if(jQuery(this).find('.errors').length>0){
				jQuery('.'+jQuery(this).attr('id')).text('* '+jQuery('.'+jQuery(this).attr('id')).text()).css({'color':'#da5a47'});
			}
		});
	},
	select_notification_event: function(){
		jQuery('.event_options').hide();
		var event = jQuery('select[name=notificationEvent]').val();
		jQuery('.'+event).show();
	}
}
	
{% endset %}
{% includeJs js %}