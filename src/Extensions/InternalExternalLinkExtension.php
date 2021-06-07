<?php

namespace Sunnysideup\InternalExternalLink\Extensions;

use Page;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TextField;

use SilverStripe\Forms\TreeDropdownField;

use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\FieldType\DBField;

class InternalExternalLinkExtension extends DataExtension
{
    private static $db = [
        'LinkType' => "Enum('Internal,External', 'Internal')",
        'ExternalLink' => 'Varchar(255)',
    ];

    private static $has_one = [
        'InternalLink' => Page::class,
    ];

    /**
     * use the $fieldNameAppendix if you have multiple fields
     * @param  string     $fieldNameAppendix - optional
     * @return string|null
     */
    public function MyLink($fieldNameAppendix = ''): ?string
    {
        return $this->owner->getMyLink($fieldNameAppendix);
    }

    /**
     * use the $fieldNameAppendix if you have multiple fields
     * @param  string     $fieldNameAppendix - optional
     * @return string|null
     */
    public function getMyLink(?string $fieldNameAppendix = ''): ?string
    {
        $linkTypeFieldName = 'LinkType' . $fieldNameAppendix;
        $internalLinkFieldName = 'InternalLink' . $fieldNameAppendix . 'ID';
        $InternalLinkMethodName = 'InternalLink' . $fieldNameAppendix;
        $externalLinkFieldName = 'ExternalLink' . $fieldNameAppendix;
        if ($this->owner->{$linkTypeFieldName} === 'Internal' && $this->owner->{$internalLinkFieldName}) {
            $obj = $this->owner->{$InternalLinkMethodName}();
            if ($obj) {
                return $obj->Link();
            }
        } elseif ($this->owner->{$linkTypeFieldName} === 'External' && $this->owner->{$externalLinkFieldName}) {
            return DBField::create_field('Varchar', $this->owner->{$externalLinkFieldName})->url();
        }

        return null;
    }

    public function updateCMSFields(FieldList $fields)
    {
        $js = <<<js
            var el = this;
            const val = jQuery(el).find('.form-check-input:checked').val();
            if (val === 'Internal') {
                jQuery('#Form_ItemEditForm_InternalLinkID_Holder').show();
                jQuery('#Form_ItemEditForm_ExternalLink_Holder').hide();
            } else if(val === 'External') {
                jQuery('#Form_ItemEditForm_InternalLinkID_Holder').hide();
                jQuery('#Form_ItemEditForm_ExternalLink_Holder').show();
            } else {
                jQuery('#Form_ItemEditForm_InternalLinkID_Holder').show();
                jQuery('#Form_ItemEditForm_ExternalLink_Holder').show();
            }

js;
        // $fields->insertBefore(new Tab('Links', 'Links'), 'Settings');
        $fields->addFieldsToTab(
            'Root.Links',
            [
                HeaderField::create('Link-Details-Heading', 'Link'),
                OptionsetField::create('LinkType', 'Link Type', $this->owner->dbObject('LinkType')->enumValues())
                    ->setAttribute('onclick', $js)
                    ->setAttribute('onchange', $js),
                TreeDropdownField::create('InternalLinkID', 'Internal Link', Page::class),
                TextField::create('ExternalLink', 'External Link')->setAttribute('placeholder', 'e.g. https://www.rnz.co.nz')
                    ->setDescription('Enter full URL, eg "https://google.com"'),
            ]
        );
    }
}
