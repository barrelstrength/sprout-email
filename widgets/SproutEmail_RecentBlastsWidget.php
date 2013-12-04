<?php
namespace Craft;

class SproutEmail_RecentBlastsWidget extends BaseWidget
{
    public function getName()
    {
        return Craft::t('Email Support');
    }

    public function getBodyHtml()
    {
        // $cocktails = craft()->cocktailRecipes->getRecentCocktails();

        // Sends an email to the user represented by $user. $body and $htmlBody can be full-blown Twig templates, and any variables passed in via $variables will be made available to them.

        $user = new UserModel;

        $user['firstName']  = 'Ben';
        $user['lastName']   = 'Parizek';
        $user['email']      = 'ben@barrelstrengthdesign.com';

        $subject    = 'hello dolly';
        $body       = 'Waat';
        $htmlBody   = '<b>Woot</b>';
        $variables  = array();

        // Send email:
        // $dog = craft()->email->sendEmail($user, $subject, $body, $htmlBody, $variables);
        return craft()->templates->render('support/_widgets/email_support/body');

    }
}

// protected function defineSettings()
// {
//     return array(
//         'limit' => array(AttributeType::Number, 'min' => 0),
//     );
// }

// public function getSettingsHtml()
// {
//     return craft()->templates->render('support/_widgets/recentcocktails/settings', array(
//         'settings' => $this->getSettings();
//     ))
// }
