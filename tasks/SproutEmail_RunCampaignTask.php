<?php
namespace Craft;

/**
 * Run email campaign task
 */
class SproutEmail_RunCampaignTask extends BaseTask
{
	/**
	 * Defines the settings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineSettings()
	{
		return array (
			'campaignId' => AttributeType::String,
			'entryId' => AttributeType::String 
		);
	}
	
	/**
	 * Returns the default description for this task.
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return Craft::t( 'Running email campaign' );
	}
	
	/**
	 * Gets the total number of steps for this task.
	 *
	 * @return int
	 */
	public function getTotalSteps()
	{
		// a single campaign is one run, regardless of number of recipients
		return 1;
	}
	
	/**
	 * Runs a task step.
	 *
	 * @param int $step            
	 * @return bool
	 */
	public function runStep($step)
	{
		$settings = $this->getSettings();
		return craft()->sproutEmail_emailProvider->exportEntry( $settings->entryId, $settings->campaignId, true );
	}
}
