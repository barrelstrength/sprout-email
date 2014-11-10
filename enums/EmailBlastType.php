<?php
namespace Craft;

/**
 * The EmailBlastType class is an abstract class that defines the different Email Blast types available in Sprout Email.
 */
abstract class EmailBlastType extends BaseEnum
{
	// Constants
	// =========================================================================

	const EmailBlast   = 'blast';
	const Notification = 'notification';
}
