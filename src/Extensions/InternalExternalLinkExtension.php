<?php

namespace Sunnysideup\InternalExternalLink\Extensions;

use \Page;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\OptionsetField;
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
        $fields->addFieldsToTab(
            'Root.Link',
            [
                OptionsetField::create('LinkType', 'Link Type', $this->owner->dbObject('LinkType')->enumValues()),
                TreeDropdownField::create('InternalLinkID', 'Internal Link', Page::class),
                TextField::create('ExternalLink', 'External Link')
                    ->setDescription('Enter full URL, eg "https://google.com"'),
            ]
        );
    }
}
