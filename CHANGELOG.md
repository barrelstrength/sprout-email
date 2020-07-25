# Changelog

## 5.0.0 - UNRELEASED

> {warning} See upgrade notes for breaking changes, etc.

## Added
- Added support for using service layer in templates via `sprout.app`

### Changed
- Updated codebase to be managed in `barrelstrength/sprout-base`
- Updated plugin translation category from `sprout-email` => `sprout`
- Updated Project Config settings from `sprout-email` => `sprout`
- Updated plugin to use `sprout` migration track
- Updated plugin settings to be managed via Craft settings area
- Updated undocumented template variables to use service layer directly 
- Updated `craftcms/cms` requirement v3.5.0
- Updated `barrelstrength/sprout-base` requirement v7.0.0

### Removed
- Removed `barrelstrength/sprout-base-email` dependency
- Removed `barrelstrength/sprout-base-fields` dependency
- Removed `barrelstrength/sprout-base-reports` dependency
- Removed `barrelstrength/sprout-base-sent-email` dependency

## 4.4.6 - 2020-05-28

### Fixed
- Fixed Sent Email navigation bug ([#165])

[#165]: https://github.com/barrelstrength/craft-sprout-email/issues/165

## 4.4.5 - 2020-05-24

### Added
- Added Enabled/Disabled status filters to Notification Email Element Index
- Add Notification Email SetStatus Element Action

### Changed
- Updated `barrelstrength/sprout-base-email` requirement to v1.3.8

### Fixed
- Fixed default return type for `barrelstrength/sproutbaseemail/base/NotificationEvent:getDescription()` 
- Fixed Notification Email Element Index CP Edit URL after modal close behavior  ([#163])

[#163]: https://github.com/barrelstrength/craft-sprout-email/issues/163

## 4.4.4 - 2020-05-21

### Changed
- Improved error handling when sending test emails
- Updated `sproutemail_sentemails.htmlBody` column type to MEDIUMTEXT ([#161])
- Updated `barrelstrength/sprout-base-sent-email` requirement v1.1.2

### Fixed
- Fixed bug where 'When an Entry is deleted' Event would also trigger when Drafts were deleted ([#149])
- Fixed bug processing Test recipients to ensure only Test Recipients receive Test Emails
- Fixed bug where valid Test email addresses could be marked invalid ([#162])
- Fixed syntax error when assigning errors during Test email workflow
- Fixed bug where `defaultBody` field could be processed even if it was null

[#149]: https://github.com/barrelstrength/craft-sprout-email/issues/149
[#161]: https://github.com/barrelstrength/craft-sprout-email/issues/161
[#162]: https://github.com/barrelstrength/craft-sprout-email/issues/162

## 4.4.3 - 2020-05-18

### Changed
- Updated `barrelstrength/sprout-base-email` requirement to v1.3.6

### Fixed
- Fixed error when creating new Notification Emails if Email Notification is not defined ([#479])

[#479]: https://github.com/barrelstrength/craft-sprout-forms/issues/479

## 4.4.2 - 2020-05-16

### Changed
- Updated email capture to check Swift message body before message children ([#126])
- Updated `barrelstrength/sprout-base-email` requirement to v1.3.5
- Updated `barrelstrength/sprout-base-reports` requirement to v1.5.2
- Updated `barrelstrength/sprout-base-sent-email` requirement to v1.1.1

### Fixed
- Fixed bug when previewing Notification Email ([#155])
- Fixed bug where Email Template settings were not saving correctly ([#156], [#158])
- Fixed various bugs in plugin settings migration and save behavior ([#159])

[#126]: https://github.com/barrelstrength/craft-sprout-email/issues/126
[#155]: https://github.com/barrelstrength/craft-sprout-email/issues/155
[#156]: https://github.com/barrelstrength/craft-sprout-email/issues/156
[#158]: https://github.com/barrelstrength/craft-sprout-email/issues/158
[#159]: https://github.com/barrelstrength/craft-sprout-email/issues/159

## 4.4.1 - 2020-05-15

### Changed
- Updated `barrelstrength/sprout-base` requirement v6.0.1
- Updated `barrelstrength/sprout-base-email` requirement v1.3.4

### Fixed
- Fixed bug in email settings migration

## 4.4.0 - 2020-04-28

### Changed
- Updated `barrelstrength/sprout-base` requirement v6.0.0

### Fixed
- Fixed migration issue when multiple Sprout plugins are installed
- Fixed Default Email Templates assignment ([#153])

[#153]: https://github.com/barrelstrength/craft-sprout-email/issues/153

## 4.3.0 - 2020-04-27

> {tip} - Sprout Email now supports editions LITE and PRO. Existing license holders will be updated to a PRO license and continue to have access to all existing features.

### Added
- Added LITE and PRO Editions
- Added uninstall migration
- Added example config file `src/config.php`
- Added `barrelstrength\sproutbase\base\SproutDependencyTrait`
- Added `barrelstrength\sproutbase\base\SproutDependencyInterface`
- Added `barrelstrength\sproutbase\records\Settings`
- Added `barrelstrength\sproutbase\migrations\Install::safeDown()`
- Added support for config overrides in base settings models

### Changed
- Moved Sent Email logic and templates to `barrelstrength/sprout-base-sent-email` module
- Moved Notification Email settings to `sprout_settings` table
- Updated `barrelstrength/sprout-base` requirement to v5.2.0
- Updated `barrelstrength/sprout-base-email` requirement to v1.3.0
- Updated `barrelstrength/sprout-base-fields` requirement to v1.4.0
- Updated `barrelstrength/sprout-base-reports` requirement to v1.5.0
- Updated `barrelstrength/sprout-base-sent-email` requirement to v1.0.0

### Fixed
- Fixed bug where disabling Notifications tab did not disable Notification Events
- Fixed bug where Email Column report setting could not be set properly ([#145])
- Fixed path in `barrelstrength\sproutbaseemail\emailtemplates\CustomTemplates::getTemplateRoot()`
- Fixed translation categories in various templates

### Removed
- Removed `barrelstrength\sproutbase\services\Settings::getPluginSettings()`
- Removed `barrelstrength\sproutbase\base\BaseSproutTrait`

[#145]: https://github.com/barrelstrength/craft-sprout-email/issues/145

## 4.2.0.1 - 2020-04-09

### Added
- Added `barrelstrength\sproutbaseemail\base\EmailTemplates::getTemplateMode()`

### Changed
- Updated `barrelstrength/sprout-base-email` requirement to v1.2.8

### Fixed
- Fixed issue where Email Templates may render in the incorrect Template Mode in some scenarios

## 4.2.0 - 2020-04-09

> {warning} Custom Email Templates Integrations will need to be updated to better support extending Twig layouts within the templates folder. See the [upgrade notes](https://sprout.barrelstrengthdesign.com/docs/email/installing-and-updating-craft-3.html#upgrading-to-email-v4-2-0).

### Added
- Added `barrelstrength\sproutbaseemail\base\EmailTemplates::getTemplateRoot()`

### Changed
- Updated Email Template rendering to treat Email Template folder as a subfolder of the template folder to allow more flexible use of extending other templates ([#111][#111-sprout-email], [#122][#122-sprout-email])
- Updated `barrelstrength\sproutbaseemail\base\EmailTemplates::getPath()` to define path within target template folder
- Updated `barrelstrength/sprout-base-email` requirement to v1.2.7

[#111-sprout-email]: https://github.com/barrelstrength/craft-sprout-email/issues/111
[#122-sprout-email]: https://github.com/barrelstrength/craft-sprout-email/issues/122

## 4.1.6 - 2020-03-19

### Changed
- Added `barrelstrength/sprout-base-reports` requirement
- Updated `barrelstrength/sprout-base-email` requirement to v1.2.4

### Fixed
- Fixed Notification Email settings in some Notification Email migration scenarios ([#121], [#125])
- Fixed `barrelstrength\sproutbasereports\base\DataSource` not found ([#144])

[#121]: https://github.com/barrelstrength/craft-sprout-email/issues/121
[#125]: https://github.com/barrelstrength/craft-sprout-email/issues/125
[#144]: https://github.com/barrelstrength/craft-sprout-email/issues/144

## 4.1.5 - 2020-03-14

### Changed
- Updated `barrelstrength/sprout-base-email` requirement to v1.2.3

### Fixed
- Notification Events are no longer initialized on install and migration requests ([#143])
- Notification Events are no longer initialized on console requests

[#143]: https://github.com/barrelstrength/craft-sprout-email/issues/143

## 4.1.4 - 2020-02-20

### Changed
- Updated Notification Email logic to only define CC and BCC when sending a Single Email
- Updated default sorting on Sent Email index page to descending ([#127])
- Updated organization of Enable Notifications and Enable Sent Email tracking settings 
- Updated `barrelstrength/sprout-base-email` requirement to v1.2.3
- Updated `barrelstrength/sprout-base-fields` requirement to v1.3.2
- Updated `barrelstrength/sprout-base` requirement to v5.1.2

### Fixed
- Fixed display bug with select dropdown field in edit page sidebars ([#139])
- Fixed bug in session logic when defining `viewContext` setting ([#142], [#425][#425-sprout-forms], [#428][#428-sprout-forms])
- Fixed bug where user could be directed to a tab without proper permissions in the sidebar ([#120])
- Fixed bug where Mailing Lists settings may not display on Notification Email Edit page
- Fixed bug where CC and BCC fields displayed alongside Email List settings even though they don't apply ([#427][#427-sprout-forms])

[#120]: https://github.com/barrelstrength/craft-sprout-email/issues/120
[#127]: https://github.com/barrelstrength/craft-sprout-email/issues/127
[#139]: https://github.com/barrelstrength/craft-sprout-email/issues/139
[#142]: https://github.com/barrelstrength/craft-sprout-email/issues/142
[#425-sprout-forms]: https://github.com/barrelstrength/craft-sprout-forms/issues/425
[#427-sprout-forms]: https://github.com/barrelstrength/craft-sprout-forms/issues/427
[#428-sprout-forms]: https://github.com/barrelstrength/craft-sprout-forms/issues/428

## 4.1.3 - 2020-02-12

### Changed
- Updated `barrelstrength/sprout-base-email` requirement to v1.2.2

### Fixed
- Fixed incorrect preview link ([#421])

[#421]: https://github.com/barrelstrength/craft-sprout-forms/issues/421

## 4.1.2 - 2020-02-06

### Changed
- Updated `barrelstrength/sprout-base-email` requirement to v1.2.1

### Fixed
- Fixed bug where viewContext may not be defined correctly when loading Mailing Lists modal

## 4.1.1 - 2020-02-05

{note}: This update migrates recipient emails in some conditions from the cc and bcc fields to the standard recipients field. Please review your recipients after updating and ensure they are working as expected.

### Added
- Added Mailing List Reports integration and Lists relations setting
- Added `barrelstrength\sproutbaseemail\mailers\DefaultMailer::hasLists()`
- Added `barrelstrength\sproutbaseemail\elements\NotificationEmail::$sendMethod`

### Changed
- Updated `barrelstrength\sproutbaseemail\base\RecipientTrait::hasLists()` default to `false`
- Updated `barrelstrength/sprout-base-email` requirement to v1.2.0
- Updated `barrelstrength/sprout-base-fields` requirement to v1.3.0
- Updated `barrelstrength/sprout-base` requirement to v5.1.0

### Removed
- Removed `barrelstrength\sproutbaseemail\base\EmailElement::$singleEmail`
- Removed `barrelstrength/sprout-base-lists` requirement (use Mailing List Reports)

## 4.0.6 - 2019-11-19

### Added
- Added `Cleanup Probability` setting to control the frequency Sent Email cleanup tasks are triggered

### Changed
- Removed Subject Line from Basic Email Templates body ([#124])
- Updated barrelstrength/sprout-base requirement v5.0.8
- Updated barrelstrength/sprout-base-email requirement v1.1.6

### Removed
- Removed `DeleteSentEmails` job in favor of `PurgeElements` job 

[#124]: https://github.com/barrelstrength/craft-sprout-email/issues/124

## 4.0.5 - 2019-08-16

### Changed
- Updated barrelstrength/sprout-base requirement v5.0.7
- Updated barrelstrength/sprout-base-email requirement v1.1.5

### Fixed
- Fixed issue when running migrations via console command ([#321][#321sproutforms])

[#321sproutforms]: https://github.com/barrelstrength/craft-sprout-forms/issues/321

## 4.0.4 - 2019-07-16

### Added
- Added Integration Send Rule setting for fine-grained control over Notification Email logic

### Changed
- Improves Notification Email integration support
- Updated barrelstrength/sprout-base-email requirement v1.1.2

## 4.0.3 - 2019-06-28

### Changed
- Updated barrelstrength/sprout-base-email requirement to v1.1.1

## Fixed
- Fixed bug when previewing a Notification Email ([#119])
- Fixed bug where deleting notification redirected to incorrect URL ([#294][#294-sproutforms])

[#119]: https://github.com/barrelstrength/craft-sprout-email/issues/119
[#294-sproutforms]: https://github.com/barrelstrength/craft-sprout-forms/issues/294

## 4.0.2 - 2019-06-11

### Changed
- Updated barrelstrength/sprout-base-email requirement to v1.1.0

### Fixed
- Fixed issue where some Notification Emails would not get triggered 

## 4.0.1 - 2019-06-10

### Added
- Added Markdown support for Notification Email Default Body field

### Changed
- Updated barrelstrength/sprout-base-email requirement to v1.0.9

### Fixed
- Fixed bug where new Notifications could throw error if Notification Event was not set ([#283][#283forms])

[#283forms]: https://github.com/barrelstrength/craft-sprout-forms/issues/283

## 4.0.0 - 2019-05-29

### Added
- Added 'When a user is activated' Notification Event

### Fixed
- Fixed bug where 'When a user is saved' Notification Event does not trigger notification ([#113])
- Fixed issue when switching notification Notification Event without settings ([#117])

[#113]: https://github.com/barrelstrength/craft-sprout-email/issues/113
[#117]: https://github.com/barrelstrength/craft-sprout-email/issues/117

## 4.0.0-beta.18 - 2019-04-20

### Changed 
- Updated barrelstrength/sprout-lists requirement to v1.0.4

## 4.0.0-beta.17 - 2019-04-20

### Changed 
- Updated barrelstrength/sprout-lists requirement to v1.0.3

## 4.0.0-beta.16 - 2019-04-20

### Changed 
- Updated barrelstrength/sprout-base-email requirement to v1.0.6
- Updated barrelstrength/sprout-base-fields requirement to v1.0.4
- Updated barrelstrength/sprout-base requirement v5.0.0

### Fixed
- Improved Postgres support
- Fixed javascript error on Internet Explorer

## 4.0.0-beta.15 - 2019-04-09

### Updated
- Updated barrelstrength/sprout-lists requirement v1.0.3

### Fixed
- Required Sprout Lists to be installed to use User Sync

## 4.0.0-beta.14 - 2019-03-22

### Changed 
- Updated barrelstrength/sprout-base-email requirement to v1.0.5
- Updated barrelstrength/sprout-base requirement v4.0.8

### Fixed
- Fixed bug where Sprout Lists integration was not being recognized ([#106])
- Fixed conflict between Sent Email logging and Contact Form plugin ([#107])
- Fixed a bug where User Save did not trigger when Select All checkbox was selected ([#108])

[#106]: https://github.com/barrelstrength/craft-sprout-email/issues/106
[#107]: https://github.com/barrelstrength/craft-sprout-email/issues/107
[#108]: https://github.com/barrelstrength/craft-sprout-email/issues/108

## 4.0.0-beta.13 - 2019-03-19

### Changed
- Improved performance of several Element queries
- Improved support for Email integrations
- Updated barrelstrength/sprout-base-email requirement to v1.0.4

### Fixed
- Fixed bug where Settings model was not available for Email integrations

## 4.0.0-beta.12 - 2019-03-18

### Added
- Added additional permissions support

### Changed
- Updated settings to require Admin permission to edit
- Updated barrelstrength/sprout-base-email requirement v1.0.3
- Updated barrelstrength/sprout-base requirement v4.0.7

## 4.0.0-beta.11 - 2019-02-18

### Changed
- Updated barrelstrength/sprout-base-email requirement v1.0.1

### Fixed
- Fixed false positives that could occur with Notification Email validation ([#100])

[#100]: https://github.com/barrelstrength/craft-sprout-email/issues/100

## 4.0.0-beta.10 - 2019-02-13

### Added
- Added support for Live Preview in Craft 3.1
- Added support for validating whether Notification Event variables exist a Notification Email's selected Email Templates ([#90])

### Changed
- Improved support for Notification Event validation and errors for recipients and other settings ([#90])
- Improved translation support
- Updated settings to implement SproutSettingsInterface
- Updated barrelstrength/sprout-base requirement to v4.0.6
- Added barrelstrength/sprout-base-email requirement v1.0.0
- Added barrelstrength/sprout-base-fields requirement v1.0.0
- Added barrelstrength/sprout-lists requirement v1.0.0

### Fixed
- Fixed undefined property error on User Delete Notification Event
- Fixed validation error not displaying and throwing up error 
- Fixed bug error validation on saving notification (sprout base)
- Fixed bug send test throws error when notification recipients is blank ([#94])

[#90]: https://github.com/barrelstrength/craft-sprout-email/issues/90
[#94]: https://github.com/barrelstrength/craft-sprout-email/issues/94

## 4.0.0-beta.9 - 2019-01-25

### Added
- Added initial support for Craft 3.1

### Changed
- Updated Craft CMS requirement to v3.1.0
- Updated Sprout Base requirement to v4.0.5

## 4.0.0-beta.8 - 2019-01-23

### Changed
- Updated Mailer to support displayName, hasSender, and hasRecipients 
- Refactored EmailElement and Mailer class
- Updated template errors to be captured on Email Element model
- Updated ListsTrait => RecipientsTrait and removed from EmailElement class
- Improved logic in Install migration

### Fixed
- Updated EntriesSave Notification Event to only trigger in live scenarios ([#76])
- Updated EntriesSave Notification Event to only trigger enabled entries. ([#79])
- Fixed issue migration could fail in some scenarios due to duplicate pluginHandle column ([#219][#219forms])
- Fixed issue where getSegment was used in a console request ([#207][#207forms])
- Fixed Subject Line output in test modal
- Fixed bug on notification email where the object asset field does not attach  to the email message
- Fixed error when sending Notification Email using getAssetFilePath
- Added migration to ensure Notification Email listSettings column exists

[#76]: https://github.com/barrelstrength/craft-sprout-email/issues/76
[#79]: https://github.com/barrelstrength/craft-sprout-email/issues/79
[#207forms]: https://github.com/barrelstrength/craft-sprout-forms/issues/207
[#219forms]: https://github.com/barrelstrength/craft-sprout-forms/issues/219

## 4.0.0-beta.7 - 2018-11-13

> {tip} Upgrade to enjoy the new Sent Email Element Type! Sent Emails keep a copy of each email sent through Craft, give you more insight when troubleshooting deliverability, and make it easy to follow up and resend any email previously sent.

### Added
- Added Sent Email Element Type
- Added support limiting the number of Sent Emails saved to the database
- Added Sent Email migration for Craft 2 to Craft 3 upgrade

### Changed
- Renamed default Email Templates from _Basic Email => Basic Notification
- Updates Notification Email layout and instructional text 
- Updated Sprout Base requirement to v4.0.2

### Fixed
- Fixed issue where no line breaks were added to the default Notification Email template
- Fixed error when all User Groups were selected for 'When a User is saved' event
- Fixed error when using Live Preview or Share options with a 'When a User is saved' event is selected

## 4.0.0-beta.6 - 2018-10-29

### Changed
- Updated Sprout Base requirement to v4.0.0

## 4.0.0-beta.5 - 2018-10-26

### Added
- Added support for CC and BCC recipients on Notification Emails.

### Changed
- Updated Sprout Base requirement to v3.0.10

### Fixed
- Fixed various issues in Notification Email migrations from Craft 2 

## 4.0.0-beta.4 - 2018-10-23

### Added
- Added EVENT_SEND_NOTIFICATION_EMAIL that is triggered when a Notification Email is sent

### Changed
- Updated Notifications to render object variables in Live Preview and via Share links ([#52])
- Updated Sprout Base requirement to v3.0.8

### Fixed
- Fixed bug where Notification would send when status was disabled ([#54])
- Fixed bug that could create missing `emailTemplateId` in migration from Craft 2 to Craft 3 ([#53])

[#52]: https://github.com/barrelstrength/craft-sprout-email/issues/52
[#53]: https://github.com/barrelstrength/craft-sprout-email/issues/53
[#54]: https://github.com/barrelstrength/craft-sprout-email/issues/54

## 4.0.0-beta.3 - 2018-07-26

## Changed
- Updated Sprout Base requirement to v3.0.0

## 4.0.0-beta.2 - 2018-07-26

## Added
- Added Sprout Email to Packagist

## 4.0.0-beta.1 - 2018-07-26

## Added
- Reduced steps required to setup and send new Notification Emails
- Added Email Templates API to manage custom email templates
- Added 'Basic Email' Email Templates integration which supports the default new Notification Email layout
- Added default Body field to Notification Emails
- Added Single Email setting to send a single email to a list of multiple recipients as you would with your mail client 
- Added Manual Notification Event to set a Notification Email to only be triggered manually
- Added support for using Email Notifications in a separate plugin without needing to install Sprout Email  
- Added support for Notification Events in Modules
- Added EmailElement Class and refactored NotificationEmail and CampaignEmail Elements to extend EmailElement

## Changed
- Added support for alternate extensions when overriding email.html (email.txt must still use .txt extension)
- Text Email templates are now optional and will be generated using HTMLtoMarkdown if no Text template is provided
- Notification Email Field Layout is now optional if a default Body field is in use
- Updated recipients to be handled via RecipientLists and SimpleRecipient models
- Refactored Mailer API
- Refactored NotificationEvent API
- Refactored error handling

## Fixed
- Fixed bug where Sender fields would not validate if using dynamic values ([#124])

[#124]: https://github.com/barrelstrength/craft-sprout-forms/issues/124

## 3.0.7 - 2018-05-24

### Fixed
- Added doesTemplateExists method for Mailers to help show improved error messages
- Fixed bug where Default Mailer did not save default settings
- Fixed bug when generating mock form submission data where an incorrect form could be returned
- Fixed issue where opening preview modal could sometimes throw JS errors which disabled the html and text tabs

## 3.0.6 - 2018-01-11

### Changed
- Added note on where to download supported Sprout Email Mailers

### Fixed
- Fixed issue where PHP warnings would display in dev environments
- Fixed a potential incompatibility with various plugin integrations
- Fixed a bug where Text Preview did no work for Sent Emails

## 3.0.4 - 2017-11-16

### Fixed
- Fixed bug where Sprout Email assumes Sprout Lists exists
- Fixed bug where &#039;Resend Email&#039; for Sent Emails throws an error

## 3.0.3 - 2017-10-18

- _Sprout Email 3 comes with many new features and requires manual steps during the upgrade process. Please see [Upgrading from Sprout Email 2.x to 3.x](https://sprout.barrelstrengthdesign.com/craft-plugins/email/docs/getting-started/upgrading-from-2-x-to-3-x)_

### Added
- Added Sent, Scheduled, Pending, and Disabled statuses
- Added support for Live and Test Campaign Email workflows
- Added Date Sent column to Campaign Emails
- Added Date Scheduled column to Campaign Emails (hidden config required)
- Added &#039;Mark as Sent&#039; as a bulk action
- Added &#039;Mark as Unsent&#039; as a bulk action
- Added ability to update Campaign Email Status with bulk action
- Added support for Content and Recipient columns to help indicate if an email is ready to send
- Added Notification Name to the list of columns for Element Index view and sortable columns
- Added ability for Mailers to integrate with Sprout Lists
- Added &#039;Mark as Sent&#039; option to Copy/Paste Email workflow
- Added ability to manage Mailer settings via `general.php`
- Added Sent Email support for Contact Form plugin

### Changed
- Updated Notification workflow modals to better match Craft styles
- Updated HTML and Text Preview links for Campaigns and Notifications to open in new window
- Updated Campaign Title to behave like Craft Section Title Format
- Updated Sent Email Element Index page to use Element Index API
- `template` column output on Campaign Element Index Page
- Updated Notifications to have HTML and Text columns
- Copy/Paste modal now copies content directly to clipboard
- Updated Mailer settings area to link to each respective Mailer Plugin settings area
- Removed third-party mailers MailChimp and Campaign Monitor and makes them available as separate plugins
- Removed extra step necessary to &#039;Install Mailers&#039;
- Removed Recipient Lists functionality in favor of new Sprout Lists and List Type integrations
- Removed sent column from Campaign Email and Notification Email schema
- Removed Incomplete Status
- Deprecated `entry` variable in templates. Use `email` instead.
- Deprecated `notification` variable in templates. Use `email` instead.
- Deprecated `campaign` variable in templates. Use `campaignType` instead.
- Deprecated SproutEmailBaseEvent::getId(). Use SproutEmailBaseEvent::getEventAction() instead.

### Fixed
- Fixed bug where deleting a single email via a bulk action could delete other emails too, depending on the status
- Fixed bug where saving notification settings with Cmd+S would overwrite the Email Subject with the Notification Name
- Fixed bug where attachments did not get attached to sprout email notifications
- Fixed bug where field values were not retained after Notification Email failed validation
- Fixed bug when previewing Notification emails if no Event is set
- Fixed bug where tabs did not display on Campaign Email edit page
- Fixed bug where Sprout Email throws an error when used alongside the Contact Form plugin
- Fixed broken link on Sent Email index page when the site uses a different locale
- Fixed bug with Sprout Email Sent Email format in some scenarios

## 2.4.7 - 2017-01-11

### Changed
- Optimized performance on Sent Emails Element Index page

### Fixed
- Fixed a bug where some field values were not retained when validation failed
- Fixed a bug when previewing Notification Emails when no Event was set

## 2.4.6 - 2016-11-21

### Fixed
- Fixed reference to deprecated `craft()-&gt;getBuild()` method introduced in Craft 2.6.2951

## 2.4.5 - 2016-11-09

### Changed
- Improved the default settings for the htmlBody column
- Improved the way that the logs are handled

### Fixed
- Fixed a bug where updating from v2.3.0 could cause an error
- Fixed a bug where the default example &#039;Campaign Type&#039; could not be deleted

## 2.4.4 - 2016-10-20

### Fixed
- Fixed a bug where &quot;From&quot; and &quot;Reply To&quot; and &quot;Recipients&quot; fields do not validate dynamic values

## 2.4.3 - 2016-10-10

### Fixed
- Fixed a bug where &quot;From&quot; and &quot;Reply To&quot; fields do not validate dynamic values like {email}
- Fixed a bug where the Sent Email Element Index table changed formatting after deleting items
- Fixed a bug where Notification Emails threw a PHP error under certain conditions
- Fixed an issue that could occur when loading some custom event integrations
- Fixed broken settings link on prepare modal window
- Fixed compatibility issue for servers using PHP 5.3

## 2.4.2 - 2016-09-16

### Fixed
- Fixed an error occurring on servers running PHP 5.5 and below

## 2.4.1 - 2016-09-16

### Fixed
- Fixed issues that caused the Notification page to not load properly

## 2.4.0 - 2016-09-01

### Added
- Added Notification Email and Campaign Email Element Types which replace the Sprout Email Entry Element Type
- Added settings to enable or disable Campaigns, Notifications, Sent Emails, and Recipient Lists
- Added validation for From Name, From Email, and Reply To Email in Notification Settings

### Changed
- Updated flow of Notification Email edit and settings page. Removed Notification Settings page which is now accessible from the Notification Email edit page.
- Updated Sent Emails to default to disabled when first installed
- Updated Recipient Lists to default to disabled when first installed
- Deprecated `entry` variable in email templates for `email`
- Deprecated `campaign` variable in email templates for `campaignType`
- Refactored several areas of codebase
- Refactored naming conventions throughout codebase

## 2.3.0 - 2016-06-08

### Added
- Users can now send test emails to multiple recipients
- Users can now re-send Sent Emails to one or more recipients
- Added Live Preview with mock data for Notification Emails
- Added token-based sharing with mock data for Notification Emails
- Sent Email Element now tracks emails that fail to send
- Added Sent Email Statuses: Sent and Failed

### Changed
- Updated SproutEmailBaseEvent:getUniqueId() =&gt; SproutEmailBaseEvent:getId()
- Save As New Campaign behavior now only appends an incremented number to the slug value
- Various updates to naming conventions and copy

### Fixed
- Fixed error that prevented Sent Email chart from rendering in PHP 5.3
- Fixed various coding conventions that could trigger warnings in earlier versions of PHP

## 2.2.4 - 2016-04-20

### Added
- Added Explorer Chart on Sent Emails Element Index page

### Fixed
- Fixed bug where MailChimp subscriber list count did not reflect proper number of people on list

## 2.2.3 - 2016-04-07

### Changed
- Removed the unused Archived status

### Fixed
- Fixed issue where the Status dropdown would disappear when using bulk actions on the Email index page
- Fixed error which blocked Live Preview from working on new Campaign Entries
- Fixed a bug where Sent Emails did not decode the email subject line (if it was encoded) before saving
- Fixed a bug where saving Campaign Settings did not update the URIs of existing Email Elements to match a new URL Format

## 2.2.2 - 2016-03-29

### Added
- Added Sprout SEO sitemap support for email campaigns with URLs
- Added support for `email` variable in Notification and Campaign templates

### Changed
- Updated naming of column and sorting for `Sent On` to `Send Date`
- Updated Copy/Paste Mailer to trim whitespace around rendered HTML and Text content

### Fixed
- Fixed bug where embed css in a notification template would be processed like Twig

## 2.2.1 - 2016-03-08

### Sent Emails
- Emails sent via Craft and Sprout Email are now captured in the database. Keep track of every email your website sends; search Sent Email by recipient or email title; and view HTML and Text versions of the email to know exactly what was sent.

### Added
- Added Sent Email Element Type
- Admin can view HTML and Text content of Sent Emails
- Admin can search Sent Emails by email title and recipient

### Integrated Mailers
- Sprout Email now comes with four Mailers out of the box: MailChimp, Campaign Monitor, Copy/Paste, and the default Sprout Mailer. Easily enable or disable the Mailers you need for your notification and marketing emails.

### Changed
- Integrated first-party mailers (Copy/Paste, Campaign Monitor, and MailChimp) into Sprout Email Core
- Improved various workflow and user interface styles in Mailer settings
- Improved handling of shorthand `{value}` and `{{ object.value }}` syntaxes in custom fields and templates
- Added Select All checkbox option to Events with settings that could include multiple checkboxes
- Notification Entry now attempts to populate default From Name, From Email, and Reply To values from Sprout Email settings
- Updated Craft Commerce Events to support mock objects for testing
- &#039;When a Craft Commerce order status is changed&#039; event now provides both the Order and Order History model to the email template
- Updated HTML and Text preview buttons on listing page to only display when a Campaign is URL-enabled
- Moved `enableDynamicLists` to a hidden config setting
- Standardized code style and removed various legacy code

## 2.1.3 - 2016-02-04

### Added
- Added support for Craft Commerce notifications for when a transaction is saved, when an order is completed, and when an order status is changed.

### Changed
- Improved error messaging when a template does not exist
- Improved error messaging for test messages when email settings are not properly setup
- Improved title behavior when using &#039;Save as new email&#039;
- Improved encoding of email subject line

### Fixed
- Fixed display issue on Email entries index page after using bulk delete

## 2.0.2 - 2016-01-14

### Added
- Added improved error messaging on Review &amp; Test modal
- Added getCpUrl() method to SproutEmail_EntryModel

### Changed
- Improved workflow and errors around Recipient List creation and managing lists
- Improved error message for SSL bug that could occur when using MailChimp

### Fixed
- Fixed issue where edit permission didn&#039;t restrict acccess to some links to the settings section
- Fixed error that could prevent modal window from closing
- Fixed bug where a Recipient could not be saved to a Recipient List under certain conditions
- Fixed link to settings page

## 2.0.1 - 2015-12-02

### Added
- The entire Control Panel has been updated to work with Craft 2.5
- Added Plugin icon
- Added Plugin description
- Added link to documentation
- Added link to plugin settings
- Added link to release feed
- Added subnav in place of tabs for top level navigation
- Added `editSproutEmailSettings` permission

### Changed
- Improved and standardized display of Sprout plugin info in footer
- Improved layout and workflow of several settings pages
- Improved Campaign and Notification sidebar

## 1.2.3 - 2015-11-21

### Fixed
- Fixed bulk delete behavior where all Campaign entries could be deleted
- Fixed `enableFileAttachments` error that could occur when saving Campaign entries
- Fixed display bug with secondary Save button actions

## 1.2.2 - 2015-11-20

### Added
- Notifications now support File Attachments
- Added &quot;Save as new email&quot; option for Campaign Emails
- Added &quot;Save and add another&quot; option for Campaign Emails
- Added &quot;Save and add another&quot; option for Email Notifications
- Added &quot;Save and edit notification settings&quot; option for Email Notifications
- Added &quot;Save and edit notification email&quot; option for Email Settings
- Creating a new notification now immediately creates it&#039;s respective Notification Email entry

### Changed
- Improved Test Notification workflow and modal window
- Added users email to Test Notification modal window
- Updated example notification templates work for more events
- Improved example notification error handling

### Fixed
- Fixed issue where user could be redirected to page not found after saving a recipient
- Fixed issue that caused Craft console application to throw errors
- Fixed issue where disabled notification emails were still being sent

## 1.2.0 - 2015-09-09

### Added
- Added support for file attachments in notifications
- Added a new config (enableFileAttachments) to enable file attachments

## 1.1.0 - 2015-08-17

### Added
- Added support for MailChimp through separate plugin
- Added the ability to bulk delete entries

### Changed
- Improved entry status handling
- Improved notification and campaign entry preview and review workflows
- Improved overall responses when sending campaigns
- Improved template validation and error reporting
- Improved mailer settings template with proper links

### Fixed
- Fixed issue where user save event would fail due to group checking

## 1.0.6 - 2015-07-13

### Added
- Adds &#039;Sprout Email Examples&#039; Field Group for example fields

### Fixed
- Fixed bug where example fields could get created without a groupId
- Fixed bug where Notification Emails could be deleted without their corresponding Campaigns and Rules

## 1.0.5 - 2015-06-29

### Added
- Added Example Notification and Campaign Emails
- Added validation for Recipient Lists and makes them required for Recipients
- Updated Recipient Lists to display errors

### Changed
- Updated naming conventions of rules options
- Various copy and minor code updates

### Fixed
- Fixed redirect error with Recipient Lists

## 1.0.4 - 2015-06-27

### Added
- Added the ability to specify Save Entry events to trigger on create and/or update
- Added support to trigger notifications when a user is activated
- Added support to trigger notifications when a user is deleted
- Added support to trigger notifications when an entry is deleted
- Added advanced support for twig constructs for on the fly recipients

### Changed
- Updated on the fly recipients to be parsed when triggered instead of when saved
- Improved the save user event rule to trigger when user is created and/or updated
- Improved recipient notifications to make sure no email is sent more than once
- Various design, copy, and code improvements

### Fixed
- Fixed issue with user permissions raised when Sprout Forms is also installed
- Fixed issue triggered on notification entry page when not running Craft Pro

## 1.0.0 - 2015-05-13

### Added
- Commercial Release

## 0.9.2 - 2015-05-07

### Added
- Added dynamic event to trigger when a user is created or when user is updated
- Added `export` action to recipient entries so that recipients in list or all can be exported as CSV
- Added `SproutEmail_UsersSaveUserEvent` to handle `onSaveUser` rules
- Added `SproutEmail_DefaultMailerRecipientElementType::getAvailableActions()`
- Added `SproutEmail_ExportRecipientElementAction` to enable export actions on recipients
- Added the ability to search and filter recipients

### Changed
- Updated recipient index to be a full element index page
- Updated the `recipients.js` file to work with the newly redesigned recipient element index
- Removed `defaultmailer/` segment from `DefaultMailerRecipientModel` edit URL
- Removed route targeting `sproutemail/recipients/{id}`

### Fixed
- Fixed issue where `saveEntry` events wouldn&#039;t trigger if no section ids were selected
- Fixed issue where some notification template variables were being overwritten by element content

## 0.9.1 - 2015-04-24

### Added
- Campaign Emails and Notification Emails are now Element Types
- Campaign Emails and Notification Emails now support Custom Fields
- Campaign Emails and Notification Emails are now searchable
- Campaign Emails and Notification Emails now have custom statuses and can be sorted
- Campaign Emails now support Live Preview and share links
- Default Mailer now supports Lists and Subscribers
- Added support for test Notification emails to use mocked data
- Default Mailer now supports dynamic recipients and variables
- Added Mailer API so Sprout Email can now be extended by other plugins and integrated more easily with third party services (we&#039;ve created additional plugins for Campaign Monitor, MailChimp, and Copy/Paste workflows)
- Added Notification Events API so Sprout Email can now be extended by other plugins who need to trigger notification emails from custom events
- Added support for adding recipients to a Notification at runtime to enable Sprout Email to behave as a transactional email client
- Added `craft.sproutEmail.entries()` tag to display emails in templates
- Notification emails now inherit sender information from setting if no sender information is provided
- Campaign Emails support an optional Entry URL Format
- Added example translation file for US English

### Changed
- Email templates for HTML and Text can now be defined with a single setting
- Added support for sending multiple Notification emails emails per event
- Notification emails can now be disabled

## 0.7.1 - 2014-04-27

### Fixed
- Fixed bug due to onSaveAsset Event method name

## 0.7.0 - 2014-04-15

### Added
- Added support for HTML email notification templates
- Campaign settings can now use submitted values in Subject, Reply To, and Recipient fields using the &#039;entry&#039; variable. For example, {{ entry.fullName }}
- Notification settings can now use the &#039;entry&#039; variable to refer to any values passed to them when the notification is triggered. For example, {{ entry.fullName }}
- Added settings page for Campaign Monitor and Mailchimp API settings
- Added support for notifications to be sent on several new Events: onActivateUser, onUnlockUser, onSuspendUser, onUnsuspendUser, onDeleteUser, onSaveGlobalContent, onSaveAsset, onSaveContent, onSaveTagContent, onBeginUpdate, onEndUpdate

### Changed
- Rewrote Campaign and Notification forms workflow so settings could be saved incrementally at each step in the workflow
- Use Entry Title for Campaign subject
- Updated recipient list delimiter to use a comma and to allow spaces between email addresses
- Updated Campaigns to work with Craft 2.0
- Standardized several naming conventions and cleaned up legacy code syntax
- Various small improvements to UI

### Fixed
- Fixed bug where User Groups were displaying where they shouldn&#039;t
- Fixed css naming conflict introduced by Craft 2.0

## 0.5.1 - 2014-04-08

### Added
- Private Beta
