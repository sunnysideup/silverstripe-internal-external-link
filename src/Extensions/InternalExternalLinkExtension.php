<?php

namespace Sunnysideup\InternalExternalLink\Extensions;

use Page;

use Sunnysideup\EmailAddressDatabaseField\Model\Fieldtypes\EmailAddress;

use Sunnysideup\PhoneField\Model\Fieldtypes\PhoneField;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\File;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\FormScaffolder;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\FieldType\DBField;

use SilverStripe\ORM\DataObject;
use SilverStripe\View\Requirements;

class InternalExternalLinkExtension extends DataExtension
{
    public static $casting = [
        'MyLink' => 'Varchar',
    ];
    private static $db = [
        'LinkType' => "Enum('Internal,External,DownloadFile,Email,Phone', 'Internal')",
        'ExternalLink' => 'Varchar(255)',
    ];

    private static $has_one = [
        'InternalLink' => Page::class,
        'DownloadFile' => File::class,
    ];

    private static $owns = [
        'DownloadFile',
    ];

    /**
     * use the $fieldNameAppendix if you have multiple fields.
     *
     * @param string $fieldNameAppendix - optional
     */
    public function MyLink($fieldNameAppendix = ''): ?string
    {
        return $this->owner->getMyLink($fieldNameAppendix);
    }

    /**
     * use the $fieldNameAppendix if you have multiple fields.
     *
     * @param string $fieldNameAppendix - optional
     */
    public function getMyLink(?string $fieldNameAppendix = ''): ?string
    {
        $linkTypeFieldName = 'LinkType' . $fieldNameAppendix;

        $InternalLinkMethodName = 'InternalLink' . $fieldNameAppendix;
        $internalLinkFieldName = $InternalLinkMethodName . 'ID';

        $downloadLinkMethodName = 'DownloadFile' . $fieldNameAppendix;
        $downloadLinkFieldName = $downloadLinkMethodName . 'ID';

        $externalLinkFieldName = 'ExternalLink' . $fieldNameAppendix;
        if ('Internal' === $this->owner->{$linkTypeFieldName} && $this->owner->{$internalLinkFieldName}) {
            $obj = $this->owner->{$InternalLinkMethodName}();
            if ($obj) {
                return $obj->Link();
            }
        } elseif ('DownloadFile' === $this->owner->{$linkTypeFieldName} && $this->owner->{$downloadLinkFieldName}) {
            $obj = $this->owner->{$downloadLinkMethodName}();
            if ($obj) {
                return $obj->Link();
            }
        } elseif ($this->owner->{$externalLinkFieldName}) {
            if('External' === $this->owner->{$linkTypeFieldName}) {
                return DBField::create_field('Varchar', $this->owner->{$externalLinkFieldName})->url();
            } elseif ('Email' === $this->owner->{$linkTypeFieldName} ) {
                $val = $this->owner->{$externalLinkFieldName};
                if(class_exists('Sunnysideup\\EmailAddressDatabaseField\\Model\\Fieldtypes\\EmailAddress')) {
                    $val = DBField::create_field('EmailAddress', $val)->HiddenEmailAddress();
                }
                return 'mailto:'.$val;
            } elseif ( 'Phone' === $this->owner->{$linkTypeFieldName}) {
                $val = $this->owner->{$externalLinkFieldName};
                if(class_exists('Sunnysideup\\PhoneField\\Model\\Fieldtypes\\PhoneField')) {
                    $val = DBField::create_field('PhoneField', $this->owner->{$externalLinkFieldName})->IntlFormat()->Raw();
                }
                return 'callto:'.$val;
            }
        }

        return null;
    }

