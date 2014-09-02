xQuery
======

```php
use RPBase\XQuery\XQuery;

$vals = [];

XQuery::load('
    <div id="root">
        <span id="my_id" class="hello-world lol">
            <div class="test">blah blah</div>
        </span>
        <div class="test">hello world!</div>
    </div>
    ')->find('#root div.test')->each(function($node, $args) {
        $args[0][] = $node->text();
    }, [&$vals]);

var_dump($vals);
// array(2) { [0]=> string(9) "blah blah" [1]=> string(12) "hello world!" }
```

##DOM query##
XQuery make use of [rohm1/Css2Xpath](https://github.com/rohm1/Css2Xpath) to convert CSS selectors into XPATH.

##Methods##
 * attr
 * attrs
 * eq
 * each
 * find
 * html
 * text

##Attributes##
 * length
