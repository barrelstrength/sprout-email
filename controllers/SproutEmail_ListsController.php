<?php
namespace Craft;

/**
 * Lists controller
 */
class SproutEmail_ListsController extends BaseController
{
	/**
	 * Subscribes a user to a List
	 * @return [type] [description]
	 */
	public function actionSubscribe()
	{
		$userId = craft()->userSession->id;
		$elementId = craft()->request->getPost( 'elementId' );
		
		if ( ! $userId or ! $elementId )
		{
			return false;
		}
		
		$record = new SproutEmail_SubscriptionRecord();
		$record->userId = $userId;
		$record->elementId = $elementId;
		
		if ( $record->save() )
		{
			if ( craft()->request->isAjaxRequest() )
			{
				$this->returnJson( array (
						'success' => 'success' 
				) );
			}
			else
			{
				$this->redirectToPostedUrl();
			}
		}
		else
		{
			$errors = $record->getErrors();
			
			if ( craft()->request->isAjaxRequest() )
			{
				$this->returnJson( array (
						'errors' => $errors 
				) );
			}
			else
			{
				craft()->urlManager->setRouteVariables( array (
						'errors' => $errors 
				) );
				
				$this->redirectToPostedUrl();
			}
		}
	}
	public function actionUnsubscribe()
	{
		$userId = craft()->userSession->id;
		$elementId = craft()->request->getPost( 'elementId' );
		
		if ( ! $userId or ! $elementId )
		{
			return false;
		}
		
		$result = craft()->db->createCommand()->delete( 'sproutemail_subscriptions', array (
				'userId' => $userId,
				'elementId' => $elementId 
		) );
		
		if ( $result )
		{
			if ( craft()->request->isAjaxRequest() )
			{
				$this->returnJson( array (
						'success' => 'success' 
				) );
			}
			else
			{
				$this->redirectToPostedUrl();
			}
		}
		else
		{
			if ( craft()->request->isAjaxRequest() )
			{
				$this->returnJson( array (
						'response' => 'fail' 
				) );
			}
			else
			{
				craft()->urlManager->setRouteVariables( array (
						'response' => 'fail' 
				) );
				
				$this->redirectToPostedUrl();
			}
		}
		
		$this->redirectToPostedUrl();
	}
	
	/**
	 * Save emailBlastType
	 *
	 * @return void
	 */
	public function actionSave()
	{
		// first save the emailBlastType as normally would be done
		$emailBlastTypeModel = parent::actionSave();
		
		if ( $emailBlastTypeModel->getErrors() )
		{
			// Send the field back to the template
			craft()->urlManager->setRouteVariables( array (
					'emailBlastType' => $emailBlastTypeModel 
			) );
		}
		else
		{
			// convert notificationEvent to id
			if ( isset( $_POST ['notificationEvent'] ) && ! is_numeric( $_POST ['notificationEvent'] ) )
			{
				$criteria = new \CDbCriteria();
				$criteria->condition = 'event=:event';
				$criteria->params = array (
						':event' => str_replace( '---', '.', $_POST ['notificationEvent'] ) 
				);
				
				if ( $res = SproutEmail_NotificationEventRecord::model()->find( $criteria ) )
				{
					$_POST ['notificationEvent'] = ( int ) $res->id;
				}
			}
			
			if ( isset( $_POST ['notificationEvent'] ) )
			{
				
				// since this is a notification, we'll make an event/emailblasts association...
				craft()->sproutEmail_notifications->associateEmailBlastType( $emailBlastTypeModel->id, $_POST ['notificationEvent'] );
				
				// ... and set notification options
				craft()->sproutEmail_notifications->setEmailBlastTypeNotificationEventOptions( $emailBlastTypeModel->id, $_POST );
			}
			
			craft()->userSession->setNotice( Craft::t( 'Notification successfully saved.' ) );
			
			switch (craft()->request->getPost( 'continue' ))
			{
				case 'info' :
					$this->redirect( 'sproutemail/settings/notifications/edit/' . $emailBlastTypeModel->id . '/template' );
					break;

				case 'template' :
				
					$this->redirect( 'sproutemail/settings/notifications/edit/' . $emailBlastTypeModel->id . '/recipients' );
					break;

				default :
					
					$this->redirectToPostedUrl( array (
							$emailBlastTypeModel 
					) );

					break;
			}
		}
	}
}