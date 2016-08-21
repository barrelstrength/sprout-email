<?php
namespace Craft;

/**
 * Class SproutEmail_DefaultMailerController
 *
 * @package Craft
 */
class SproutEmail_DefaultMailerController extends BaseController
{
	/**
	 * @param array $variables
	 *
	 * @throws HttpException
	 */
	public function actionShowRecipientIndexTemplate(array $variables = array())
	{
		$recipientListId = isset($variables['recipientListId']) ? $variables['recipientListId'] : null;
		$recipientLists  = sproutEmailDefaultMailer()->getRecipientLists();
		$recipients      = null;

		if ($recipientListId)
		{
			/**
			 * @var $recipientList SproutEmail_DefaultMailerRecipientListModel
			 */
			$recipientList = sproutEmailDefaultMailer()->getRecipientListById($recipientListId);

			if (!$recipientList)
			{
				throw new HttpException(404);
			}

			$recipients = $recipientList->recipients;
		}
		else
		{
			$recipients = sproutEmailDefaultMailer()->getRecipients();
		}

		$this->renderTemplate('sproutemail/recipients/index', array(
			'recipientListId' => $recipientListId,
			'recipientLists'  => $recipientLists,
			'recipients'      => $recipients
		));
	}

	/**
	 * @param array $variables
	 *
	 * @throws HttpException
	 */
	public function actionShowRecipientEditTemplate(array $variables = array())
	{
		$variables['recipientListsHtml'] = null;
		$defaultRecipientList            = array();

		if (isset($variables['recipient']))
		{
			// When a form doesn't validate, we can use our SproutEmail_DefaultMailerRecipientModel object here
			$variables['element'] = $variables['recipient'];
		}
		else
		{
			if (isset($variables['id']))
			{
				$variables['element'] = sproutEmailDefaultMailer()->getRecipientById($variables['id']);

				if (!$variables['element'])
				{
					throw new HttpException(404);
				}
			}
			else
			{
				$variables['title']   = Craft::t('New Recipient');
				$variables['element'] = new SproutEmail_DefaultMailerRecipientModel();

				$recipientListId = craft()->request->getParam('recipientListId');
				if ($recipientListId != null)
				{
					$defaultRecipientList[] = $recipientListId;
				}
			}
		}

		$recipientListOptions = array();
		$recipientLists       = sproutEmailDefaultMailer()->getRecipientLists();

		if (isset($recipientLists))
		{
			foreach ($recipientLists as $recipientList)
			{
				$recipientListOptions[$recipientList->id] = $recipientList->name;
			}
		}

		$variables['recipientListsHtml'] = sproutEmailDefaultMailer()->getRecipientListsHtml($variables['element'], $defaultRecipientList);

		$variables['recipientLists'] = $recipientLists;

		$variables['recipientListOptions'] = $recipientListOptions;

		$variables['continueEditingUrl'] = isset($variables['id']) ? 'sproutemail/recipients/_edit/' . $variables['id'] :
			null;

		$this->renderTemplate('sproutemail/recipients/_edit', $variables);
	}

	/**
	 * Export Recipients
	 */
	public function actionExportCsv()
	{
		$attributes = craft()->httpSession->get('__exportJob');

		craft()->httpSession->remove('__exportJob');

		$recipients = craft()->elements->getCriteria('SproutEmail_DefaultMailerRecipient', $attributes)->find();

		if ($attributes && $recipients)
		{
			$this->generateCsvExport($recipients);

			craft()->end();
		}

		craft()->userSession->setError(Craft::t('Nothing to export.'));

		craft()->request->redirect(UrlHelper::getCpUrl('sproutemail/recipients'));
	}

	/**
	 * @param SproutEmail_DefaultMailerRecipientModel[] $elements
	 * @param string                                    $filename
	 * @param string                                    $delimiter
	 *
	 * @return bool
	 */
	protected function generateCsvExport(array $elements, $filename = 'recipients.csv', $delimiter = ',')
	{
		header('Content-Type: application/csv');
		header('Content-Disposition: attachment; filename="' . $filename . '";');

		$f = fopen('php://output', 'w');

		foreach ($elements as $element)
		{
			fputcsv(
				$f,
				array(
					$element->firstName,
					$element->lastName,
					$element->email,
				),
				$delimiter
			);
		}

		fclose($f);
	}

