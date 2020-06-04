<?php /** @noinspection ClassConstantCanBeUsedInspection */

/**
 * Sprout Email config.php
 *
 * This file exists only as a template for the Sprout Email settings.
 * It does nothing on its own.
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'sprout-email.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'craft/config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

return [
    // The name to display in the control panel in place of the plugin name
    'pluginNameOverride' => 'Sprout Email',

    // Enable Notification Emails for sending and management within the
    // Control Panel
    'enableNotificationEmails' => true,

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
    'enablePerEmailEmailTemplateIdOverride' => false
];
