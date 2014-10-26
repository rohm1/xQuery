xQuery
======

[![Build Status](https://secure.travis-ci.org/rohm1/xQuery.png?branch=master)](http://travis-ci.org/rohm1/xQuery)

```php
use RPBase\XQuery\Event;
use RPBase\XQuery\XQuery;

$vals = [];
$maxItems = 5;

XQuery::load('
    <div id="root">
        <span id="my_id" class="hello-world lol">
            <div class="test">blah blah</div>
        </span>
        <div class="test">hello world!</div>
    </div>
    ')->find('#root div.test')
      ->each(function(XQuery $node, Event $event) use (&$vals, $maxItems) {
        $vals[] = $node->text();

        if (count($vals) >= $maxItems) {
            $event->stopPropagation();
        }
    });

var_dump($vals);
// array(2) { [0]=> string(9) "blah blah" [1]=> string(12) "hello world!" }
```

##DOM query##
XQuery make use of [rohm1/Css2Xpath](https://github.com/rohm1/Css2Xpath) to convert CSS selectors into XPATH.

##Methods##
 * attr
 * attrs
 * children
 * end
 * eq
 * each
 * find
 * getDocument
 * is
 * hasAttribute
 * html
 * length
 * next
 * nextAll
 * parent
 * parents
 * prev
 * prevAll
 * text
