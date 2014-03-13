<?php
namespace Craft;

/**
 * Campaigns controller
 *
 */
class SproutEmail_CampaignsController extends BaseController
{
	/**
	 * Export campaign
	 * 
	 * @return void
	 */
	public function actionExport()
	{
		craft()->sproutEmail_emailProvider->exportCampaign(
			craft()->request->getPost('entryId'),craft()->request->getPost('campaignId'));
	}

	/**
	 * Save campaign
	 * 
	 * @return void
	 */
	public function actionSave()
	{		
		$this->requirePostRequest();

		$campaignModel = SproutEmail_CampaignModel::populateModel(craft()->request->getPost());

		if($campaignId = craft()->sproutEmail->saveCampaign($campaignModel, craft()->request->getPost('tab')))
		{
			// if this was called by the child (Notifications), return the model
			if(get_class($this) == 'Craft\SproutEmail_NotificationsController')
			{
				$campaignModel->id = $campaignId;
				return $campaignModel;
			}
			craft()->userSession->setNotice(Craft::t('Campaign successfully saved.'));	
			
			switch (craft()->request->getPost('continue'))
			{
			    case 'info':
			        $this->redirect('sproutemail/campaigns/edit/' . $campaignId . '/template');
			        break;
			    case 'template':
			        $this->redirect('sproutemail/campaigns/edit/' . $campaignId . '/recipients');
			        break;
			    default:
			        $this->redirectToPostedUrl(array($campaignModel));
			        break;
			}
		}
		else  // problem
		{
		    craft()->userSession->setError(Craft::t('Please correct the errors below.'));
		    
		    // if this was called by the child (Notifications), return the model
		    if(get_class($this) == 'Craft\SproutEmail_NotificationsController')
		    {
		        return $campaignModel;
		    }			
		}
		
		// Send the field back to the template
		craft()->urlManager->setRouteVariables(array(
			'campaign' => $campaignModel
		));
	}
	
	/**
	 * Delete campaign
	 * 
	 * @return void
	 */
	public function actionDelete()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();
		 
		$this->returnJson(array(
				'success' => craft()->sproutEmail->deleteCampaign(craft()->request->getRequiredPost('id'))));
	}
	
	/**
	 * Save settings
	 *
	 * @return void
	 */
	public function actionSaveSettings()
	{
	    $this->requirePostRequest();
	    
	    foreach(craft()->request->getPost('settings') as $provider => $settings)
	    {
	        $service = 'sproutEmail_' . lcfirst($provider);
	        craft()->$service->saveSettings($settings);
	    }
	
	    craft()->userSession->setNotice(Craft::t('Settings successfully saved.'));
	    $this->redirectToPostedUrl();
	}
}