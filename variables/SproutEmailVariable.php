<?php
namespace Craft;

/**
 * Main SproutEmail variable interface
 */
class SproutEmailVariable
{
	/**
	 * Plugin Name
	 * Make your plugin name available as a variable
	 * in your templates as {{ craft.YourPlugin.name }}
	 *
	 * @return string
	 */
	public function getName()
	{
		$plugin = craft()->plugins->getPlugin( 'sproutemail' );
		return $plugin->getName();
	}
	
	/**
	 * Get plugin version
	 *
	 * @return string
	 */
	public function getVersion()
	{
		$plugin = craft()->plugins->getPlugin( 'sproutemail' );
		return $plugin->getVersion();
	}
	
	/**
	 * Get All EmailBlastTypes
	 * By default, this returns all emailblasts that are normal emailblasts.
	 * If you want
	 * to get only Section-based emailblasts, pass 'true' to the $sectionId paramenter
	 *
	 * @todo - make this more intuitive, kinda clunky parameter names
	 *      
	 * @return mixed EmailBlastType model
	 */
	public function getEmailBlastTypes()
	{
		/**
	 * Get All Section Based EmailBlastTypes
	 * return craft()->sproutEmail->getEmailBlastTypes();
	 */
	
		return craft()->sproutEmail->getAllEmailBlastTypes();
	}
	
	/**
	 * Get a EmailBlastType by id *
	 *
	 * @param int $emailBlastTypeId            
	 * @return object emailBlastType record
	 */
	public function getEmailBlastTypeById($emailBlastTypeId)
	{
		return craft()->sproutEmail->getEmailBlastTypeById($emailBlastTypeId);
	}
	
	/**
	 * Get all EmailBlastType Info (only settings, no related Section EmailBlastTypes)
	 *
	 * @return object emailBlastType table records
	 */
	public function getAllEmailBlastTypeInfo()
	{
		return craft()->sproutEmail->getAllEmailBlastTypeInfo();
	}
	
	/**
	 * Get All Sections for Options dropdown *
	 *
	 * @param string $indexBy            
	 * @return array
	 */
	public function getAllSections($indexBy = null)
	{
		$result = craft()->sections->getAllSections( $indexBy );
		
		$options = array (
			array (
				'label' => 'Select a Section...',
				'value' => '' 
			) 
		);
		
		foreach ( $result as $key => $section )
		{
			array_push( $options, array (
				'label' => $section->name,
				'value' => $section->id 
			) );
		}
		
		return $options;
	}
	
	/**
	 * Get all user groups
	 *
	 * @param string $indexBy            
	 * @return array
	 */
	public function getAllUserGroups($indexBy = null)
	{
		if ( ! craft()->hasPackage( CraftPackage::Users ) )
		{
			$parts = explode( '/', craft()->request->requestUri );
			array_pop( $parts );
			craft()->userSession->setError( Craft::t( 'In order to use this feature, you must install the ' . CraftPackage::Users . ' package.' ) );
			craft()->request->redirect( implode( '/', $parts ) );
		}

		$result = craft()->userGroups->getAllGroups( $indexBy );
		$options = array ();
		
		foreach ( $result as $key => $group )
		{
			$options [$group->id] = $group->name;
		}
		
		return $options;
	}
	
	/**
	 * Get subscriber list for specified provider
	 *
	 * @param string $provider            
	 * @return array
	 */
	public function getSubscriberList($provider = 'SproutEmail')
	{
		$service = 'sproutEmail_' . lcfirst( $provider );
		return craft()->{$service}->getSubscriberList();
	}
	
	
	/**
	 * Get emailBlastType list for specified provider
	 *
	 * @param string $provider            
	 * @return array
	 */
	public function getEmailBlastTypeList($provider = 'SproutEmail')
	{
		$service = 'sproutEmail_' . lcfirst( $provider );
		return craft()->{$service}->getEmailBlastTypeList();
	}
	
	/**
	 * Get templates
	 *
	 * @return array
	 */
	public function getTemplatesDirListing()
	{
		return craft()->sproutEmail->getTemplatesDirListing();
	}
	
	/**
	 * Get plain text field handles
	 *
	 * @return array
	 */
	public function getPlainTextFields()
	{
		return craft()->sproutEmail->getPlainTextFields();
	}

	/**
	 * Get email providers
	 *
	 * @return array
	 */
	public function getEmailProviders($excludeWithoutApiSettings = false)
	{
		$providers = craft()->sproutEmail_emailProvider->getEmailProviders();
		
		if ( $excludeWithoutApiSettings )
		{
			foreach ( $providers as $key => $provider )
			{
				$settings = $this->getEmailProviderSettings( $provider );
				if ( ! $settings->valid )
				{
					unset( $providers [$key] );
				}
			}
		}
		
		return $providers;
	}
	public function getEmailProviderSettings($provider)
	{
		$service = 'sproutEmail_' . lcfirst( $provider );
		return craft()->$service->getSettings();
	}
	
