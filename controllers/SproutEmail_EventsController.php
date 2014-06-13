<?php

namespace Craft;

/**
 * Notification events controller
 */
class SproutEmail_EventsController extends BaseController
{
    /**
     * Save event
     *
     * @return void
     */
    public function actionSave()
    {
        $this->requirePostRequest();
        
        // mass assignment to form model
        $event_model = SproutEmail_NotificationEventModel::populateModel( craft()->request->getPost() );
        
        if ( $res = craft()->sproutEmail->saveEvent( $event_model ) )
        {
            if ( $res->hasErrors() )
            {
                craft()->userSession->setError( Craft::t( 'Couldn’t save form.' ) );
                
                // Send the field back to the template
                craft()->urlManager->setRouteVariables( array (
                        'event' => $event_model 
                ) );
                return true;
            }
            
            craft()->userSession->setNotice( Craft::t( 'Event saved.' ) );
            $this->redirectToPostedUrl( array (
                    $event_model 
            ) );
        }
        else // problem
        {
            craft()->userSession->setError( Craft::t( 'Couldn’t save form.' ) );
        }
        
        // Send the field back to the template
        craft()->urlManager->setRouteVariables( array (
                'event' => $event_model 
        ) );
    }
    
    /**
     * Deletes an event
     *
     * @return void
     */
    public function actionDelete()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();
        
        $this->returnJson( array (
                'success' => craft()->sproutEmail->deleteEvent( craft()->request->getRequiredPost( 'id' ) ) 
        ) );
    }
}