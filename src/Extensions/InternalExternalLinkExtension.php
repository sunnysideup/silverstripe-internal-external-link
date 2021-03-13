<?php

namespace Sunnysideup\InternalExternalLink\Extensions;

use \Page;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\Tab;

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
        // $fields->insertBefore(new Tab('Links', 'Links'), 'Settings');
        $fields->addFieldsToTab(
            'Root.Links',
            [
                HeaderField::create('Link-Details-Heading', 'Link'),
                OptionsetField::create('LinkType', 'Link Type', $this->owner->dbObject('LinkType')->enumValues())
                    ->setAttribute('onchange', 'if(this.value ==="Internal")'),
                TreeDropdownField::create('InternalLinkID', 'Internal Link', Page::class),
                TextField::create('ExternalLink', 'External Link')->setAttribute('placeholder', 'e.g. https://www.rnz.co.nz')
                    ->setDescription('Enter full URL, eg "https://google.com"'),
                $this->getLinksField('Main', 'Go back to Content Tab')
            ]
        );

        $fields->addFieldToTab(
            'Root.Main',
            $this->getLinksField('Links', 'Add the Links in the Link Tab')
        );
    }

    public function getLinksField(string $nameOfTab, string $label)
    {
        return LiteralField::create(
            'LinkToLink'.$nameOfTab,
            '<a href="#" onclick="'.$this->getJsFoTabSwitch($nameOfTab).'">'.$label.'</a>'
        );
    }
    protected function getJsFoTabSwitch(string $nameOfTab) : string
    {
        $js = <<<js
        if(jQuery(this).closest('div.element-editor__element').length > 0) {
            jQuery(this)
                .closest('div.element-editor__element')
                .find('button[name=\'$nameOfTab\']')
                .click();
        } else {
            jQuery('li[aria-controls=\'Root_$nameOfTab\'] a').click();
        }
        return false;
js;
        return $js;
    }
}
