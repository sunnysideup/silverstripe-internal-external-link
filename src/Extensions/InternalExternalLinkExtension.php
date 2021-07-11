<?php

namespace Sunnysideup\InternalExternalLink\Extensions;

use Page;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\File;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\FieldType\DBField;

class InternalExternalLinkExtension extends DataExtension
{
    public static $casting = [
        'MyLink' => 'Varchar',
    ];
    private static $db = [
        'LinkType' => "Enum('Internal,External,DownloadFile', 'Internal')",
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
        } elseif ('External' === $this->owner->{$linkTypeFieldName} && $this->owner->{$externalLinkFieldName}) {
            return DBField::create_field('Varchar', $this->owner->{$externalLinkFieldName})->url();
        } elseif ('DownloadFile' === $this->owner->{$linkTypeFieldName} && $this->owner->{$downloadLinkFieldName}) {
            $obj = $this->owner->{$downloadLinkMethodName}();
            if ($obj) {
                return $obj->Link();
            }
        }

        return null;
    }

    public function updateCMSFields(FieldList $fields)
    {
        $fieldNameAppendici = $this->getFieldNameAppendici();
        foreach ($fieldNameAppendici as $appendix) {
            $js = <<<js
                var el = this;
                const val = jQuery(el).find('.form-check-input:checked').val();
                if (val === 'Internal') {
                    jQuery('#Form_ItemEditForm_InternalLink{$appendix}ID_Holder').show();
                    jQuery('#Form_ItemEditForm_ExternalLink{$appendix}_Holder').hide();
                    jQuery('#Form_ItemEditForm_DownloadFile{$appendix}_Holder').hide();
                } else if(val === 'External') {
                    jQuery('#Form_ItemEditForm_InternalLink{$appendix}ID_Holder').hide();
                    jQuery('#Form_ItemEditForm_ExternalLink{$appendix}_Holder').show();
                    jQuery('#Form_ItemEditForm_DownloadFile{$appendix}_Holder').hide();
                } else if(val === 'DownloadFile') {
                    jQuery('#Form_ItemEditForm_InternalLink{$appendix}ID_Holder').hide();
                    jQuery('#Form_ItemEditForm_ExternalLink{$appendix}_Holder').hide();
                    jQuery('#Form_ItemEditForm_DownloadFile{$appendix}_Holder').show();
                } else {
                    jQuery('#Form_ItemEditForm_InternalLink{$appendix}ID_Holder').show();
                    jQuery('#Form_ItemEditForm_ExternalLink{$appendix}_Holder').show();
                    jQuery('#Form_ItemEditForm_DownloadFile{$appendix}_Holder').show();
                }

js;

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
                        ->setAttribute('onclick', $js)
                        ->setAttribute('onchange', $js),
                    TreeDropdownField::create(
                        'InternalLink' . $appendix . 'ID',
                        'Internal Link ' . $appendix,
                        Page::class
                    ),
                    UploadField::create(
                        'DownloadFile' . $appendix,
                        'Download File ' . $appendix
                    ),
                ]
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
                $this->owner->LinkType = 'External';
            }
        }
    }

    protected function getFieldNameAppendici(): array
    {
        if ($this->owner->hasMethod('getFieldNameAppendiciMore')) {
            return $this->owner->getFieldNameAppendiciMore();
        }

        return [];
    }
}
