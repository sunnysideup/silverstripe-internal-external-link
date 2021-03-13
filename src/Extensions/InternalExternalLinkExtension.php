<?php

namespace Sunnysideup\InternalExternalLink\Extensions;

use SilverStripe\Forms\FieldList;
use \Page;


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
        return $this->getMyLink($fieldNameAppendix);
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
        if ($this->{$linkTypeFieldName} === 'Internal' && $this->{$internalLinkFieldName}) {
            $obj = $this->{$InternalLinkMethodName}();
            if ($obj) {
                return $obj->Link();
            }
        } elseif ($this->{$linkTypeFieldName} === 'External' && $this->{$externalLinkFieldName}) {
            return DBField::create_field('Varchar', $this->{$externalLinkFieldName})->url();
        }

        return null;
    }

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldsToTab(
            'Root.Link',
            [
                OptionsetField::create('LinkType', 'Link Type', $this->dbObject('LinkType')->enumValues()),
                TreeDropdownField::create('InternalLinkID', 'Internal Link', Page::class),
                TextField::create('ExternalLink', 'External Link'),
            ]
        );

    }
}
