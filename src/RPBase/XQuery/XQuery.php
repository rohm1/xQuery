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
     * Top document
     *
     * @var XQuery
     */
    protected $root = null;

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
     * @param XQuery $root The top XQuery instance of this instance.
     *     You should never set this.
     * @throws ImportException
     */
    public function __construct($doc = null, $selector = '', XQuery $prev = null, $root = null)
    {
        $this->selector = $selector;
        $this->prev = $prev;
        $this->root = $root;

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
        if ($doc instanceof DOMDocument) {
            $this->doc = $doc;
            return;
        }

        $this->doc = new DOMDocument();
        $this->doc->validateOnParse = true;
        $this->doc->preserveWhiteSpace = false;
        $this->doc->strictErrorChecking = false;

        if ($doc === null) {
            return;
        }

        if (is_string($doc)) {
            $doc = trim($doc);

            if (!preg_match('#^<!?\w+#', $doc)) {
                $doc = file_get_contents($doc);
            }

            // html cleaning: remove script tags
            $doc = preg_replace('#<((no)?script).*>.*</\1>#iUms', '', $doc);

            $this->doc->loadHTML($doc);
            return;
        }

        if ($doc instanceof DOMElement) {
            return $this->doc->appendChild( $this->doc->importNode($doc, true) );
        }

        if ($doc instanceof DOMNodeList) {
            foreach ($doc as $node) {
                $this->doc->appendChild( $this->doc->importNode($node, true) );
            }

            return;
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

    /**
     * Returns the root object
     *
     * @return XQuery
     */
    public function root()
    {
        return $this->root;
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

        return $this->queryAndResult($this->doc, $xpath);
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
     * Executes a traversal query on the document given an axis, and returns
     * the result as an XQuery object
     *
     * @param mixed $doc
     * @param string $axis
     * @param string $selector
     * @param string $baseSelector
     * @param bool $useDocAsRoot
     * @return XQuery
     */
    protected function traverse($doc, $axis, $selector = null, $baseSelector = null, $useDocAsRoot = false)
    {
        $xpath = (!empty($baseSelector) ? $baseSelector . '/' : '') .
                 $axis . '::*' .
                 (!empty($selector) ? $this->parse($selector, true) : '');

        return $this->queryAndResult($doc, $xpath, $useDocAsRoot ? $doc : null);
    }

    /**
     * Traverses the following or preceding siblings
     *
     * @param string $axis
     * @param string $selector
     * @return XQuery
     */
    protected function siblingTraverse($axis, $selector = null)
    {
        $doc = $this->prev;
        $index = 1;
        $baseSelector = $this->selector;

        if (preg_match('/(?<base>.+)\/' . $axis . '-sibling::\*\[position\(\) = (?<position>\d)+\]$/', $this->selector, $match)) {
            $doc = $this->root();
            $index = $match['position']+1;
            $baseSelector = $match['base'];
        }

        $res = $this->traverse($doc, $axis . '-sibling', ':nth-child(' . $index . ')', $baseSelector, true);

        return empty($selector) || $res->is($selector) ? $res : $this->mkRes(new DOMNodeList());
    }

    /**
     * Selects the immediately following sibling of each element in the set
     *
     * @param string $selector
     * @return XQuery
     */
    public function next($selector = null)
    {
        return $this->siblingTraverse('following', $selector);
    }

    /**
     * Selects all the following siblings of each element in the set
     *
     * @return XQuery
     */
    public function nextAll($selector = null)
    {
        return $this->traverse($this->prev, 'following-sibling', $selector, $this->selector);
    }

    /**
     * Selects the immediately preceding sibling of each element in the set
     *
     * @param string $selector
     * @return XQuery
     */
    public function prev($selector = null)
    {
        return $this->siblingTraverse('preceding', $selector);
    }

    /**
     * Selects all the preceding siblings of each element in the set
     *
     * @return XQuery
     */
    public function prevAll($selector = null)
    {
        return $this->traverse($this->prev, 'preceding-sibling', $selector, $this->selector);
    }

    /**
     * Selects all the direct children of the current node, eventually
     * filtered by a selector
     *
     * @param string $selector
     * @return XQuery
     */
    public function children($selector = null)
    {
        return $this->traverse($this, 'child', $selector);
    }

    /**
     * Returns the parent node of the document
     *
     * @return XQuery
     */
    public function parent()
    {
        return $this->traverse($this->prev, 'parent', null, $this->selector);
    }

    /**
     * Returns a parent node of the document filtered by a selector
     *
     * @param string $selector
     * @return XQuery
     */
    public function parents($selector)
    {
        return $this->traverse($this->prev, 'ancestor', $selector, $this->selector);
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
     * Queries the document with an XPATH expression and returns the result
     * as an XQuery object
     *
     * @param mixed $doc
     * @param string $xpath
     * @param XQuery $root
     * @return XQuery
     */
    protected function queryAndResult($doc, $xpath, $root = null)
    {
        return $this->mkRes($this->query($doc, $xpath), $xpath, $root);
    }

    /**
     * Executes a XPATH query on a document and returns the result
     *
     * @param mixed $doc
     * @param string $xpath
     * @return DOMNodeList
     */
    protected function query($doc, $xpath)
    {
        if ($doc instanceof self) {
            $doc = $doc->getDocument();
        }

        if (!$doc instanceof DOMDocument) {
            return new DOMNodeList();
        }

        $dxpath = new DOMXPath($doc);
        return $dxpath->query($xpath);
    }

    /**
     * Creates the object to return
     *
     * @param mixed $res
     * @param string $selector
     * @param XQuery $root
     * @return XQuery
     */
    protected function mkRes($res, $selector = null, $root = null)
    {
        if ($root === null) {
            $root = $this->prev === null ? $this : $this->prev;
        }

        return new static($res, !empty($selector) ? $selector : $this->selector, $this, $root);
    }

}
