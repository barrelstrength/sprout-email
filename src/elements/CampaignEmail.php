<?php

namespace barrelstrength\sproutemail\elements;

use barrelstrength\sproutbase\app\email\base\EmailElement;
use barrelstrength\sproutbase\app\email\base\Mailer;
use barrelstrength\sproutbase\SproutBase;
use barrelstrength\sproutbase\app\email\web\assets\email\EmailAsset;
use barrelstrength\sproutemail\elements\db\CampaignEmailQuery;
use barrelstrength\sproutemail\records\CampaignEmail as CampaignEmailRecord;
use barrelstrength\sproutemail\models\CampaignType;
use barrelstrength\sproutemail\SproutEmail;
use Craft;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\DateTimeHelper;
use craft\helpers\UrlHelper;
use yii\base\Exception;

/**
 * Class CampaignEmail
 */
class CampaignEmail extends EmailElement
{
    // Constants
    // =========================================================================

    const READY = 'ready';
    const DISABLED = 'disabled';
    const PENDING = 'pending';
    const SCHEDULED = 'scheduled';
    const SENT = 'sent';

    /**
     * @var bool
     */
    public $id;

    /**
     * @var bool
     */
    public $campaignTypeId;

    /**
     * @var string
     */
    public $emailSettings;

    /**
     * @var
     */
    public $send;

    /**
     * @var
     */
    public $preview;

    /**
     * @var $dateScheduled \DateTime
     */
    public $dateScheduled;

    /**
     * @var $dateSent \DateTime
     */
    public $dateSent;

    /**
     * @var
     */
    public $saveAsNew;

    /**
     * The default email message.
     *
     * This field is only visible when no Email Notification Field Layout exists. Once a Field Layout exists, this field will no longer appear in the interface.
     *
     * @var string
     */
    public $defaultBody;

    /**
     * @return string
     */
    public static function displayName(): string
    {
        return Craft::t('sprout-email', 'Campaign Email');
    }

    /**
     * @return null|string
     */
    public static function refHandle()
    {
        return 'campaignEmail';
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasUris(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function statuses(): array
    {
        return [
            self::DISABLED => Craft::t('sprout-email', 'Disabled'),
            self::PENDING => Craft::t('sprout-email', 'Pending'),
            ///self::SCHEDULED => Craft::t('sprout-email','Scheduled'),
            self::SENT => Craft::t('sprout-email', 'Sent')
        ];
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl()
    {
        return UrlHelper::cpUrl(
            'sprout-email/campaigns/edit/'.$this->id
        );
    }

    /**
     * @return ElementQueryInterface
     */
    public static function find(): ElementQueryInterface
    {
        return new CampaignEmailQuery(static::class);
    }

    /**
     * @param string|null $context
     *
     * @return array
     */
    protected static function defineSources(string $context = null): array
    {
        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('sprout-email', 'All campaigns')
            ]
        ];

        $campaignTypes = SproutEmail::$app->campaignTypes->getCampaignTypes();

        $sources[] = ['heading' => Craft::t('sprout-email', 'Campaigns')];

        foreach ($campaignTypes as $campaignType) {
            $source = [
                'key' => 'campaignTypeId:'.$campaignType->id,
                'label' => Craft::t('sprout-email', $campaignType->name),
                'criteria' => [
                    'campaignTypeId' => $campaignType->id
                ]
            ];

            $sources[] = $source;
        }

        return $sources;
    }

    /**
     * @inheritdoc
     */
    protected static function defineActions(string $source = null): array
    {
        $actions = [];

        // Delete
        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Delete::class,
            'confirmationMessage' => Craft::t('sprout-email', 'Are you sure you want to delete the selected campaign emails?'),
            'successMessage' => Craft::t('sprout-email', 'Campaign emails deleted.'),
        ]);

