GoogleAnalytics
===============

A PHP wrapper for Google Analytics

This is a simple PHP wrapper for various Google Analytics features. It returns the javascript code (wrapped in <script> tags) that needs to be put in your pages. You should add the code before the closing </body> tag.

Sample use:
======

```php
require 'GoogleAnalytics.php';

$ga = new GoogleAnalytics('UA-XXXXXXXX-X');
echo $ga->getBasicInitCode();
```


If you would like to manually set campaign information, use the manual init code
This is useful for facebook applications because facebook masks referrer information and campaign info doesn't get sent all the way through
```php
$ga = new GoogleAnalytics('UA-XXXXXXXX-X');
echo $ga->getManualCampaignInitCode($utm_source, $utm_medium, $utm_campaign, $utm_content, $utm_term, $referrer);
```

Setting custom variables
```php
$ga = new GoogleAnalytics('UA-XXXXXXXX-X');
$ga->setCustomVar(1, 'somevariablename', 'somevalue', 1);
echo $ga->getBasicInitCode();
```

Getting code to track a virtual page view
```php
$ga = new GoogleAnalytics('UA-XXXXXXXX-X');
echo $ga->getVirtualPageviewCode('http://someurltotrack.com/virtualpage');
```

Getting code to track an event.
$ga = new GoogleAnalytics('UA-XXXXXXXX-X');
echo $ga->getEventCode(1, 'someaction', 'somelabel');
