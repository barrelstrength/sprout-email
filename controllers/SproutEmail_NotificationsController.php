<?php
namespace Craft;

/**
 * Notifications controller
 *
 */
class SproutEmail_NotificationsController extends SproutEmail_CampaignsController
{
	/**
	 * Save campaign
	 * 
	 * @return void
	 */
	public function actionSave()
	{		
		// first save the campaign as normally would be done
	    $campaignModel = parent::actionSave();

		if($campaignModel->getErrors())
		{
		    // Send the field back to the template
		    craft()->urlManager->setRouteVariables(array(
		        'campaign' => $campaignModel
		    ));
		}
		else 
		{
    		// convert notificationEvent to id
    		if(isset($_POST['notificationEvent']) && ! is_numeric($_POST['notificationEvent']))
    		{
    			$criteria = new \CDbCriteria();
    			$criteria->condition = 'event=:event';
    			$criteria->params = array(':event' => str_replace('---', '.', $_POST['notificationEvent']));
    			 
    			if($res =  SproutEmail_NotificationEventRecord::model()->find($criteria))
    			{
    				$_POST['notificationEvent'] = (int) $res->id;
    			}
  			}
  			
  			if(isset($_POST['notificationEvent']))
  			{

  			    // since this is a notification, we'll make an event/campaign association...
  			    craft()->sproutEmail_notifications->associateCampaign($campaignModel->id, $_POST['notificationEvent']);
  			    
  			    // ... and set notification options
  			    craft()->sproutEmail_notifications->setCampaignNotificationEventOptions($campaignModel->id, $_POST);
  			}

    		craft()->userSession->setNotice(Craft::t('Notification successfully saved.'));
    		
    		switch (craft()->request->getPost('continue'))
    		{
    		    case 'info':
    		        $this->redirect('sproutemail/notifications/edit/' . $campaignModel->id . '/template');
    		        break;
    		    case 'template':
    		        $this->redirect('sproutemail/notifications/edit/' . $campaignModel->id . '/recipients');
    		        break;
    		    default:
    		        $this->redirectToPostedUrl(array($campaignModel));
    		        break;
    		}
		}
	}
}