<?php

namespace Craft;


class m160308_000000_sproutEmail_renameReplyTo extends BaseMigration
{
	/**
	 * @return bool
	 */
	public function safeUp()
	{
		if (($table = $this->dbConnection->schema->getTable('{{sproutemail_campaigns_entries}}')))
		{
			if ($table->getColumn('replyTo') != null)
			{
				craft()->db->createCommand()->renameColumn('{{sproutemail_campaigns_entries}}', 'replyTo', 'replyToEmail');

				SproutEmailPlugin::log('Updated sproutemail_campaigns_entries table and renamed column `replyTo` to `replyToEmail`', LogLevel::Info, true);
			}
		}
		return true;
	}
}