	/**
	 * Get notifications
	 *
	 * @return array
	 */
	public function getNotifications()
	{
		return craft()->sproutEmail->getNotifications();
	}
	
	/**
	 * Get notification events
	 *
	 * @param string $notificationEvent            
	 * @param bool $return_full_objects            
	 * @return array
	 */
	public function getNotificationEvents($notificationEvent = null, $return_full_objects = false)
	{
		// we'll use this opportunity to clean up and register plugin registration events;
		// although this is more of an 'install' type script, doing it here limits
		// its execution and keeps the events fresh
		craft()->sproutEmail_integration->registerEvents();
		
		$events = craft()->sproutEmail->getNotificationEvents( $notificationEvent );
		
		if ( $return_full_objects )
		{
			return $events;
		}
		
		$out = array ();
		foreach ( $events as $event )
		{
			if ( $event->registrar == 'craft' )
			{
				$out [str_replace( '.', '---', $event->event )] = $event->description;
			}
			else
			{
				$out [$event->id] = $event->description;
			}
		}
		
		return $out;
	}
	
	/**
	 * Get notification event for specified id
	 *
	 * @param int $id            
	 * @return obj
	 */
	public function getNotificationEventById($id)
	{
		return craft()->sproutEmail->getNotificationEventById( $id );
	}
	
	/**
	 * Get notification event options
	 *
	 * @return array
	 */
	public function getNotificationEventOptions()
	{
		$res = craft()->sproutEmail->getNotificationEventOptions();
		
		$out = array ();
		foreach ( $res as $key => $template )
		{
			if ( $key === 'plugin_options' )
				continue;
			list ( $event, $options ) = explode( '/', $template );
			$out ['system_options'] [$event] [] = $options;
		}
		
		if ( isset( $res ['plugin_options'] ) )
		{
			foreach ( $res ['plugin_options'] as $k => $v )
			{
				if ( empty( $v ) )
					continue;
				
				$out ['plugin_options'] [$k] = $v;
			}
		}
		
		// parse html
		if ( isset( $out ['plugin_options'] ) )
		{
			foreach ( $out ['plugin_options'] as $k => $v )
			{
				$out ['plugin_options'] [$k] = $v;
			}
		}
		
		return $out;
	}

	public function isSubscribed($userId = null, $elementId = null)
	{
		if ( ! $userId or ! $elementId )
		{
			return false;
		}
		
		$query = craft()->db->createCommand()->select( 'userId, elementId' )->from( 'sproutemail_subscriptions' )->where( array (
				'AND',
				'userId = :userId',
				'elementId = :elementId' 
		), array (
				':userId' => $userId,
				':elementId' => $elementId 
		) )->queryRow();
		
		return (is_array( $query )) ? true : false;
	}
	
	public function getSubscriptionIds($userId = null, $elementType = 'Entry', $criteria = array())
	{
		$userId = craft()->userSession->id;
		
		if ( ! $userId )
		{
			return false;
		}
		
		// @TODO - join the sproutemail_subscriptions and elements table to make sure we're only
		// getting back the IDs of the Elements that match our type.
		
		$results = craft()->db->createCommand()->select( 'elementId' )->from( 'sproutemail_subscriptions' )->where( 'userId = :userId', array (
				':userId' => $userId 
		) )->queryAll();
		
		$ids = "";
		
		foreach ( $results as $key => $value )
		{
			if ( $ids == "" )
			{
				$ids = $value ['elementId'];
			}
			else
			{
				$ids .= "," . $value ['elementId'];
			}
		}
		
		return $ids;
	}
	
	public function getGeneralSettingsTemplate($emailProvider = null)
	{
		$customTemplate = 'sproutemail/_providers/' . $emailProvider . '/generalEmailBlastTypeSettings';
		$customTemplateExists = craft()->templates->doesTemplateExist($customTemplate);
		
		// if there is a custom set of general settings for this provider, return those; if not, return the default
		if($customTemplateExists)
		{
			return true;
		}	
		
		return false;
	}

	/**
	 * Provider specific functions (since there is no support for multiple variable files)
	 */
	public function getSendGridSenderAddresses()
	{
		if( ! $senderAddresses = craft()->sproutEmail_sendGrid->getSenderAddresses())
		{
			$senderAddresses[''] = 'Please create a sender address (from name) in your SendGrid account.';
		}
		
		return $senderAddresses;
	}
}
