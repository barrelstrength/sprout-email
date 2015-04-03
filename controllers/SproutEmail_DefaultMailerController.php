<?php
namespace Craft;

class SproutEmail_DefaultMailerController extends BaseController
{
	public function actionShowEditRecipientTemplate(array $variables = array())
	{
		$variables['title']              = 'Recipient';
		$variables['recipientListsHtml'] = null;

		if (isset($variables['id']))
		{
			$variables['element']            = craft()->elements->getElementById($variables['id']);
			$variables['recipientListsHtml'] = sproutEmailDefaultMailer()->getRecipientListsHtml($variables['element']->getRecipientListIds());
		}
		else
		{
			$variables['title']              = 'New Recipient';
			$variables['element']            = new SproutEmail_DefaultMailerRecipientModel();
			$variables['recipientListsHtml'] = sproutEmailDefaultMailer()->getRecipientListsHtml();
		}

		$variables['recipientLists']     = sproutEmailDefaultMailer()->getRecipientLists();
		$variables['continueEditingUrl'] = isset($variables['id']) ? 'sproutemail/defaultmailer/recipients/edit/'.$variables['id'] : null;

		$this->renderTemplate('sproutemail/defaultmailer/recipients/_edit', $variables);
	}

	public function actionShowIndexRecipientTemplate()
	{
		$recipientLists = sproutEmailDefaultMailer()->getRecipientLists();

		$variables = array(
			'title'                  => 'Recipients',
			'elementType'            => 'SproutEmail_DefaultMailerRecipient',
			'recipientLists'         => $recipientLists,
			'recipientListsHtml'     => sproutEmailDefaultMailer()->getRecipientListsHtml($recipientLists),
			'selectedRecipientLists' => array(),
		);

		$this->renderTemplate('sproutemail/defaultmailer/recipients/_index', $variables);
	}

	public function actionShowEditRecipientListTemplate(array $variables = array())
	{
		if (isset($variables['id']) && is_numeric($variables['id']))
		{
			$model = sproutEmailDefaultMailer()->getRecipientListById($variables['id']);

			if (!$model)
			{
				throw new Exception(Craft::t('Recipient list with id ({id}) was not found.', array('id' => $variables['id'])));
			}
		}
		else
		{
			$model = new SproutEmail_DefaultMailerRecipientListModel();
		}

		$variables['recipientList']      = $model;
		$variables['continueEditingUrl'] = isset($variables['id']) ? 'sproutemail/defaultmailer/recipients/edit/'.$variables['id'] : null;

		$this->renderTemplate('sproutemail/defaultmailer/recipientlists/_edit', $variables);
	}

	public function actionShowIndexRecipientListTemplate(array $variables = array())
	{
		$variables = array(
			'recipientLists' => sproutEmailDefaultMailer()->getRecipientLists(),
		);

		$this->renderTemplate('sproutemail/defaultmailer/recipientlists/_index', $variables);
	}

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

		$model->setAttributes(craft()->request->getPost('recipient'));

		if ($model->validate() && sproutEmailDefaultMailer()->saveRecipient($model))
		{
			craft()->userSession->setNotice('Recipient saved successfully.');

			$this->redirectToPostedUrl($model);
			craft()->end();
		}

		craft()->userSession->setError('Unable to save recipient.');
		craft()->urlManager->setRouteVariables(array('recipient' => $model));
	}

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

		$model->setAttributes(craft()->request->getPost('recipientList'));
		$model->setAttribute('handle', ElementHelper::createSlug($model->getAttribute('name')));

		if ($model->validate() && sproutEmailDefaultMailer()->saveRecipientList($model))
		{
			craft()->userSession->setNotice('Recipient list saved successfully.');

			$this->redirectToPostedUrl($model);
		}

		craft()->userSession->setError('Unable to save recipient list.');
		craft()->urlManager->setRouteVariables(array('recipientList' => $model));
	}
}
