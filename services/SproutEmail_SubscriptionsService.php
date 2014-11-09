<?php
namespace Craft;

class SproutEmail_SubscriptionsService extends BaseApplicationComponent
{
	/**
	 * Get subscription users given element id
	 *
	 * @param string $elementId            
	 */
	public function getSubscriptionUsersByElementId($elementId = null)
	{
		$users = array ();
		$criteria = new \CDbCriteria();
		$criteria->condition = 'elementId=:elementId';
		$criteria->params = array (
				':elementId' => $elementId 
		);
		
		if ( $subscriptions = SproutEmail_SubscriptionRecord::model()->findAll( $criteria ) )
		{
			$criteria = craft()->elements->getCriteria( 'User' );
			
			foreach ( $subscriptions as $subscription )
			{
				$criteria->id = $subscription->elementId;
				$users [] = craft()->elements->findElements( $criteria );
			}
		}
		
		return $users;
	}
}