    public function updateCMSFields(FieldList $fields)
    {
        $fieldNameAppendici = $this->getFieldNameAppendici();
        foreach ($fieldNameAppendici as $appendix) {
            $linkTypeClass = 'LinkType' . $appendix . '_'.rand(0,999999);
            $internalClass = 'InternalLink' . $appendix . 'ID_'.rand(0,999999);
            $externalClass = 'ExternalLink' . $appendix . '_'.rand(0,999999);
            $downloadFileClass = 'DownloadFile' . $appendix . '_'.rand(0,999999);

            $js = <<<js
                var el = this;
                const val = jQuery('.{$linkTypeClass}').find('.form-check-input:checked').val();
                const internaLinkHolder = jQuery('.{$internalClass}');
                const externaLinkHolder = jQuery('.{$externalClass}');
                const downloadLinkHolder = jQuery('.{$downloadFileClass}');
                if (val === 'Internal') {
                    internaLinkHolder.show();
                    externaLinkHolder.hide();
                    downloadLinkHolder.hide();
                } else if(val === 'External' || val === 'Phone' || val === 'Email') {
                    internaLinkHolder.hide();
                    externaLinkHolder.show();
                    downloadLinkHolder.hide();
                } else if(val === 'DownloadFile') {
                    internaLinkHolder.hide();
                    externaLinkHolder.hide();
                    downloadLinkHolder.show();
                } else {
                    internaLinkHolder.show();
                    externaLinkHolder.show();
                    downloadLinkHolder.show();
                }

js;
            Requirements::customScript('
                const '.$linkTypeClass.'fx = function() {
                    '.$js.'
                }
                jQuery(".'.$linkTypeClass.' input").on("change click", '.$linkTypeClass.'fx());
                window.setTimeout(
                    function() {
                        '.$linkTypeClass.'fx();
                    },
                    500
                )',
                $linkTypeClass
            );
            $fields->removeByName([
                'LinkType' . $appendix,
                'InternalLink' . $appendix . 'ID',
                'DownloadFile' . $appendix,
                'ExternalLink' . $appendix,
            ]);
            // $fields->insertBefore(new Tab('Links', 'Links'), 'Settings');
            $fields->addFieldsToTab(
                'Root.Links',
                [
                    HeaderField::create(
                        'Link-Details-Heading' . $appendix,
                        'Link'
                    ),
                    OptionsetField::create(
                        'LinkType' . $appendix,
                        'Link Type ' . $appendix,
                        $this->owner->dbObject('LinkType')->enumValues()
                    )
                        ->setAttribute('onchange', $js)
                        ->setAttribute('onclick', $js)
                        ->addExtraClass($linkTypeClass),
                    TreeDropdownField::create(
                        'InternalLink' . $appendix . 'ID',
                        'Internal Link ' . $appendix,
                        Page::class
                    )
                        ->addExtraClass($internalClass),
                    TextField::create(
                        'ExternalLink' . $appendix,
                        'External Link / Email / Phone'
                    )
                        ->addExtraClass($externalClass),
                    UploadField::create(
                        'DownloadFile' . $appendix,
                        'Download File ' . $appendix
                    )
                        ->addExtraClass($downloadFileClass),
                ]
            );
            Requirements::customScript(
                'window.setTimeout(
                    function() {
                        jQuery(\'input[name="LinkType' . $appendix . '"]\').change();
                    }
                    , 500
                )',
                'InternalExternalLinkKickStart' . $appendix
            );
        }
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $fieldNameAppendici = $this->getFieldNameAppendici();
        foreach ($fieldNameAppendici as $appendix) {
            $linkTypeField = 'LinkType' . $appendix;
            $internalLinkField = 'InternalLink' . $appendix . 'ID';
            $externalLinkField = 'ExternalLink' . $appendix;

            if ('Internal' === $this->owner->{$linkTypeField} && ! $this->owner->{$internalLinkField} && $this->owner->{$externalLinkField}) {
                $this->owner->{$linkTypeField} = 'External';
            }
            if ('External' === $this->owner->LinkType && $this->owner->{$internalLinkField} && ! $this->owner->{$externalLinkField}) {
                $this->owner->{$linkTypeField} = 'Internal';
            }
        }
    }

    protected function getFieldNameAppendici(): array
    {
        if ($this->owner->hasMethod('getFieldNameAppendiciMore')) {
            return $this->owner->getFieldNameAppendiciMore();
        }

        // we need an empty string here ...
        return [
            '',
        ];
    }
}
