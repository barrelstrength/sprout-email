<?php   
// MUST be in the Craft namespace
namespace Craft;  

class MasterBlaster_Commerce_OnCheckoutEnd extends MasterBlaster_Commerce_Base
{	
	/**
	 * This is the hook MasterBlaster will call to register this event with Commerce
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
		return 'checkoutEnd';
	}
	
	/**
	 * Event name
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Commerce: On Checkout End');
	}
	
	/**
	 * Event description
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return Craft::t('This event will fire after a user checks out and a status message is received back from the gateway. The event will pass the Order Model to Master Blaster');
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