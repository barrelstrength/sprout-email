<?php

namespace barrelstrength\sproutemail\elements;

use barrelstrength\sproutbase\SproutBase;
use barrelstrength\sproutbase\app\email\web\assets\email\EmailAsset;
use barrelstrength\sproutemail\elements\actions\DeleteEmail;
use barrelstrength\sproutemail\elements\db\SentEmailQuery;

use barrelstrength\sproutemail\models\SentEmailInfoTable;
use Craft;
use craft\base\Element;
use barrelstrength\sproutemail\records\SentEmail as SentEmailRecord;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use yii\base\Exception;

/**
 * Class SentEmail
 *
 * @package barrelstrength\sproutemail\elements
 */
class SentEmail extends Element
{
    const SENT = 'sent';
    const FAILED = 'failed';

    /**
     * @var bool
     */
    public $saveAsNew;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $emailSubject;

    /**
     * @var bool
     */
    public $enableFileAttachments;

    // Sender Info
    /**
     * @var string
     */
    public $fromName;

    /**
     * @var string
     */
    public $fromEmail;

    /**
     * @var string
     */
    public $toEmail;

    /**
     * @var string
     */
    public $body;

    /**
     * @var string
     */
    public $htmlBody;

    /**
     * @var string
     */
    public $info;

    /**
     * @var string
     */
    public $status;

    /**
     * @var \DateTime
     */
    public $dateSent;

    /**
     * @var array
     */
    protected $fields;

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getLocaleNiceDateTime();
    }

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('sprout-email', 'Campaign Email');
    }

    /**
     * @inheritdoc
     */
    public function getIsEditable(): bool
    {
        return true;
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
                'label' => Craft::t('sprout-email', 'All Sent Emails')
            ]
        ];

        return $sources;
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        $attributes = [
            'dateSent' => ['label' => Craft::t('sprout-email', 'Date Sent')],
            'toEmail' => ['label' => Craft::t('sprout-email', 'Recipient')],
            'emailSubject' => ['label' => Craft::t('sprout-email', 'Subject')],
            'info' => ['label' => Craft::t('sprout-email', 'Details')],
            'resend' => ['label' => Craft::t('sprout-email', 'Resend')],
            'preview' => ['label' => Craft::t('sprout-email', 'Preview'), 'icon' => 'view'],
        ];

        return $attributes;
    }

    /**
     * @return ElementQueryInterface
     */
    public static function find(): ElementQueryInterface
    {
        return new SentEmailQuery(static::class);
    }

    /**
     * @param string $attribute
     *
     * @return string
     */
    public function getTableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'preview':

                $previewUrl = UrlHelper::cpUrl('sprout-email/preview/sent/'.$this->id);

                return '<a class="email-preview" '.
                    'data-email-id="'.$this->id.'" '.
                    'data-preview-url="'.$previewUrl.'" '.
                    'href="'.$previewUrl.'" '.
                    '" data-icon="view"></a>';

                break;
            case 'resend':

                return '<a class="prepare btn small formsubmit" 
                                data-action="sprout-email/sent-email/get-resend-modal" 
                                data-email-id="'.$this->id.'">'.
                    Craft::t('sprout-email', 'Prepare').
                    '</a>';
                break;

            case 'info':

                return '<a class="prepare btn small formsubmit"
                                data-action="sprout-email/sent-email/get-info-html" 
                                data-email-id="'.$this->id.'" 
                                data-type="'.get_class($this).'"
                                data-id="'.$this->id.'"
                                data-site-id="'.$this->siteId.'"
                                data-status="'.$this->getStatus().'"
                                data-label="'.(string)$this.'"
                                data-url="'.$this->getUrl().'"              
                                data-level="'.$this->level.'">'.
                    Craft::t('sprout-email', 'Details').
                    '</a>';
                break;

            default:
        }

        return parent::getTableAttributeHtml($attribute);
    }

//    /**
//     * @return string
//     */
//    public function setHud()
//    {
//        $element = $this;
//
//        $htmlAttributes =
//            [
//                'data-icon' => 'info',
//                'data-type' => get_class($element),
//                'data-id' => $element->id,
//                'data-site-id' => $element->siteId,
//                'data-status' => $element->getStatus(),
//                'data-label' => (string)$element,
//                'data-url' => $element->getUrl(),
//                'data-level' => $element->level,
//                'class' => 'element',
//            ];
//
//
//        $html = '<div';
//
//        foreach ($htmlAttributes as $attribute => $value) {
//            $html .= ' '.$attribute.($value !== null ? '="'.Html::encode($value).'"' : '');
//        }
//
//        if (ElementHelper::isElementEditable($element)) {
//            $html .= ' data-editable';
//        }
//
//        $html .= '>View Info</div>';

        // <div
        // data-icon="info"
        // data-type="barrelstrength\sproutemail\elements\SentEmail"
        // data-id="168"
        //data-site-id="1"
        //data-status="enabled"
        //data-label="Oct 31, 2018 05:44 AM"
        //data-url=""
        //data-level=""
        //class="element info-hud"
        // data-editable=""
        //></div>
//        return $html;
//    }

//    /**
//     * @return string
//     * @throws \Twig_Error_Loader
//     * @throws \yii\base\Exception
//     */
//    public function getEditorHtml(): string
//    {
//        $sentEmailInfo = [];
//
//        if ($this->info != null) {
//            $sentEmailInfo = Json::decode($this->info);
//        }
//
//        $html = Craft::$app->getView()->renderTemplate('sprout-base-email/sentemails/_hud', [
//            'sentEmail' => $sentEmailInfo
//        ]);
//
//        return $html;
//    }

    /**
     * @return array
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
     * @return string
     */
    public function getLocaleNiceDateTime()
    {
        return $this->dateCreated->format('M j, Y H:i A');
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
            $record = SentEmailRecord::findOne($this->id);

            if (!$record) {
                throw new Exception('Invalid campaign email ID: '.$this->id);
            }
        } else {
            $record = new SentEmailRecord();
            $record->id = $this->id;
        }

        $record->title = $this->title;
        $record->emailSubject = $this->emailSubject;
        $record->fromEmail = $this->fromEmail;
        $record->fromName = $this->fromName;
        $record->toEmail = $this->toEmail;
        $record->body = $this->body;
        $record->htmlBody = $this->htmlBody;
        $record->info = $this->info;
        $record->status = $this->status;
        $record->dateCreated = $this->dateCreated;
        $record->dateUpdated = $this->dateUpdated;

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
     * @inheritdoc
     */
    protected static function defineActions(string $source = null): array
    {
        $actions = [];

        $actions[] = DeleteEmail::class;

        return $actions;
    }

    public function getInfo()
    {
        // @todo - review Sent Email assignment
        $infoTable = new SentEmailInfoTable();
        $infoTable->setAttributes(Json::decode($this->info), false);

        return $infoTable;
    }
}
