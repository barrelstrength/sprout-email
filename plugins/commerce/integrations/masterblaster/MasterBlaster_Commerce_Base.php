<?php   
// MUST be in the Craft namespace
namespace Craft;  

class MasterBlaster_Commerce_Base
{	
	/**
	 * Display custom Commerce options
	 * @return string Returns the template which displays our settings
	 */
	public function getOptionsHtml()
	{
		// return craft()->templates->render('commerce/_masterblaster/settings', array('settings'=> $settings));
		return '<hr>
				<h3>Custom options for Commerce.</h3>
				
				{% if campaign.notificationEvents[0] is defined %}
					{% set opts = campaign.notificationEvents[0].options %}
				{% endif %}
	
				{{ forms.textField({
								label: "Tester Field"|t,
								id: "tester",
								name: "options[tester]",
								instructions: "This is a test option"|t,
								value: (opts.tester is defined ? opts.tester : null),
								autofocus: true,
				}) }}
				<hr>';
	}
}