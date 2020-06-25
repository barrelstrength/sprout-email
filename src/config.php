<?php /** @noinspection ClassConstantCanBeUsedInspection */

/**
 * Notifications settings available in craft/config/sprout.php
 *
 * This file does nothing on its own. It provides documentation of the
 * default value for each config setting and provides an example of how to
 * override each setting in 'craft/config/sprout.php`
 *
 * To override default settings, copy the settings you wish to implement to
 * your 'craft/config/sprout.php' config file and make your changes there.
 *
 * Config settings files are multi-environment aware so you can have different
 * settings groups for each environment, just as you do for 'general.php'
 */
return [
    'sprout' => [
        'notifications' => [
            // The templates that will be used to display your Notification Emails
            //
            // Email Template Class:
            // barrelstrength\sproutbase\app\email\emailtemplates\BasicTemplates
            //
            // Custom Templates Folder:
            // _emails/notification
            'emailTemplateId' => 'barrelstrength\sproutbase\app\email\emailtemplates\BasicTemplates',

            // Allow admins to (optionally) choose custom Email Templates for each
            // email created
            'enablePerEmailEmailTemplateIdOverride' => false,
        ],
    ],
];
