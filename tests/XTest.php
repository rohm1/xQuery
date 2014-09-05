<?php

use RPBase\XQuery\Event;
use RPBase\XQuery\XQuery;

class XTest extends PHPUnit_Framework_TestCase
{

    public function testEach()
    {
        $vals = [];
        $maxItems = 5;

        XQuery::load('
            <div id="root">
                <span id="my_id" class="hello-world lol">
                    <div class="test">blah blah</div>
                    <div class="tested">blah blah</div>
                    <span class="test">blah blah</span>
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

        $this->assertEquals(2, count($vals));
    }
}
