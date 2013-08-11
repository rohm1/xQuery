xQuery
======

```php
xQuery('
	<div id="root">
		<span id="my_id" class="hello-world lol">
			<div class="test">blah blah</div>
		</span>
		<div class="test">hello world!</div>
	</div>
')->get('#root > div.test')->html();
```
