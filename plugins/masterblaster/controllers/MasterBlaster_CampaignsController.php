<?php
namespace Craft;

/**
 * Main MasterBlaster controller
 *
 */
class MasterBlaster_CampaignsController extends BaseController
{
	
	public function actionExport()
	{
		craft()->masterBlaster_emailProvider->exportCampaign(
			craft()->request->getPost('entryId'),craft()->request->getPost('campaignId'));
	}

	/**
	 * Save campaign
	 * @return void
	 */
	public function actionSave()
	{	
		//$_POST['id'] = null;		
		$this->requirePostRequest();
		
		// mass assignment to form model		
		$campaign_model = MasterBlaster_CampaignModel::populateModel(craft()->request->getPost());

		if($campaignId = craft()->masterBlaster->saveCampaign($campaign_model))
		{
			if(get_class($this) == 'Craft\MasterBlaster_NotificationsController')
			{
				return $campaignId;
			}
			craft()->userSession->setNotice(Craft::t('Campaign saved.'));			
			$this->redirectToPostedUrl(array($campaign_model));
		}
		else  // problem
		{
			craft()->userSession->setError(Craft::t('Couldn’t save form.'));
		}
		
		// Send the field back to the template
		craft()->urlManager->setRouteVariables(array(
			'campaign' => $campaign_model
		));
	}
	
	/**
	 * Deletes a campaign
	 * @return void
	 */
	public function actionDelete()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();
		 
		$this->returnJson(array(
				'success' => craft()->masterBlaster->deleteCampaign(craft()->request->getRequiredPost('id'))));
	}
}