        return $actions;
    }


    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        $attributes = [
            'subjectLine' => ['label' => Craft::t('sprout-email', 'Subject')],
            'contentCheck' => ['label' => Craft::t('sprout-email', 'Content')],
            'recipientsCheck' => ['label' => Craft::t('sprout-email', 'Recipients')],
            'dateCreated' => ['label' => Craft::t('sprout-email', 'Date Created')],
            'dateSent' => ['label' => Craft::t('sprout-email', 'Date Sent')],
            'send' => ['label' => Craft::t('sprout-email', 'Send')],
            'preview' => ['label' => Craft::t('sprout-email', 'Preview'), 'icon' => 'view'],
            'link' => ['label' => Craft::t('sprout-email', 'Link'), 'icon' => 'world']
        ];

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('sprout-email', 'Title'),
            'elements.dateCreated' => Craft::t('sprout-email', 'Date Created'),
            'elements.dateUpdated' => Craft::t('sprout-email', 'Date Updated'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function beforeSave(bool $isNew): bool
    {
        $campaignTypeId = $this->campaignTypeId;

        $campaignType = SproutEmail::$app->campaignTypes->getCampaignTypeById($campaignTypeId);

        $this->fieldLayoutId = $campaignType->fieldLayoutId;

        return parent::beforeSave($isNew);
    }

    /**
     * @param bool $isNew
     *
     * @throws \Exception
     */
    public function afterSave(bool $isNew)
    {
        // Get the entry record
        if (!$isNew) {
            $record = CampaignEmailRecord::findOne($this->id);

            if (!$record) {
                throw new Exception('Invalid campaign email ID: '.$this->id);
            }
        } else {
            $record = new CampaignEmailRecord();
            $record->id = $this->id;
        }

        $record->subjectLine = $this->subjectLine;
        $record->defaultBody = $this->defaultBody;
        $record->campaignTypeId = $this->campaignTypeId;
        $record->recipients = $this->recipients;
        $record->emailSettings = $this->emailSettings;
        $record->listSettings = $this->listSettings;
        $record->fromName = $this->fromName;
        $record->fromEmail = $this->fromEmail;
        $record->replyToEmail = $this->replyToEmail;
        $record->enableFileAttachments = $this->enableFileAttachments;
        $record->dateScheduled = $this->dateScheduled;
        $record->dateSent = $this->dateSent;

        $record->save(false);

        // Update the entry's descendants, who may be using this entry's URI in their own URIs
        Craft::$app->getElements()->updateElementSlugAndUri($this, true, true);

        parent::afterSave($isNew);
    }

    /**
     * @param ElementQueryInterface $elementQuery
     * @param array|null            $disabledElementIds
     * @param array                 $viewState
     * @param string|null           $sourceKey
     * @param string|null           $context
     * @param bool                  $includeContainer
     * @param bool                  $showCheckboxes
     *
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public static function indexHtml(ElementQueryInterface $elementQuery, array $disabledElementIds = null, array $viewState, string $sourceKey = null, string $context = null, bool $includeContainer, bool $showCheckboxes): string
    {
        $html = parent::indexHtml($elementQuery, $disabledElementIds, $viewState, $sourceKey, $context, $includeContainer,
            $showCheckboxes);

        Craft::$app->getView()->registerAssetBundle(EmailAsset::class);
        Craft::$app->getView()->registerJs('var sproutModalInstance = new SproutModal(); sproutModalInstance.init();');
        SproutBase::$app->mailers->includeMailerModalResources();

        return $html;
    }

    /**
     * @param string $attribute
     *
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function getTableAttributeHtml(string $attribute): string
    {
        $campaignType = SproutEmail::$app->campaignTypes->getCampaignTypeById($this->campaignTypeId);

        $passHtml = '<span class="success" title="'.Craft::t('sprout-email', 'Passed').'" data-icon="check"></span>';
        $failHtml = '<span class="error" title="'.Craft::t('sprout-email', 'Failed').'" data-icon="error"></span>';

        if ($attribute === 'send') {
            $mailer = $campaignType->getMailer();

            return Craft::$app->getView()->renderTemplate('sprout-base-email/_components/elementindex/CampaignEmail/prepare-link', [
                'campaignEmail' => $this,
                'campaignType' => $campaignType,
                'mailer' => $mailer
            ]);
        }

        if ($attribute === 'preview') {
            return Craft::$app->getView()->renderTemplate('sprout-base-email/_components/elementindex/CampaignEmail/preview-links', [
                'email' => $this,
                'campaignType' => $campaignType,
                'type' => 'html'
            ]);
        }

        if ($attribute === 'template') {
            return '<code>'.$this->template.'</code>';
        }

        if ($attribute === 'contentCheck') {
            return $this->isContentReady() ? $passHtml : $failHtml;
        }

        if ($attribute === 'recipientsCheck') {
            return $this->isListReady() ? $passHtml : $failHtml;
        }

        $formatter = Craft::$app->getFormatter();

        if ($attribute === 'dateScheduled') {
            return '<span title="'.$formatter->asDatetime($this->dateScheduled, 'l, d F Y, h:ia').'">'.
                $formatter->asDatetime($this->dateCreated, 'l, d F Y, h:ia').'</span>';
        }

        if ($attribute === 'dateSent' && $this->dateSent) {
            return '<span title="'.$formatter->asDatetime($this->dateSent, 'l, d F Y, h:ia').'">'.
                $formatter->asDatetime($this->dateSent, 'l, d F Y, h:ia').'</span>';
        }

        return parent::getTableAttributeHtml($attribute);
    }

    /**
     * @return bool
     * @throws Exception
     * @throws \Twig_Error_Loader
     */
    public function isContentReady(): bool
    {
        $campaignType = $this->getCampaignType();

        // todo: update recipient info to be dynamic
        $params = [
            'email' => $this,
            'campaignType' => $campaignType,
            'recipient' => [
                'firstName' => 'First',
                'lastName' => 'Last',
                'email' => 'user@domain.com'
            ]
        ];

        $this->setEventObject($params);

        $htmlBody = $this->getEmailTemplates()->getHtmlBody();

        return !($htmlBody == null);
    }

    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public function rules()
    {
        $rules = parent::rules();

        $rules[] = [['subjectLine', 'fromName', 'fromEmail', 'replyToEmail'], 'required'];
        $rules[] = ['replyToEmail', 'validateEmailWithOptionalPlaceholder'];
        $rules[] = ['fromEmail', 'validateEmailWithOptionalPlaceholder'];
        $rules[] = ['recipients', 'validateOnTheFlyRecipients'];

        return $rules;
    }


    /**
     * Ensures that $attribute is a valid email address or a placeholder to be parsed later
     *
     * @param $attribute
     */
    public function validateEmailWithOptionalPlaceholder($attribute)
    {
        $value = $this->{$attribute};
        // Validate only if it is not a placeholder and it is not empty
        if (strpos($value, '{') !== 0 &&
            !empty($this->{$attribute}) &&
            !filter_var($value, FILTER_VALIDATE_EMAIL)) {

            $this->addError($attribute, Craft::t('sprout-email', '{attribute} is not a valid email address.', [
                'attribute' => ($attribute == 'replyToEmail') ? Craft::t('sprout-email', 'Reply To') : Craft::t('sprout-email', 'From Email'),
            ]));
        }
    }

    /**
     * Ensures that all email addresses in recipients are valid
     *
     * @param $attribute
     */
    public function validateOnTheFlyRecipients($attribute)
    {
        $value = $this->{$attribute};

        if (is_array($value) && count($value)) {
            foreach ($value as $recipient) {
                if (strpos($recipient, '{') !== 0 &&
                    !empty($this->{$attribute}) &&
                    !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {

                    $this->addError($attribute, Craft::t('sprout-email', 'All recipients must be placeholders or valid email addresses.', [
                        'attribute' => $attribute,
                    ]));
                }
            }
        }
    }

    /**
     *  Determine if this Campaign Email has lists that it will be sent to     *
     *
     * @return bool
     */
    public function isListReady()
    {
        /**
         * @var $mailer Mailer
         */
        $mailer = $this->getMailer();

        if ($mailer AND $mailer->hasLists()) {
            $listSettings = json_decode($this->listSettings);

            if (empty($listSettings->listIds)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return Mailer|null
     */
    public function getMailer()
    {
        $campaignType = SproutEmail::$app->campaignTypes->getCampaignTypeById($this->campaignTypeId);

        return $campaignType->getMailer();
    }

    /**
     * @return CampaignType
     */
    public function getCampaignType()
    {
        return SproutEmail::$app->campaignTypes->getCampaignTypeById($this->campaignTypeId);
    }

    /**
     * @return null|string
     */
    public function getStatus()
    {
        $status = parent::getStatus();

        if ($status == Element::STATUS_ENABLED) {
            $currentTime = DateTimeHelper::currentTimeStamp();
            $dateScheduled = $this->dateScheduled !== null ? $this->dateScheduled->getTimestamp() : null;

            if ($this->dateSent != null) {
                return static::SENT;
            }

            if ($this->dateScheduled != null && $dateScheduled > $currentTime && $this->dateSent == null) {
                return static::SCHEDULED;
            }

            return static::PENDING;
        }

        return $status;
    }

    /**
     * @return null|string
     */
    public function getUriFormat()
    {
        $campaignType = SproutEmail::$app->campaignTypes->getCampaignTypeById($this->campaignTypeId);

        if ($campaignType && $campaignType->hasUrls) {
            return $campaignType->urlFormat;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $names = parent::datetimeAttributes();
        $names[] = 'dateCreated';

        return $names;
    }

    /**
     * Determine if this Campaign Email is ready to be sent
     *
     * @return bool
     * @throws \yii\base\Exception
     */
    public function isReadyToSend()
    {
        return (bool)($this->getMailer() && $this->isContentReady() && $this->isListReady());
    }

    /**
     * Determine if this Campaign Email is ready to be sent
     *
     * @return bool
     * @throws \yii\base\Exception
     */
    public function isReadyToTest()
    {
        return (bool)($this->getMailer() && $this->isContentReady());
    }

    /**
     * @return array|bool|mixed
     */
    protected function route()
    {
        $campaignType = SproutEmail::$app->campaignTypes->getCampaignTypeById($this->campaignTypeId);

        if (!$campaignType) {
            return false;
        }

        $extension = null;

        if ($type = Craft::$app->getRequest()->getParam('type')) {
            $extension = in_array(strtolower($type), ['txt', 'text'], false) ? '.txt' : null;
        }

        if (!Craft::$app->getView()->doesTemplateExist($campaignType->template.$extension)) {
            $templateName = $campaignType->template.$extension;

            SproutEmail::error(Craft::t('sprout-email', "The template '{templateName}' could not be found", [
                'templateName' => $templateName
            ]));
        }

        return [
            'templates/render', [
                'template' => $campaignType->template.$extension,
                'variables' => [
                    'email' => $this,
                    'entry' => $this,
                    'campaignType' => $campaignType,
                ]
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function getEmailTemplateId()
    {
        $campaignType = $this->getCampaignType();

        return $campaignType->emailTemplateId;
    }
}