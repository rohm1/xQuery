xQuery
======

```php
$vals = [];

xQuery('
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

##Disclaimer##
This is a work in progress. For a more detailled doc please refer to comments in the code. Thanks for reporting bugs, feature requests, and love messages.

##CSS Selectors##
 * *
 * E
 * E F
 * E > F
 * .class
 * #id
 * :first-child
 * :last-child
 * :nth-child
 * :not()

##Methods##
 * attr
 * eq
 * each
 * find
 * html
 * text

##Attributes##
 * length
