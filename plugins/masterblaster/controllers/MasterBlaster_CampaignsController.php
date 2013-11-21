<?php
namespace Craft;

/**
 * Campaigns controller
 *
 */
class MasterBlaster_CampaignsController extends BaseController
{
	/**
	 * Export campaign
	 * 
	 * @return void
	 */
	public function actionExport()
	{
		craft()->masterBlaster_emailProvider->exportCampaign(
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

		$campaignModel = MasterBlaster_CampaignModel::populateModel($_POST);

		if($campaignId = craft()->masterBlaster->saveCampaign($campaignModel))
		{
			// if this was called by the child (Notifications), return the new pk
			if(get_class($this) == 'Craft\MasterBlaster_NotificationsController')
			{
				$campaignModel->id = $campaignId;
				return $campaignModel;
			}
			craft()->userSession->setNotice(Craft::t('Campaign successfully saved.'));			
			$this->redirectToPostedUrl(array($campaignModel));
		}
		else  // problem
		{
			craft()->userSession->setError(Craft::t('Please correct the errors below.'));
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
				'success' => craft()->masterBlaster->deleteCampaign(craft()->request->getRequiredPost('id'))));
	}
}