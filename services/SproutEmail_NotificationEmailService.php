<?php
namespace Craft;

/**
 * The NotificationEmail API service layer
 *
 * Class SproutEmail_NotificationsService
 *
 * @package Craft
 */
class SproutEmail_NotificationEmailService extends BaseApplicationComponent
{
	public function getNotificationByVariables($variables, $session = null, $elementService = null)
	{
		$notification = null;

		if (isset($variables['notification']))
		{
			$notification = $variables['notification'];
		}
		elseif (isset($variables['notificationId']))
		{
			$notificationId = $variables['notificationId'];

			if ($elementService == null)
			{
				$elementService = craft()->elements;
			}
			$notification = $elementService->getElementById($notificationId);
		}
		elseif ($session != null)
		{
			$notification = unserialize($session);
		}
		else
		{
			$notification = new SproutEmail_NotificationEmailModel();
		}

		return $notification;
	}

	public function saveNotification(SproutEmail_NotificationEmailModel $notification)
	{
		$isNewEntry  = true;
		$record = new SproutEmail_NotificationEmailRecord();

		if (!empty($notification->id))
		{
			$record = SproutEmail_NotificationEmailRecord::model()->findById($notification->id);

			$isNewEntry  = false;
			if (!$record)
			{
				throw new Exception(Craft::t('No entry exists with the ID “{id}”', array('id' => $notification->id)));
			}
		}
		else
		{
			$record->subjectLine = $notification->subjectLine;
		}

		if (!empty($notification->getAttributes()))
		{
			foreach ($notification->getAttributes() as $handle => $value)
			{
				$record->setAttribute($handle, $value);
			}
		}

		if ($record->validate())
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

			$fieldLayout = $notification->getFieldLayout();



			// Assign our new layout id info to our
			// form model and records
			$notification->fieldLayoutId = $fieldLayout->id;
			$record->fieldLayoutId       = $fieldLayout->id;

			try
			{
				if (craft()->elements->saveElement($notification))
				{
					if ($isNewEntry)
					{
						$record->id = $notification->id;
					}

					if($record->save(false))
					{
						if ($transaction && $transaction->active)
						{
							$transaction->commit();
						}

						return $record;
					}
					else
					{
						Craft::dd('errors');
					}
				}
				else
				{
					Craft::dd($notification->getErrors());
				}
			}
			catch (\Exception $e)
			{
				if ($transaction && $transaction->active)
				{
					$transaction->rollback();
				}

				throw $e;
			}
		}
		else
		{
			Craft::dd($record->getErrors());
		}

	}
}
