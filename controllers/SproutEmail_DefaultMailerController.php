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
}
