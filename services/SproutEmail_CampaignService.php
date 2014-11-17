<?php
namespace Craft;

class SproutEmail_CampaignService extends BaseApplicationComponent
{

	public function getCampaigns($type = null)
	{	
		// Grab All Blast Types by default
		$query = craft()->db->createCommand()
					->select( '*' )
					->from( 'sproutemail_campaigns' )
					->order( 'dateCreated desc' );

		// If we have a specific $type, limit the results
		if ($type == Campaign::Email OR 
				$type == Campaign::Notification) 
		{
			$query->where( 'type=:type', array(':type' => $type));
		}
		
		$results = $query->queryAll();

		$campaigns = SproutEmail_CampaignModel::populateModels($results);
		
		return $campaigns;
	}

	/**
	 * Returns all section based entries.
	 *
	 * @param string|null $indexBy            
	 * @return array
	 */
	public function getCampaignById($campaignId)
	{
		$campaignRecord = SproutEmail_CampaignRecord::model();
		$campaignRecord = $campaignRecord->findById($campaignId);
		
		if ($campaignRecord) 
		{
			return SproutEmail_CampaignModel::populateModel($campaignRecord);
		} 
		else 
		{
			return new SproutEmail_CampaignModel();
		}
	}

	/**
	 * Returns section based campaign by entryId
	 *
	 * @param string|null $entryId            
	 * @return array
	 */
	public function getCampaignByEntryAndCampaignId($entryId = false, $campaignId = false)
	{
		return SproutEmail_CampaignRecord::model()->getCampaignByEntryAndCampaignId( $entryId, $campaignId );
	}

	/**
	 * Returns campaignRecipient lists
	 *
	 * @param int $campaignId            
	 * @return array
	 */
	public function getCampaignRecipientLists($campaignId)
	{
		$criteria = new \CDbCriteria();
		$criteria->condition = 'campaign.id=:campaignId';
		$criteria->params = array (
				':campaignId' => $campaignId 
		);
		
		return SproutEmail_RecipientListRecord::model()->with( 'campaign' )->findAll( $criteria );
	}

	/**
	 * Process the 'save campaign' action.
	 *
	 * @param SproutEmail_CampaignModel $campaign            
	 * @throws \Exception
	 * @return int CampaignRecordId
	 */
	public function saveCampaign(SproutEmail_CampaignModel $campaign, $tab = 'info')
	{
		if (is_numeric($campaign->id))
		{
			$campaignRecord = SproutEmail_CampaignRecord::model()->findById( $campaign->id );
			$oldCampaign = SproutEmail_CampaignModel::populateModel($campaignRecord);
		}
		else
		{
			$campaignRecord = new SproutEmail_CampaignRecord();
		}

		// since we have to perform saves on multiple entities,
		// it's all or nothing using sql transactions
		$transaction = craft()->db->beginTransaction();
	       
		switch ($tab)
		{
			// save & associate the recipient list
			case 'recipients' :

				$service = 'sproutEmail_' . lcfirst( $campaignRecord->emailProvider );

				if ( ! craft()->{$service}->saveRecipientList( $campaign, $campaignRecord ) )
				{
					$transaction->rollback();
					return false;
				}
				
				break;

			case 'fields' : 
				
				// Save Field Layout
				$fieldLayout = $campaign->getFieldLayout();
				craft()->fields->saveLayout($fieldLayout);

				// Delete our previous record
				if ($campaign->id && $oldCampaign->fieldLayoutId) 
				{
					craft()->fields->deleteLayoutById($oldCampaign->fieldLayoutId);	
				}

				// Assign our new layout id info to our 
				// form model and records
				$campaign->fieldLayoutId = $fieldLayout->id;				
				$campaignRecord->fieldLayoutId = $fieldLayout->id;

				
				// Save the Campaign
				$campaignRecord = $this->_saveCampaignInfo( $campaign );

				if ( $campaignRecord->hasErrors() ) // no good
				{
					$transaction->rollBack();
					return false;
				}

				break;

			// save the campaign
			default :
				
				if ($campaign->type == 'notification')
				{
					$campaign->type = Campaign::Notification;
				}
				else
				{	
					$campaign->type = Campaign::Email;
				}

				try
				{	
					// Save the Campaign
					$campaignRecord = $this->_saveCampaignInfo( $campaign );

					// Rollback if saving fails
					if ( $campaignRecord->hasErrors() )
					{
						$transaction->rollBack();
						return false;
					}

					// If we have a Notification, also Save the Entry
					if ($campaign->type == Campaign::Notification) 
					{	
						// Check to see if we have a matching Entry by CampaignId
						$criteria = craft()->elements->getCriteria('SproutEmail_Entry');

						if (isset($oldCampaign->id)) 
						{
							$criteria->campaignId = $oldCampaign->id;
						}
						
						$entry = $criteria->first();
						
						if (isset($entry))
						{	
							// if we have a blast already, update it
							$entry->campaignId = $campaignRecord->id;
							$entry->subjectLine = ($entry->subjectLine != '') ? $entry->subjectLine : $campaign->name;
							$entry->getContent()->title = $campaign->name;
						}
						else
						{
							// If we don't have a blast yet, create a new entry
							$entry = new SproutEmail_EntryModel();
							$entry->campaignId = $campaignRecord->id;
							$entry->subjectLine = $campaign->subject;
							$entry->getContent()->title = $campaign->name;
						}
						
						if (craft()->sproutEmail_entry->saveEntry($entry)) 
						{
							// TODO - redirect and such
						}
						else
						{
							SproutEmailPlugin::log(json_encode($entry->getErrors()));

							echo "<pre>";
							print_r($entry->getErrors());
							echo "</pre>";
							die('fin');
							
						}
					}

				}
				catch ( \Exception $e )
				{	
					SproutEmailPlugin::log(json_encode($e));

					throw new Exception( Craft::t( 'Error: Campaign could not be saved.' ) );
				}
				break;
		}
		
		$transaction->commit();
		
		return SproutEmail_CampaignModel::populateModel($campaignRecord);
	}

