<?php

namespace RPBaseTest\Css2Xpath;

use RPBase\Css2Xpath\Parser;

class ParserTest extends \PHPUnit_Framework_TestCase
{

    public function testParseId()
    {
        $this->assertEquals('/*/descendant::*[@id="id"]', Parser::parse('#id'));
    }
}
