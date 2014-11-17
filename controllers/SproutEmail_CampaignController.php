<?php
namespace Craft;

/**
 * Campaign controller
 */
class SproutEmail_CampaignController extends BaseController
{
	
	/**
	 * Export campaign
	 *
	 * @return void
	 */
	public function actionExport()
	{
		$campaign = craft()->sproutEmail->getEntry( array (
				'id' => craft()->request->getPost( 'campaignId' ) 
		) );
		
		if ( $campaign->emailProvider != 'SproutEmail' )
		{
			craft()->sproutEmail_emailProvider->exportEntry( craft()->request->getPost( 'entryId' ), craft()->request->getPost( 'campaignId' ) );
		}
		else
		{
			craft()->sproutEmail_emailProvider->exportEntry( craft()->request->getPost( 'entryId' ), craft()->request->getPost( 'campaignId' ) );
						
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
	public function actionSaveCampaign()
	{
		$this->requirePostRequest();
		
		$campaignId = craft()->request->getRequiredPost('sproutEmail.id');

		// @TODO - clean this up
		$campaign = craft()->sproutEmail_campaign->getCampaignById($campaignId);
		$campaign->setAttributes( craft()->request->getPost('sproutEmail') );
		
		$useRecipientLists = craft()->request->getPost( 'useRecipientLists' ) ? 1 : 0;
		$campaign->useRecipientLists = $useRecipientLists;

		if (craft()->request->getPost('fieldLayout')) 
		{
			// Set the field layout
			$fieldLayout =  craft()->fields->assembleLayoutFromPost();
							
			$fieldLayout->type = 'SproutEmail_Campaign';
			$campaign->setFieldLayout($fieldLayout);
		}

		$tab = craft()->request->getPost( 'tab' );

		if ( $campaign = craft()->sproutEmail_campaign->saveCampaign( $campaign,  $tab) )
		{	
			$_POST['redirect'] = str_replace('{id}', $campaign->id, $_POST['redirect']);

			craft()->userSession->setNotice( Craft::t( 'Campaign successfully saved.' ) );

			$continue = craft()->request->getPost( 'continue' );
			
			// @TODO - review this, removed from saveNotification controller and placed here for review
			// // convert notificationEvent to id
			// if ( isset( $_POST ['notificationEvent'] ) && ! is_numeric( $_POST ['notificationEvent'] ) )
			// {
			// 	$criteria = new \CDbCriteria();
			// 	$criteria->condition = 'event=:event';
			// 	$criteria->params = array (
			// 			':event' => str_replace( '---', '.', $_POST ['notificationEvent'] ) 
			// 	);
				
			// 	if ( $res = SproutEmail_NotificationEventRecord::model()->find( $criteria ) )
			// 	{
			// 		$_POST ['notificationEvent'] = ( int ) $res->id;
			// 	}
			// }
			
			// if ( isset( $_POST ['notificationEvent'] ) )
			// {
			// 	// since this is a notification, we'll make an event/email blast association...
			// 	craft()->sproutEmail_notifications->associateCampaign( $campaign->id, $_POST ['notificationEvent'] );
				
			// 	// ... and set notification options
			// 	craft()->sproutEmail_notifications->setCampaignNotificationEventOptions( $campaign->id, $_POST );
			// }

			if ($continue == 'info')
			{
				if(craft()->request->getPost( 'emailProvider' ) == 'CopyPaste')
				{
					$this->redirect( 'sproutemail/settings/campaigns/edit/' . $campaign->id . '/template' );
				}
				else
				{
					$this->redirect( 'sproutemail/settings/campaigns/edit/' . $campaign->id . '/recipients' );
				}
			}
			elseif($continue == 'recipients')
			{
				$this->redirect( 'sproutemail/settings/campaigns/edit/' . $campaign->id . '/template' );
			}
			else
			{
				$this->redirectToPostedUrl($campaign);
			}
		}
		else
		{
			SproutEmailPlugin::log(json_encode($campaign->getErrors()));
			
			craft()->userSession->setError( Craft::t( 'Please correct the errors below.' ) );
			
			// Send the field back to the template
			craft()->urlManager->setRouteVariables(array(
				'campaign' => $campaign 
			));
		}
	}
	
	/**
	 * Delete campaign
	 *
	 * @return void
	 */
	public function actionDeleteCampaign()
	{
		$this->requirePostRequest();
		
		$campaignId = craft()->request->getRequiredPost('id');

		// @TODO - handle errors
		if (craft()->sproutEmail_campaign->deleteCampaign($campaignId)) 
		{
			craft()->userSession->setNotice(Craft::t('Campaign deleted.'));

			$this->redirectToPostedUrl();	
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t delete Campaign.'));
		}
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


	public function actionCampaignSettingsTemplate(array $variables = array())
	{
		if (isset($variables['campaignId'])) 
		{
			// If campaign already exists, we're returning an error object
			if ( ! isset($variables['campaign']) ) 
			{
				$variables['campaign'] = craft()->sproutEmail_campaign->getCampaignById($variables['campaignId']);
			}
		}
		else
		{	
			$variables['campaign'] = new SproutEmail_Campaign();
		}
		
		// Load our template
		$this->renderTemplate('sproutemail/settings/campaigns/_edit', $variables);
	}
}