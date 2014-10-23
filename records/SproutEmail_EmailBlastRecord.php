<?php
namespace Craft;

class SproutEmail_EmailBlastRecord extends BaseRecord
{
	/**
	 * Return table name
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'sproutemail_emailblasts';
	}
	
	/**
	 * Define attributes
	 *
	 * @return array
	 */
	// public function defineAttributes()
	// {
	// 	return array();
	// }
	
	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'element' => array(static::BELONGS_TO, 'ElementRecord', 'id', 'required' => true, 'onDelete' => static::CASCADE),
			'emailBlastType' => array(static::BELONGS_TO, 'SproutEmail_EmailBlastTypeRecord', 'required' => true, 'onDelete' => static::CASCADE),
		);
	}
}
