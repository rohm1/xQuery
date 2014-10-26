<?php

namespace RPBaseTest\XQuery;

use RPBase\XQuery\Event;
use RPBase\XQuery\XQuery;

class XQueryTest extends \PHPUnit_Framework_TestCase
{

    protected function getSimpleHtml()
    {
        return '
            <div id="root">
                <span id="my_id" class="hello-world lol">
                    <div class="test test1">blah blah</div>
                    <div class="tested">blah blah</div>
                    <p class="test test2">blah blah</p>
                    <p class="with-child">
                        <span class="child1">child1</span>
                        <span class="child2">child2</span>
                    </p>
                </span>
                <div>
                    <div class="test">hello world!</div>
                    <div>hello again</div>
                </div>
            </div>
        ';
    }

    public function testFind()
    {
        $doc = XQuery::load( $this->getSimpleHtml() );

        $this->assertEquals(3, $doc->find('.test')->length());
        $this->assertTrue($doc->find('.test', 1)->is('p'));
    }

    public function testEach()
    {
        $vals = [];
        $maxItems = 5;

        XQuery::load( $this->getSimpleHtml() )->find('#root div.test')
              ->each(function(XQuery $node, Event $event) use (&$vals, $maxItems) {
                $vals[] = $node->text();

                if (count($vals) >= $maxItems) {
                    $event->stopPropagation();
                }
            });

        $this->assertEquals(2, count($vals));
    }

    public function testEq()
    {
        $childs = XQuery::load( $this->getSimpleHtml() )->find('.test');

        $this->assertEquals(3, $childs->length());
        $this->assertEquals('hello world!', $childs->eq(2)->text());
        $this->assertEquals(0, $childs->eq(5)->length());
    }

    public function testIs()
    {
        $doc = XQuery::load( $this->getSimpleHtml() )->find('#my_id');

        $this->assertTrue($doc->is('span'));
        $this->assertTrue($doc->is('.lol'));
        $this->assertTrue($doc->is('.hello-world'));

        $this->assertFalse($doc->is('div'));
    }

    public function testEnd()
    {
        $doc = XQuery::load( $this->getSimpleHtml() )->find('#my_id');
        $childs = $doc->find('.test');
        $firstChild = $childs->eq(0);

        $this->assertEquals(2, $childs->length());
        $this->assertEquals(1, $firstChild->length());
        $this->assertEquals(2, $firstChild->end()->length());
        $this->assertEquals($childs, $firstChild->end());
        $this->assertEquals($doc, $firstChild->end()->end());
        $this->assertEquals($doc, $childs->end());
    }

    public function testSelectChildren()
    {
        $doc = XQuery::load( $this->getSimpleHtml() )->find('#my_id');

        $this->assertEquals(4, $doc->children()->length());

        $div = $doc->children('.with-child');
        $this->assertTrue($div->is('.with-child'));
        $this->assertEquals(2, $div->children()->length());
        $this->assertTrue($div->children()->eq(0)->is('.child1'));
        $this->assertTrue($div->children()->eq(1)->is('.child2'));
    }

    public function testSelectParent()
    {
        $parent = XQuery::load( $this->getSimpleHtml() )->find('#root div.test')->parent();

        $this->assertEquals(2, $parent->length());

        $this->assertEquals('hello world! hello again', preg_replace('/\s+/', ' ', str_replace("\n", '', trim($parent->eq(1)->text()))));
    }

    public function testHasNoParent()
    {
        $doc = XQuery::load( $this->getSimpleHtml() );

        $this->assertEquals(0, $doc->parent()->length());
    }

    public function testSelectParents()
    {
        $childs = XQuery::load( $this->getSimpleHtml() )->find('#root div.test');

        $this->assertEquals(0, $childs->parents('p')->length());
        $this->assertEquals(1, $childs->parents('#my_id')->length());
        $this->assertEquals(2, $childs->parents('div')->length());
    }

    public function testNext()
    {
        $doc = XQuery::load( $this->getSimpleHtml() )->find('.test1');

        $this->assertTrue($doc->next()->is('.tested'));
        $this->assertTrue($doc->next()->next()->is('.test2'));
        $this->assertTrue($doc->next()->next()->next()->is('.with-child'));

        $this->assertEquals(0, $doc->next('.test2')->length());
        $this->assertEquals(1, $doc->next('.tested')->length());
    }

    public function testNextAll()
    {
        $doc = XQuery::load( $this->getSimpleHtml() )->find('.test1');

        $this->assertEquals(3, $doc->nextAll()->length());
        $this->assertEquals(1, $doc->nextAll('.test2')->length());
    }

    public function testPrev()
    {
        $doc = XQuery::load( $this->getSimpleHtml() )->find('.with-child');

        $this->assertTrue($doc->prev()->is('.test2'));
        $this->assertTrue($doc->prev()->prev()->is('.tested'));
        $this->assertTrue($doc->prev()->prev()->prev()->is('.test1'));

        $this->assertEquals(0, $doc->prev('.test1')->length());
        $this->assertEquals(1, $doc->prev('.test2')->length());
    }

    public function testPrevAll()
    {
        $doc = XQuery::load( $this->getSimpleHtml() )->find('.test1');

        $this->assertEquals(3, $doc->nextAll()->length());
        $this->assertEquals(1, $doc->nextAll('.test2')->length());
    }

}
