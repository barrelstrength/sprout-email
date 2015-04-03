<?php
namespace Craft;

/**
 * Class SproutEmail_DefaultMailerRecipientModel
 *
 * @package Craft
 *
 * @property int                                           $id
 * @property string                                        $email
 * @property string                                        $firstName
 * @property string                                        $lastName
 * @property SproutEmail_DefaultMailerRecipientListModel[] $recipientLists
 */
class SproutEmail_DefaultMailerRecipientModel extends BaseElementModel
{
	/**
	 * @var SproutEmail_DefaultMailerRecipientListModel[]
	 */
	protected $recipientListsIds;

	public function defineAttributes()
	{
		$defaults = array(
			'id'             => AttributeType::Number,
			'email'          => array(AttributeType::Email, 'required' => true),
			'firstName'      => array(AttributeType::String, 'required' => false),
			'lastName'       => array(AttributeType::String, 'required' => false),
			'recipientLists' => AttributeType::Mixed,
		);

		return array_merge(parent::defineAttributes(), $defaults);
	}

	/**
	 * The element type that this model is associated with
	 *
	 * @var string
	 */
	protected $elementType = 'SproutEmail_DefaultMailerRecipient';

	/**
	 * Returns the product title when used in string context
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->email;
	}

	public function isEditable()
	{
		return true;
	}

	/**
	 * Returns the control panel edit URL
	 *
	 * @return false|string
	 */
	public function getCpEditUrl()
	{
		return UrlHelper::getCpUrl('sproutemail/defaultmailer/recipients/edit/'.$this->id);
	}

	/**
	 * @return SproutEmail_DefaultMailerRecipientListModel[]|null
	 */
	public function getRecipientLists()
	{
		return sproutEmailDefaultMailer()->getRecipientListsByRecipientId($this->id);
	}

	/**
	 * @return array
	 */
	public function getRecipientListIds()
	{
		if (is_null($this->recipientListsIds))
		{
			$this->recipientListsIds = array();
			$recipientLists          = $this->getRecipientLists();

			if (count($recipientLists))
			{
				foreach ($recipientLists as $list)
				{
					$this->recipientListsIds[] = $list->id;
				}
			}
		}

		return $this->recipientListsIds;
	}
}
