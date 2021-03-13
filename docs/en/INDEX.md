# tl;dr

Add as extension to any class ...

```php
myClass extends DataObject
{
    private static $extensions = [
        Sunnysideup\InternalExternalLink\Extensions\InternalExternalLinkExtension:class
    ];
}
```