	/**
	 * @throws \Exception
	 * @throws Exception
	 * @throws HttpException
	 */
	public function actionSaveRecipient()
	{
		$this->requirePostRequest();

		$id = craft()->request->getPost('id');

		if ($id && is_numeric($id))
		{
			$model = sproutEmailDefaultMailer()->getRecipientById($id);

			if (!$model)
			{
				throw new Exception(Craft::t('Recipient with id ({id}) was not found.', array('id' => $id)));
			}
		}
		else
		{
			$model = new SproutEmail_DefaultMailerRecipientModel();
		}

		$posts = craft()->request->getPost('recipient');
		$model->setAttributes($posts);

		if ($model->validate() && sproutEmailDefaultMailer()->saveRecipient($model))
		{
			craft()->userSession->setNotice(Craft::t('Recipient saved successfully.'));

			$this->redirectToPostedUrl($model);
			craft()->end();
		}

		craft()->userSession->setError(Craft::t('Unable to save recipient.'));
		craft()->urlManager->setRouteVariables(array('recipient' => $model));
	}

	/**
	 * @throws Exception
	 * @throws HttpException
	 */
	public function actionSaveRecipientList()
	{
		$this->requirePostRequest();

		$id = craft()->request->getPost('id');

		if ($id && is_numeric($id))
		{
			$model = sproutEmailDefaultMailer()->getRecipientListById($id);

			if (!$model)
			{
				throw new Exception(Craft::t('Recipient list with id ({id}) was not found.', array('id' => $id)));
			}
		}
		else
		{
			$model = new SproutEmail_DefaultMailerRecipientListModel();
		}

		$name = craft()->request->getPost('name', $model->name);

		$model->setAttribute('name', $name);
		$model->setAttribute('handle', sproutEmail()->createHandle($name));

		if ($model->validate() && sproutEmailDefaultMailer()->saveRecipientList($model))
		{
			craft()->userSession->setNotice(Craft::t('Recipient list saved successfully.'));

			if (craft()->request->isAjaxRequest())
			{
				$this->returnJson(
					array(
						'success' => 'true',
						'list'    => array(
							'id' => $model->id
						)
					)
				);
			}

			$this->redirectToPostedUrl($model);
		}

		craft()->userSession->setError(Craft::t('Unable to save recipient list.'));

		if (craft()->request->isAjaxRequest())
		{
			$this->returnErrorJson(Craft::t('Unable to save recipient list.'));
		}

		craft()->urlManager->setRouteVariables(array('recipientList' => $model));
	}

	/**
	 * @throws Exception
	 * @throws HttpException
	 */
	public function actionDeleteRecipient()
	{
		$this->requirePostRequest();

		$id    = craft()->request->getRequiredPost('id');
		$model = null;

		if (($model = sproutEmailDefaultMailer()->getRecipientById($id)))
		{
			if (!$model)
			{
				throw new Exception(Craft::t('Recipient with id ({id}) was not found.', array('id' => $id)));
			}

			$deleted = SproutEmail_DefaultMailerRecipientRecord::model()->deleteByPk($model->id);

			if ($deleted)
			{
				SproutEmail_DefaultMailerRecipientListRecipientRecord::model()->deleteAllByAttributes(array(
					'recipientId' => $model->id
				));

				craft()->userSession->setNotice(Craft::t('Recipient deleted successfully.'));

				if (craft()->request->isAjaxRequest())
				{
					$this->returnJson(array('success' => true));
				}
			}
			else
			{
				craft()->userSession->setNotice(Craft::t('Unable to delete recipient.'));

				if (craft()->request->isAjaxRequest())
				{
					$this->returnErrorJson(Craft::t('Unable to delete recipient.'));
				}
			}

			$this->redirectToPostedUrl($model);
		}

		throw new HttpException(404);
	}

	public function actionDeleteRecipientList()
	{
		$this->requirePostRequest();

		$id    = craft()->request->getRequiredPost('id');
		$model = null;

		if (($model = sproutEmailDefaultMailer()->getRecipientListById($id)))
		{
			if (!$model)
			{
				throw new Exception(Craft::t('Recipient list with id ({id}) was not found.', array('id' => $id)));
			}

			$deleted = SproutEmail_DefaultMailerRecipientListRecord::model()->deleteByPk($model->id);

			if ($deleted)
			{
				SproutEmail_DefaultMailerRecipientListRecipientRecord::model()->deleteAllByAttributes(array(
					'recipientListId' => $model->id
				));

				craft()->userSession->setNotice(Craft::t('Recipient list deleted successfully.'));

				if (craft()->request->isAjaxRequest())
				{
					$this->returnJson(array(
						'success' => true
					));
				}
			}
			else
			{
				craft()->userSession->setNotice(Craft::t('Unable to delete recipient list.'));

				if (craft()->request->isAjaxRequest())
				{
					$this->returnErrorJson(Craft::t('Unable to delete recipient list.'));
				}
			}

			$this->redirectToPostedUrl($model);
		}

		throw new HttpException(404);
	}
}
