<?php
namespace Craft;

/**
 * The Campaign class is an abstract class that defines the different Campaigns available in Sprout Email.
 */
abstract class Campaign extends BaseEnum
{
	// Constants
	// =========================================================================

	const Email        = 'email';
	const Notification = 'notification';
}