	private function _saveCampaignInfo(SproutEmail_CampaignModel &$campaign)
	{
		$oldCampaignEmailProvider = null;
		
		// If we already have a numeric ID this will be an edit
		if ( isset( $campaign->id ) && is_numeric($campaign->id) )
		{
			$campaignRecord = SproutEmail_CampaignRecord::model()->findById( $campaign->id );
			
			if ( ! $campaignRecord )
			{
				throw new Exception( Craft::t( 'No campaign exists with the ID “{id}”', array (
						'id' => $campaign->id 
				) ) );
			}
			
			$oldCampaignEmailProvider = $campaignRecord->emailProvider;
		}
		else
		{
			$campaignRecord = new SproutEmail_CampaignRecord();
		}
		
		// Set common attributes
		$campaignRecord->fieldLayoutId = $campaign->fieldLayoutId;
		$campaignRecord->name = $campaign->name;
		$campaignRecord->handle = $campaign->handle;
		$campaignRecord->type = $campaign->type;
		$campaignRecord->titleFormat = $campaign->titleFormat;
		$campaignRecord->hasUrls = $campaign->hasUrls;
		$campaignRecord->hasAdvancedTitles = $campaign->hasAdvancedTitles;
		$campaignRecord->subject = $campaign->subject;
		$campaignRecord->fromEmail = $campaign->fromEmail;
		$campaignRecord->fromName = $campaign->fromName;
		$campaignRecord->replyToEmail = $campaign->replyToEmail;
		$campaignRecord->emailProvider = $campaign->emailProvider;
		
		$campaignRecord->urlFormat = $campaign->urlFormat;
		$campaignRecord->template = $campaign->template;
		$campaignRecord->templateCopyPaste = $campaign->templateCopyPaste;

		// if this is a notification and replyToEmail does NOT contain a twig variable
		// OR this is not a notification, set email rule
		if ( ($campaignRecord->notificationEvent && ! preg_match( '/{{(.*?)}}/', $campaignRecord->replyToEmail )) || ! $campaignRecord->notificationEvent )
		{
			$campaignRecord->addRules( array (
					'replyToEmail',
					'email' 
			) );
		}
		
		$campaignRecord->validate();
		$campaign->addErrors( $campaignRecord->getErrors() );
		
		if ( ! $campaignRecord->hasErrors() )
		{
			$campaignRecord->save( false );
			
			// if emailProvider has changed, let's get rid of the old recipient list since it's no longer valid
			if ( $campaignRecord->emailProvider != $oldCampaignEmailProvider )
			{
				if ( $recipientLists = $this->getCampaignRecipientLists( $campaignRecord->id ) )
				{
					foreach ( $recipientLists as $list )
					{
						$this->deleteCampaignRecipientList( $list->id, $campaignRecord->id );
					}
				}
			}
		}
		
		return $campaignRecord;
	}

	/**
	 * Deletes a campaign by its ID along with associations;
	 * also cleans up any remaining orphans
	 *
	 * @param int $campaignId            
	 * @return bool
	 */
	public function deleteCampaign($campaignId)
	{
		// since we have to perform deletes on multiple entities,
		// it's all or nothing using sql transactions
		$transaction = craft()->db->beginTransaction();
		
		try
		{
			$campaignRecord = SproutEmail_CampaignRecord::model()->findByPk( $campaignId );
			
			// delete campaign
			if ( ! craft()->db->createCommand()->delete( 'sproutemail_campaigns', array (
					'id' => $campaignId 
			) ) )
			{
				$transaction->rollback();
				return false;
			}
			
			// delete associated recipients
			$service = 'sproutEmail_' . lcfirst( $campaignRecord->emailProvider );
			craft()->{$service}->deleteRecipients( $campaignRecord );
		}
		catch ( \Exception $e )
		{
			$transaction->rollback();
			return false;
		}
		
		$transaction->commit();
		return true;
	}

	/**
	 * Delete campaign recipient list entry
	 *
	 * @param int $recipientListId            
	 * @param int $campaignId            
	 * @return bool
	 */
	public function deleteCampaignRecipientList($recipientListId, $campaignId)
	{
		return craft()->db->createCommand()->delete( 'sproutemail_campaigns_recipientlists', array (
				'recipientListId' => $recipientListId,
				'campaignId' => $campaignId 
		) );
	}
}
