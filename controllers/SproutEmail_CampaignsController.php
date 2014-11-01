<?php
namespace Craft;

/**
 * Campaigns controller
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
		$campaignId = craft()->request->getPost('campaignId');
		$entryId = craft()->request->getPost('entryId');

		$campaign = craft()->sproutEmail->getCampaign( array (
				'id' => $campaignId 
		) );
		
		if ( $campaign->emailProvider != 'SproutEmail' )
		{
			craft()->sproutEmail_emailProvider->exportCampaign($entryId, $campaignId);
		}
		else
		{
			craft()->sproutEmail_emailProvider->exportCampaign($entryId, $campaignId);
						
			craft()->tasks->createTask( 'SproutEmail_RunCampaign', Craft::t( 'Running campaign' ), array (
					'campaignId' => $campaignId,
					'entryId' => $entryId 
			) );

			// Apparently not. Is there a pending task?
			$task = craft()->tasks->getNextPendingTask();
			
			if ( $task )
			{
				// Return info about the next pending task without stopping PHP execution
				JsonHelper::sendJsonHeaders();
				craft()->request->close( JsonHelper::encode( 'Campaign successfully scheduled.' ) );
				
				// Start running tasks
				craft()->tasks->runPendingTasks();
			}
		}
	}
	
	/**
	 * Save campaign
	 *
	 * @return void
	 */
	public function actionSave()
	{
		$this->requirePostRequest();

		$tab = craft()->request->getPost('tab');
		$continue = craft()->request->getPost('continue');
		
		$campaignModel = SproutEmail_CampaignModel::populateModel( craft()->request->getPost() );
				
		$campaignModel->useRecipientLists = craft()->request->getPost( 'useRecipientLists' ) ? 1 : 0;
		
		if ( $campaignId = craft()->sproutEmail->saveCampaign($campaignModel, $tab))
		{
			// if this was called by the child (Notifications), return the model
			if ( get_class( $this ) == 'Craft\SproutEmail_NotificationsController' )
			{
				$campaignModel->id = $campaignId;
				return $campaignModel;
			}
			craft()->userSession->setNotice( Craft::t( 'Campaign successfully saved.' ) );
			
			if($continue == 'info')
			{
				if(craft()->request->getPost( 'emailProvider' ) == 'CopyPaste')
				{
					$redirectUrl = 'sproutemail/campaigns/edit/' . $campaignId . '/template';
					$this->redirect($redirectUrl);
				}
				else
				{
					$redirectUrl = 'sproutemail/campaigns/edit/' . $campaignId . '/recipients';
					$this->redirect($redirectUrl);
				}
			}
			elseif ($continue == 'recipients')
			{
				$redirectUrl = 'sproutemail/campaigns/edit/' . $campaignId . '/template';
				$this->redirect($redirectUrl);
			}
			else
			{
				$this->redirectToPostedUrl($campaignModel);
			}
		}
		else // problem
		{
			craft()->userSession->setError( Craft::t( 'Please correct the errors below.' ) );
			
			// if this was called by the child (Notifications), return the model
			if ( get_class( $this ) == 'Craft\SproutEmail_NotificationsController' )
			{
				return $campaignModel;
			}
		}
		
		// Send the field back to the template
		craft()->urlManager->setRouteVariables( array (
				'campaign' => $campaignModel 
		) );
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

		$campaignId = craft()->request->getRequiredPost('id');
		
		$this->returnJson( array (
				'success' => craft()->sproutEmail->deleteCampaign($campaignId) 
		) );
	}
	
	/**
	 * Save settings
	 *
	 * @return void
	 */
	public function actionSaveSettings()
	{
		$this->requirePostRequest();

		$settings = craft()->request->getPost('settings');
		
		foreach ($settings  as $provider => $providerSettings )
		{
			$service = 'sproutEmail_' . lcfirst( $provider );
			craft()->$service->saveSettings( $providerSettings );
		}
		
		craft()->userSession->setNotice( Craft::t( 'Settings successfully saved.' ) );
		$this->redirectToPostedUrl();
	}
}