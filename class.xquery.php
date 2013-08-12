<?php
/**
 * @author rohm1
 */

/**
 * Shortcut function to create a new xQuery object
 */
function xQuery($doc) {
	return new xQuery($doc);
}

/**
 * xQuery
 */
class xQuery {

	/**
	 *
	 *
	 * @var array
	 */
	public static $ATTRS = array('#' => 'id', '.' => 'class');

	/**
	 * The document
	 *
	 * @var DOMDocument
	 */
	private $doc;

	/**
	 * CSS selector used to create this object
	 *
	 * @var string
	 */
	private $selector = '';

	/**
	 * Parent document
	 *
	 * @var xQuery
	 */
	private $prev = null;

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
	 * 	$doc can be any of (X)HTML string, a (X)HTML document location,
	 * 	a DOMDocument, a DOMElement, or a DOMNodeList.
	 * @param string $selector The selector used to create this instance.
	 * 	You should never set this.
	 * @param xQuery $prev The parent xQuery instance of this document.
	 * 	You should never set this.
	 */
	public function __construct($doc, $selector = '', xQuery $prev = null) {
		$this->createDOMDocument($doc);

		$this->length = $this->doc->childNodes->length;
		$this->selector = $selector;
		$this->prev = $prev;
	}

	/**
	 * Creates the DOMDocument
	 *
	 * @param mixed $doc
	 * @return void
	 */
	private function createDOMDocument($doc) {
		$_doc = new DOMDocument();
		$_doc->validateOnParse = true;
		$_doc->preserveWhiteSpace = false;
		$_doc->strictErrorChecking = false;

		if (is_string($doc)) {
			if (preg_match('#^<([a-zA-Z]+)#', trim($doc))) {
				@$_doc->loadHTML($doc);
			}
			else {
				@$_doc->loadHTMLFile($doc);
			}
		}
		else {
			$class = get_class($doc);
			if ($class == 'DOMElement') {
				$_doc->appendChild( $_doc->importNode($doc, true) );
			}
			else if (in_array($class, ['DOMDocument', 'DOMNodeList'])) {
				foreach ($doc as $node) {
					$_doc->appendChild( $_doc->importNode($node, true) );
				}
			}
		}

		$this->doc = $_doc;
	}

	/**
	 * Queries the DOM with a CSS selector
	 *
	 * @param string $selector
	 * @param int $index
	 * @return xQuery
	 */
	public function find($selector, $index = -1) {
		$xpath = self::parse($selector);

		$dxpath = new DOMXPath($this->doc);
		$res = $dxpath->query($xpath);
		if ($index != -1) {
			$res = $res->item($index);
		}

		return self::mkRes($res, $selector);
	}

	/**
	 * Selects the $index node in the document
	 *
	 * @param int $index
	 * @return xQuery
	 */
	public function eq($index) {
		return self::mkRes($this->doc->childNodes->item($index), $this->selector);
	}

	/**
	 * Returns the HTML version of the document
	 *
	 * @return string
	 */
	public function html() {
		return $this->doc->saveHtml();
	}

	/**
	 * Returns the parent node of the document
	 *
	 * Note: This requires the current document is the result of
	 * a xQuery->find()
	 *
	 * @return xQuery
	 */
	public function parent() {
		//TODO implement
	}

	/**
	 * Returns a parent node of the document filtered by a selector
	 *
	 * Note: This requires the current document is the result of
	 * a xQuery->find()
	 *
	 * @param string $selector
	 * @return xQuery
	 */
	public function parents($selector) {
		$xpath = self::parse($selector);

		//TODO implement
	}

	/**
	 * Parses a CSS selector
	 *
	 * @return string
	 */
	private function parse($selector) {
		$selector = trim($selector);
		$l = strlen($selector);
		$i = 0;
		$rules = [];
		$crt_rule = ['direct_child' => false];

		while ($i < $l) {
			$crt_char = substr($selector, $i, 1);
			if (array_key_exists($crt_char, self::$ATTRS)) {
				$type = self::$ATTRS[$crt_char];
				$value = self::extractClassOrId($selector, $l, $i);

				if ($type == 'class') {
					if (!isset($crt_rule['class'])) {
						$crt_rule['class'] = [];
					}
					$crt_rule['class'][] = $value;
				}
				else {
					$crt_rule[$type] = $value;
				}
			}
			elseif (preg_match('/[a-zA-Z]/', $crt_char)) {
				$crt_rule['tagName'] = self::extractTagName($selector, $l, $i);
			}
			elseif ($crt_char == '*') {
				$crt_rule['tagName'] = '*';
				$i++;
			}
			else {
				$rules[] = $crt_rule;
				$crt_rule = ['direct_child' => self::findNextRule($selector, $l, $i)];
			}
		}
		$rules[] = $crt_rule;

		return self::rules2Xpath($rules);
	}

	/**
	 * Extracts a class or ID name
	 *
	 * @param string $selector
	 * @param int $l
	 * @param int $offset
	 * @return string
	 */
	private function extractClassOrId($selector, $l, &$offset) {
		$i = 1;
		while ($i < $l && preg_match('/[a-zA-Z0-9_-]/', substr($selector, $offset + $i, 1))) {
			$i++;
		}

		$attr = substr($selector, $offset + 1, $i - 1);
		$offset += $i;
		return $attr;
	}

	/**
	 * Extracts a tag name
	 *
	 * @param string $selector
	 * @param int $l
	 * @param int $offset
	 * @return string
	 */
	private function extractTagName($selector, $l, &$offset) {
		$i = 1;
		while ($i < $l && preg_match('/[a-zA-Z]/', substr($selector, $offset + $i, 1))) {
			$i++;
		}

		$attr = substr($selector, $offset, $i);
		$offset += $i;
		return $attr;
	}

	/**
	 * Strips white spaces to the next CSS rule
	 *
	 * @param string $selector
	 * @param int $l
	 * @param int $offset
	 * @return bool is the new selector a direct child?
	 */
	private function findNextRule($selector, $l, &$offset) {
		$is_direct_child = false;
		$i = 0;
		while ($i < $l && (substr($selector, $offset + $i, 1) == ' ' || (!$is_direct_child && substr($selector, $offset + $i, 1) == '>'))) {
			if (!$is_direct_child && substr($selector, $offset + $i, 1) == '>') {
				$is_direct_child = true;
			}
			$i++;
		}

		$offset += $i;
		return $is_direct_child;
	}

	/**
	 * Translates the set of rules in a CSS selector into a xpath expression
	 *
	 * @param array $rules
	 * @return string
	 */
	private function rules2Xpath($rules) {
		$xpath = '';

		foreach ($rules as $rule) {
			$properties = [];

			if ($rule['direct_child']) {$xpath .= '/';}
			else                       {$xpath .= '//';}

			if (isset($rule['tagName'])) {$xpath .= $rule['tagName'];}
			else                         {$xpath .= '*';}

			if (isset($rule['class'])) {
				foreach ($rule['class'] as $class) {
					$properties[] = 'contains(concat(" ", @class, " "), concat(" ", "' . $class . '", " "))';
				}
			}

			if (isset($rule['id'])) {
				$properties[] = '@id="' . $rule['id'] . '"';
			}

			if (count($properties) > 0) {
				$xpath .= '[' . implode(' and ', $properties) . ']';
			}
		}

		return $xpath;
	}

	/**
	 * Creates the object to return
	 *
	 * @param DOMNodeList $res
	 * @param string $selector
	 * @return xQuery
	 */
	private function mkRes($res, $selector) {
		return new xQuery($res, $selector, $this);
	}

}

?>
