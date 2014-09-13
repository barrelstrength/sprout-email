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
		$campaign = craft()->sproutEmail->getCampaign( array (
				'id' => craft()->request->getPost( 'campaignId' ) 
		) );
		
		if ( $campaign->emailProvider != 'SproutEmail' )
		{
			craft()->sproutEmail_emailProvider->exportCampaign( craft()->request->getPost( 'entryId' ), craft()->request->getPost( 'campaignId' ) );
		}
		else
		{
	        craft()->sproutEmail_emailProvider->exportCampaign( craft()->request->getPost( 'entryId' ), craft()->request->getPost( 'campaignId' ) );
            
			craft()->tasks->createTask( 'SproutEmail_RunCampaign', Craft::t( 'Running campaign' ), array (
					'campaignId' => craft()->request->getPost( 'campaignId' ),
					'entryId' => craft()->request->getPost( 'entryId' ) 
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
		
		$campaignModel = SproutEmail_CampaignModel::populateModel( craft()->request->getPost() );
				
		$campaignModel->useRecipientLists = craft()->request->getPost( 'useRecipientLists' ) ? 1 : 0;
		
		if ( $campaignId = craft()->sproutEmail->saveCampaign( $campaignModel, craft()->request->getPost( 'tab' ) ) )
		{
			// if this was called by the child (Notifications), return the model
			if ( get_class( $this ) == 'Craft\SproutEmail_NotificationsController' )
			{
				$campaignModel->id = $campaignId;
				return $campaignModel;
			}
			craft()->userSession->setNotice( Craft::t( 'Campaign successfully saved.' ) );
			
			$continue = craft()->request->getPost( 'continue' );
			
			if($continue == 'info')
			{
				if(craft()->request->getPost( 'emailProvider' ) == 'CopyPaste'){
				    $this->redirect( 'sproutemail/campaigns/edit/' . $campaignId . '/template' );
                }
                else
                {
                    $this->redirect( 'sproutemail/campaigns/edit/' . $campaignId . '/recipients' );
                }
			}
			elseif($continue == 'recipients')
			{
				$this->redirect( 'sproutemail/campaigns/edit/' . $campaignId . '/template' );
			}
			else
			{
				$this->redirectToPostedUrl(
				                            array(
						                            $campaignModel 
                                                 )
                                          );
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
		
		$this->returnJson( array (
				'success' => craft()->sproutEmail->deleteCampaign( craft()->request->getRequiredPost( 'id' ) ) 
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
		
		foreach ( craft()->request->getPost( 'settings' ) as $provider => $settings )
		{
			$service = 'sproutEmail_' . lcfirst( $provider );
			craft()->$service->saveSettings( $settings );
		}
		
		craft()->userSession->setNotice( Craft::t( 'Settings successfully saved.' ) );
		$this->redirectToPostedUrl();
	}
}