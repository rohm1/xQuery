<?php
/**
 * @author rohm1
 * @link https://github.com/rohm1/xQuery
 */

namespace RPBase\XQuery;

use DOMDocument;
use DOMElement;
use DOMNodeList;
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
     * XPATH selector used to create this object
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
    protected $length;

    /**
     * Constructor
     *
     * @param mixed $doc The document to manipulate.
     *     $doc can be any of a HTML string, a HTML document location (path, url),
     *     a DOMDocument, a DOMElement, or a DOMNodeList.
     * @param string $selector The selector used to create this instance.
     *     You should never set this.
     * @param XQuery $prev The parent XQuery instance of this instance.
     *     You should never set this.
     * @throws ImportException
     */
    public function __construct($doc = null, $selector = '', XQuery $prev = null)
    {
        $this->selector = $selector;
        $this->prev = $prev;

        $this->createDOMDocument($doc);
        $this->length = $this->doc->childNodes->length;
    }

    /**
     * @param mixed $doc
     * @return XQuery
     * @throws ImportException
     */
    public static function load($doc)
    {
        return new static($doc);
    }

    /**
     * Creates the DOMDocument
     *
     * @param mixed $doc
     * @throws ImportException
     */
    protected function createDOMDocument($doc)
    {
        $this->doc = new DOMDocument();
        $this->doc->validateOnParse = true;
        $this->doc->preserveWhiteSpace = false;
        $this->doc->strictErrorChecking = false;

        if ($doc === null) {
            return;
        }

        if (is_string($doc)) {
            $doc = trim($doc);

            if (preg_match('#^<(!?)(\w+)#', $doc)) {
                return @$this->doc->loadHTML($doc);
            }

            return @$this->doc->loadHTMLFile($doc);
        }

        if (is_object($doc)) {

            if ($doc instanceof DOMElement) {
                return $this->doc->appendChild( $this->doc->importNode($doc, true) );
            }

            if ($doc instanceof DOMDocument || $doc instanceof DOMNodeList) {
                foreach ($doc as $node) {
                    $this->doc->appendChild( $this->doc->importNode($node, true) );
                }

                return;
            }
        }

        throw new ImportException('Unsuported document type submitted for import by XQuery');
    }

    /************************
     *  Getters             *
     ************************/

    /**
     * @return int
     */
    public function length()
    {
        return $this->length;
    }

    /**
     * @return DOMDocument
     */
    public function getDocument()
    {
        return $this->doc;
    }

    /**
     * Returns the set of matched elements to its previous state
     *
     * @return XQuery
     */
    public function end()
    {
        if (!$this->prev instanceof self) {
            return new static();
        }

        return $this->prev;
    }

    /************************
     *  Selecting           *
     ************************/

    /**
     * Queries the DOM with a CSS selector
     *
     * @param string $selector
     * @param int $index
     * @return XQuery
     */
    public function find($selector, $index = -1)
    {
        $xpath = $this->parse($selector) .
                 ($index != -1 ? '[position() = ' . ($index + 1) . ']' : '');

        return $this->query($this->doc, $xpath, true);
    }

    /**
     * Selects the $index node in the document
     *
     * @param int $index
     * @return XQuery
     */
    public function eq($index)
    {
        $xpath = $this->selector . '[position() = ' . ($index + 1) . ']';

        return $this->mkRes($this->doc->childNodes->item($index), $xpath);
    }

    /************************
     *  Manipulating        *
     ************************/

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
        return $this->hasAttribute($attr) ? $this->attrs[$attr] : null;
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

            if ($this->length != 0) {
                foreach ($this->doc->firstChild->attributes as $attribute) {
                    $this->attrs[$attribute->name] = $attribute->value;
                }
            }

        }

        return $this->attrs;
    }

    /**
     * Determine whether the document has a given attribute
     *
     * @param string $attr
     * @return mixed
     */
    public function hasAttribute($attr)
    {
        return array_key_exists($attr, $this->attrs());
    }

    /**
     * Tells whether the first element in the set matches the given selector
     *
     * @param string $selector
     * @return bool
     */
    public function is($selector)
    {
        $xpath = 'self::*' . $this->parse($selector, true);

        $res = $this->query($this->eq(0), $xpath);

        return $res->length == 1;
    }

    /************************
     *  Traversing          *
     ************************/

    /**
     * Selects all the direct children of the current node, eventually
     * filtered by a selector
     *
     * @param string $selector
     * @return XQuery
     */
    public function children($selector = null)
    {
        $xpath = 'child::*' .
                 (!empty($selector) ? $this->parse($selector, true) : '');

        return $this->query($this, $xpath, true);
    }

    /**
     * Returns the parent node of the document
     *
     * @return XQuery
     */
    public function parent()
    {
        $xpath = $this->selector . '/parent::*';

        return $this->query($this->prev, $xpath, true);
    }

    /**
     * Returns a parent node of the document filtered by a selector
     *
     * @param string $selector
     * @return XQuery
     */
    public function parents($selector)
    {
        $xpath = $this->selector .
                 '/ancestor::*' . $this->parse($selector, true);

        return $this->query($this->prev, $xpath, true);
    }

    /**
     * Iterates over the nodes of the document with a callback
     *
     * Callback prototype: void callback(XQuery $node, Event $event)
     *
     * @param callable $callback
     * @return XQuery
     * @see call_user_func
     */
    public function each($callback)
    {
        if ($this->length != 0) {

            // create a callback event we can use to break the loop
            $event = new Event();

            foreach ($this->doc->childNodes as $node) {
                call_user_func($callback, $this->mkRes($node), $event);

                if ($event->isPropagationStopped()) {
                    break;
                }
            }

        }

        return $this;
    }

    /************************
     *  Utilities           *
     ************************/

    /**
     * Parses a CSS selector into a XPath expression
     *
     * @param string $selector
     * @param bool $removeDeepTraversing
     * @return string
     */
    protected function parse($selector, $removeDeepTraversing = false)
    {
        $xpath = Css2Xpath::parse($selector);

        if ($removeDeepTraversing) {
            $xpath = preg_replace('#^/\*/descendant::\*#', '', $xpath);
        }

        return $xpath;
    }

    /**
     * Executes a XPATH query on a document and returns the result
     *
     * @param mixed $doc
     * @param string $xpath
     * @param bool $mkResult
     * @return DOMNodeList|XQuery
     */
    protected function query($doc, $xpath, $mkResult = false)
    {
        if ($doc instanceof self) {
            $doc = $doc->getDocument();
        }

        if (!$doc instanceof DOMDocument) {
            $res = new DOMNodeList();
        } else {
            $dxpath = new DOMXPath($doc);
            $res = $dxpath->query($xpath);
        }

        return $mkResult ? $this->mkRes($res, $xpath) : $res;
    }

    /**
     * Creates the object to return
     *
     * @param DOMNodeList $res
     * @param string $selector
     * @return XQuery
     */
    protected function mkRes($res, $selector = null)
    {
        return new static($res, !empty($selector) ? $selector : $this->selector, $this);
    }

}
