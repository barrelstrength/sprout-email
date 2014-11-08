<?php
namespace Craft;

/**
 * Run email emailBlastType task
 */
class SproutEmail_RunEmailBlastTypeTask extends BaseTask
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
			'emailBlastTypeId' => AttributeType::String,
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
		return Craft::t( 'Running email emailBlastType' );
	}
	
	/**
	 * Gets the total number of steps for this task.
	 *
	 * @return int
	 */
	public function getTotalSteps()
	{
		// a single emailBlastType is one run, regardless of number of recipients
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
		return craft()->sproutEmail_emailProvider->exportEmailBlast( $settings->entryId, $settings->emailBlastTypeId, true );
	}
}
