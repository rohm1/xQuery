<?php
/**
 * @author rohm1
 * @link https://github.com/rohm1/xQuery
 */

namespace RPBase\XQuery;

use DOMDocument;
use DOMXPath;

use RPBase\Css2Xpath\Parser as Css2Xpath;

class XQuery
{

    /**
     * The document
     *
     * @var DOMDocument
     */
    protected $doc;

    /**
     * List of attributes of the document
     *
     * @var array
     */
    protected $attrs;

    /**
     * CSS selector used to create this object
     *
     * @var string
     */
    protected $selector = '';

    /**
     * Parent document
     *
     * @var XQuery
     */
    protected $prev = null;

    /**
     * Number of child nodes
     *
     * @var int
     */
    public $length;

    /**
     * Constructor
     *
     * @param mixed $doc The document to manipulate.
     *     $doc can be any of (X)HTML string, a (X)HTML document location,
     *     a DOMDocument, a DOMElement, or a DOMNodeList.
     * @param string $selector The selector used to create this instance.
     *     You should never set this.
     * @param XQuery $prev The parent XQuery instance of this document.
     *     You should never set this.
     */
    public function __construct($doc, $selector = '', XQuery $prev = null)
    {
        $this->selector = $selector;
        $this->prev = $prev;

        $this->createDOMDocument($doc);
        $this->length = $this->doc->childNodes->length;
    }

    /**
     * @param mixed $doc
     * @param string $selector
     * @param \RPBase\XQuery\XQuery $prev
     * @return \RPBase\XQuery\XQuery
     */
    public static function load($doc, $selector = '', XQuery $prev = null)
    {
        return new self($doc, $selector, $prev);
    }

    /**
     * Creates the DOMDocument
     *
     * @param mixed $doc
     * @throws \RPBase\XQuery\XQueryImportException
     */
    protected function createDOMDocument($doc)
    {
        $this->doc = new DOMDocument();
        $this->doc->validateOnParse = true;
        $this->doc->preserveWhiteSpace = false;
        $this->doc->strictErrorChecking = false;

        if (is_string($doc)) {
            if (preg_match('#^<(!?)([a-zA-Z]+)#', trim($doc))) {
                return @$this->doc->loadHTML($doc);
            }

            return @$this->doc->loadHTMLFile($doc);
        }

        if (is_object($doc)) {
            $class = get_class($doc);
            if ($class == 'DOMElement') {
                return $this->doc->appendChild( $this->doc->importNode($doc, true) );
            }

            if (in_array($class, ['DOMDocument', 'DOMNodeList'])) {
                foreach ($doc as $node) {
                    $this->doc->appendChild( $this->doc->importNode($node, true) );
                }

                return;
            }
        }

        throw new XQueryImportException('Unsuported document type submitted for import by XQuery');
    }

    /**
     * Queries the DOM with a CSS selector
     *
     * @param string $selector
     * @param int $index
     * @return \RPBase\XQuery\XQuery
     */
    public function find($selector, $index = -1)
    {
        $xpath = $this->parse($selector);

        $dxpath = new DOMXPath($this->doc);
        $res = $dxpath->query($xpath);

        if ($index != -1) {
            $res = $res->item($index);
        }

        return $this->mkRes($res, $selector);
    }

    /**
     * Selects the $index node in the document
     *
     * @param int $index
     * @return \RPBase\XQuery\XQuery
     */
    public function eq($index)
    {
        return $this->mkRes($this->doc->childNodes->item($index));
    }

    /**
     * Returns the text version of the document
     *
     * @return string
     */
    public function text()
    {
        return $this->doc->textContent;
    }

    /**
     * Returns the HTML version of the document
     *
     * @return string
     */
    public function html()
    {
        return $this->doc->saveHtml();
    }

    /**
     * Returns an attribute of the document
     *
     * @param string $attr
     * @return string|null
     */
    public function attr($attr)
    {
        $this->attrs();

        if (!array_key_exists($attr, $this->attrs)) {
            return null;
        }

        return $this->attrs[$attr];
    }

    /**
     * Returns all the attributes of the document
     *
     * @return array
     */
    public function attrs()
    {
        if ($this->attrs === null) {

            $this->attrs = [];
            foreach ($this->doc->firstChild->attributes as $attribute) {
                $this->attrs[$attribute->name] = $attribute->value;
            }

        }

        return $this->attrs;
    }

    /**
     * Returns the parent node of the document
     *
     * Note: This requires the current document is the result of
     * a XQuery->find()
     *
     * @return \RPBase\XQuery\XQuery
     */
    public function parent()
    {
        //TODO implement
    }

    /**
     * Returns a parent node of the document filtered by a selector
     *
     * Note: This requires the current document is the result of
     * a XQuery->find()
     *
     * @param string $selector
     * @return \RPBase\XQuery\XQuery
     */
    public function parents($selector)
    {
        $xpath = $this->parse($selector);

        //TODO implement
    }

    /**
     * Iterates over the nodes of the document with a callback
     *
     * The first argument of the callback are the nodes of the document
     * converted to XQuery objects, and the second the arguments $arg.
     *
     * @param callable $callback a callback object
     * @param mixed $args arguments to pass to the callback object
     * @return \RPBase\XQuery\XQuery
     * @see call_user_func
     */
    public function each($callback, $args = null)
    {
        if ($this->doc !== null) {
            foreach ($this->doc->childNodes as $node) {
                call_user_func($callback, $this->mkRes($node), $args);
            }
        }

        return $this;
    }

    /**
     * Parses a CSS selector into a XPath
     *
     * @param string $selector
     * @return string
     */
    protected function parse($selector)
    {
        return Css2Xpath::parse($selector);
    }

    /**
     * Creates the object to return
     *
     * @param DOMNodeList $res
     * @param string $selector
     * @return \RPBase\XQuery\XQuery
     */
    protected function mkRes($res, $selector = '')
    {
        return new XQuery($res, $selector != '' ? $selector : $this->selector, $this);
    }

}
