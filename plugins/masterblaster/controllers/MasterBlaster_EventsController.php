<?php
namespace Craft;

/**
 * Notification events controller
 *
 */
class MasterBlaster_EventsController extends BaseController
{
	/**
	 * Save event
	 * 
	 * @return void
	 */
	public function actionSave()
	{			
		$this->requirePostRequest();
		
		// mass assignment to form model		
		$event_model = MasterBlaster_NotificationEventModel::populateModel(craft()->request->getPost());

		if($res = craft()->masterBlaster->saveEvent($event_model))
		{
			if($res->hasErrors())
			{
				craft()->userSession->setError(Craft::t('Couldn’t save form.'));
				
				// Send the field back to the template
				craft()->urlManager->setRouteVariables(array(
					'event' => $event_model
				));
				return true;
			}
				
			craft()->userSession->setNotice(Craft::t('Event saved.'));			
			$this->redirectToPostedUrl(array($event_model));
		}
		else  // problem
		{
			craft()->userSession->setError(Craft::t('Couldn’t save form.'));
		}
		
		// Send the field back to the template
		craft()->urlManager->setRouteVariables(array(
			'event' => $event_model
		));
	}
	
	/**
	 * Deletes an event
	 *
	 * @return void
	 */
	public function actionDelete()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();
			
		$this->returnJson(array(
				'success' => craft()->masterBlaster->deleteEvent(craft()->request->getRequiredPost('id'))));
	}
}