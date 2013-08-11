<?php

function xQuery($doc) {
	return new xQuery($doc);
}

class xQuery {

	public static $ATTRS = array('#' => 'id', '.' => 'class');

	private $doc;

	public function __construct($doc) {
		if(is_string($doc)) {
			$this->doc = $this->createDOMDocument();
			if(preg_match('#^<([a-zA-Z]+)#', trim($doc))) {
				@$this->doc->loadHTML($doc);
			}
			else {
				@$this->doc->loadHTMLFile($doc);
			}

		}
		else {
			$this->doc = $this->createDOMDocument($doc);
		}
	}


	private function createDOMDocument($nodes = '') {
		$doc = new DOMDocument();
		$doc->validateOnParse = true;
		$doc->preserveWhiteSpace = false;
		$doc->strictErrorChecking = false;

		if($nodes != '') {
			@$doc->loadHTML('<root></root>');
			if(get_class($nodes) == 'DOMElement') {
				$doc->documentElement->appendChild( $doc->importNode($nodes, true) );
			}
			else {
				foreach($nodes as $node) {
					$doc->documentElement->appendChild( $doc->importNode($node, true) );
				}
			}
		}

		return $doc;
	}

	public function get($css = '', $index = -1) {
		$xpath = self::parse($css);

		$dxpath = new DOMXPath($this->doc);

		if ($index == -1) {
			return new xQuery($dxpath->query($xpath));
		}
		else {
			return new xQuery($dxpath->query($xpath)->item($index));
		}
	}

	public function html() {
		return $this->doc->textContent;
	}

	private function parse($css) {
		$css = trim($css);
		$l = strlen($css);
		$i = 0;
		$xpath = '';
		$rules = [];
		$crt_rule = ['direct_child' => false];

		while ($i < $l) {
			if (array_key_exists(substr($css, $i, 1), self::$ATTRS)) {
				$type = self::$ATTRS[substr($css, $i, 1)];
				$value = self::extract_class_or_id($css, $l, $i);

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
			elseif (preg_match('/[a-zA-Z]/', substr($css, $i, 1))) {
				$crt_rule['tagName'] = self::extract_tag_name($css, $l, $i);
			}
			else {
				$rules[] = $crt_rule;
				$crt_rule = [];
				$crt_rule['direct_child'] = self::go_to_next($css, $l, $i);
			}
		}
		$rules[] = $crt_rule;

		return self::rules_to_xpath($rules);
	}

	private function extract_class_or_id($css, $l, &$offset) {
		$i = 1;
		while ($i < $l && preg_match('/[a-zA-Z0-9_-]/', substr($css, $offset + $i, 1))) {
			$i++;
		}

		$attr = substr($css, $offset + 1, $i - 1);
		$offset += $i;
		return $attr;
	}

	private function extract_tag_name($css, $l, &$offset) {
		$i = 1;
		while ($i < $l && preg_match('/[a-zA-Z]/', substr($css, $offset + $i, 1))) {
			$i++;
		}

		$attr = substr($css, $offset, $i);
		$offset += $i;
		return $attr;
	}

	private function go_to_next($css, $l, &$offset) {
		$is_direct_child = false;
		$i = 0;
		while ($i < $l && (substr($css, $offset + $i, 1) == ' ' || (!$is_direct_child && substr($css, $offset + $i, 1) == '>'))) {
			if (!$is_direct_child && substr($css, $offset + $i, 1) == '>') {
				$is_direct_child = true;
			}
			$i++;
		}

		$offset += $i;
		return $is_direct_child;
	}

	private function rules_to_xpath($rules) {
		$xpath = '';

		foreach ($rules as $rule) {
			if ($rule['direct_child']) {$xpath .= '/';}
			else                       {$xpath .= '//';}

			if (isset($rule['tagName'])) {$xpath .= $rule['tagName'];}

			if (isset($rule['class'])) {
				if (!isset($rule['tagName'])) {
					$xpath .= '*';
				}
				$classes = [];
				foreach ($rule['class'] as $class) {
					$classes[] = 'contains(concat(" ", normalize-space(@class), " "), concat(" ", "' . $class . '", " "))';
				}
				$xpath .= '[' . implode(' and ', $classes) . ']';
			}

			if (isset($rule['id'])) {
				if (!isset($rule['tagName'])) {
					$xpath .= '*';
				}
				$xpath .= '[@id="' . $rule['id'] . '"]';
			}
		}

		return $xpath;
	}

}

?>
