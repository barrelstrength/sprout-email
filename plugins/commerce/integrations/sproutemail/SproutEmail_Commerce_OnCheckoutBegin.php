<?php   
// MUST be in the Craft namespace
namespace Craft;  

class SproutEmail_Commerce_OnCheckoutBegin extends SproutEmail_Commerce_Base
{	
	/**
	 * This is the hook SproutEmail will call to register this event with Commerce
	 *
	 * @return string
	 */
	public function getHook()
	{
		return 'commerceAddEventListener';
	}
	
	/**
	 * Event name
	 *
	 * @return string
	 */
	public function getEvent()
	{
		return 'checkoutBegin';
	}
	
	/**
	 * Event name
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Commerce: On Checkout Begin');
	}
	
	/**
	 * Event description
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return Craft::t('This event will fire before a user checks out. The event will pass the Order Model to Sprout Email');
	}
	
	/**
	 * Display custom Commerce options
	 * @return string Returns the template which displays our settings
	 */
	public function getOptionsHtml()
	{
		//return craft()->templates->render('commerce/_sproutemail/settings', array('settings'=> $settings));
		return '<hr>
				<h3>Custom options for Commerce.</h3>
	
				{% if campaign.notificationEvents[0] is defined %}
					{% set opts = campaign.notificationEvents[0].options %}
				{% endif %}
	
				{{ forms.textField({
								label: "Tester Field"|t,
								id: "tester begin",
								name: "options[tester2]",
								instructions: "This is a test begin option"|t,
								value: (opts.tester2 is defined ? opts.tester2 : null),
								autofocus: true,
				}) }}
				<hr>';
	}
	
	/**
	 * Process the options associated with the event
	 *
	 * @return string
	 */
	public function processOptions($event, $entity, $options)
	{

		return true;
	
	}
}