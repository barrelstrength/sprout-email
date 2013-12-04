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

		$campaignModel = SproutEmail_CampaignModel::populateModel($_POST);

		if($campaignId = craft()->sproutEmail->saveCampaign($campaignModel))
		{
			// if this was called by the child (Notifications), return the new pk
			if(get_class($this) == 'Craft\SproutEmail_NotificationsController')
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
				'success' => craft()->sproutEmail->deleteCampaign(craft()->request->getRequiredPost('id'))));
	}
}