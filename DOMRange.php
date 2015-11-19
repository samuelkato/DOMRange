<?php
/**
 * 
 * traducao do codigo encontrado em phpcrossref.com/xref/sugarcrm///include/javascript/tiny_mce/classes/dom/Range.js.html
 * de javascript para php em 2015/06/26
 *
 */
class DOMRange{
	const EXTRACT = 0;
	const CLONAR = 1;
	const DELETE = 2;
	
	const START_TO_START = 0;
	const START_TO_END = 1;
	const END_TO_END = 2;
	const END_TO_START = 3;
	
	private $doc;
	
	public $START_CONTAINER;
	public $START_OFFSET;
	public $END_CONTAINER;
	public $END_OFFSET;
	public $collapsed;
	public $commonAncestorContainer;
	
	public function __construct(DOMDocument $doc) {
		$this->doc = $doc;
		$this->START_CONTAINER = $doc;
		$this->START_OFFSET = 0;
		$this->END_CONTAINER = $doc;
		$this->END_OFFSET = 0;
		$this->collapsed = true;
		$this->commonAncestorContainer = $doc;
	}
	
	public function getFlatText( $ns = '', $br_ = false ){
		$txt = '';
		foreach($this->getTextNodes( $ns ) as $ndTxt){
			$txt .= $ndTxt->nodeType === 3 ? $ndTxt->nodeValue : " ";
		}
		return $txt;
	}
	
	public function offsetToTextNode( $offset, $nextNode_ = true ){
		//$liText = $this->getTextNodes();
		return $this->recOffset($this->doc, max($offset,0), $nextNode_);
	}
	
	private function recOffset(DOMNode $ndPai, $offset, $nextNode_, &$len = 0){
		foreach( $ndPai->childNodes as $ndFilho ){
			if( $ndFilho->nodeType === 3 ){
				$txtLen = mb_strlen( $ndFilho->nodeValue );
				$len += $txtLen;
				if( $len > $offset || ($len >= $offset && !$nextNode_) ) return ['nd'=>$ndFilho,'offset'=>$txtLen - ($len - $offset)];
			}else if($ndFilho->nodeType === 1){
				if($ret = $this->recOffset($ndFilho, $offset, $nextNode_, $len))return $ret;
			}
		}
		return false;
	}
	
	public function getTextNodes( $ns = '' ){
		$aResp = array();
		$xpath = new DOMXPath($this->doc);
		
		if($ns){
			$xpath->registerNamespace('ns', $ns);
			//$li = $xpath->query(".//ns:*/text()|.//ns:br");
			$li = $xpath->query(".//ns:*/text()");
		}else{
			//$li = $xpath->query(".//text()|//br");
			$li = $xpath->query(".//text()");
		}
		foreach($li as $ndTxt) $aResp[] = $ndTxt;
		return $aResp;
	}
	
	
	public function createDocumentFragment() {
		return $this->doc->createDocumentFragment();
	}

	public function setStart(DOMNode $n, $o) {
		if(!$n instanceof DOMNode)throw new Exception("arg 1 deve ser um DOMNode");
		$this->_setEndPoint(true, $n, $o);
	}

	public function setEnd(DOMNode $n, $o) {
		if(!$n instanceof DOMNode)throw new Exception("arg 1 deve ser um DOMNode");
		$this->_setEndPoint(false, $n, $o);
	}

	public function setStartBefore(DOMNode $n) {
		$this->setStart($n->parentNode, $this->nodeIndex($n));
	}

	public function setStartAfter(DOMNode $n) {
		$this->setStart($n->parentNode, $this->nodeIndex($n) + 1);
	}

	public function setEndBefore(DOMNode $n) {
		$this->setEnd($n->parentNode, $this->nodeIndex($n));
	}

	public function setEndAfter(DOMNode $n) {
		$this->setEnd($n->parentNode, $this->nodeIndex($n) + 1);
	}

	public function collapse($ts) {
		if ($ts) {
			$this->END_CONTAINER = $this->START_CONTAINER;
			$this->END_OFFSET = $this->START_OFFSET;
		} else {
			$this->START_CONTAINER = $this->END_CONTAINER;
			$this->START_OFFSET = $this->END_OFFSET;
		}
		$this->collapsed = true;
	}

	public function selectNode(DOMNode $n) {
		$this->setStartBefore($n);
		$this->setEndAfter($n);
	}

	public function selectNodeContents(DOMNode $n) {
		$this->setStart($n, 0);
		$this->setEnd($n, $n->nodeType === 1 ? $n->childNodes->length : mb_strlen($n->nodeValue));
	}

	public function compareBoundaryPoints($h, DOMRange $r) {
		$sc = $this->START_CONTAINER;
		$so = $this->START_OFFSET;
		$ec = $this->END_CONTAINER;
		$eo = $this->END_OFFSET;
		$rsc = $r->START_CONTAINER;
		$rso = $r->START_OFFSET;
		$rec = $r->END_CONTAINER;
		$reo = $r->END_OFFSET;

		// Check START_TO_START
		if ($h === self::START_TO_START) {
			return $this->_compareBoundaryPoints($sc, $so, $rsc, $rso);
		}

		// Check START_TO_END
		if ($h === self::START_TO_ETART) {
			return $this->_compareBoundaryPoints($ec, $eo, $rsc, $rso);
		}

		// Check END_TO_END
		if ($h === self::END_TO_END) {
			return $this->_compareBoundaryPoints($ec, $eo, $rec, $reo);
		}

		// Check END_TO_START
		if ($h === self::END_TO_START) {
			return $this->_compareBoundaryPoints($sc, $so, $rec, $reo);
		}
	}

	public function deleteContents() {
		$this->_traverse(self::DELETE);
	}

	public function extractContents() {
		return $this->_traverse(self::EXTRACT);
	}

	public function cloneContents() {
		return $this->_traverse(self::CLONAR);
	}

	public function insertNode(DOMNode $n) {
		$startContainer = $this->START_CONTAINER;
		$startOffset = $this->START_OFFSET;
		$nn;
		$o;

		// Node is TEXT_NODE or CDATA
		if (($startContainer->nodeType === 3 || $startContainer->nodeType === 4) && $startContainer->nodeValue) {
			if (!$startOffset) {
				// At the start of text
				$startContainer->parentNode->insertBefore($n, $startContainer);
			} else if ($startOffset >= mb_strlen($startContainer->nodeValue)) {
				// At the end of text
				$this->insertAfter($n, $startContainer);
			} else {
				// Middle, need to split
				$nn = $startContainer->splitText($startOffset);
				$startContainer->parentNode->insertBefore($n, $nn);
			}
		} else {
			// Insert element node
			if ($startContainer->childNodes->length > 0) {
				$o = $startContainer->childNodes->item($startOffset);
			}

			if ($o) {
				$startContainer->insertBefore($n, $o);
			} else {
				if ($startContainer->nodeType === 3) {
					$this->insertAfter($n, $startContainer);
				} else {
					$startContainer->appendChild($n);
				}
			}
		}
	}

	public function surroundContents(DOMNode $n) {
		$f = $this->extractContents();

		$this->insertNode($n);
		$n->appendChild($f);
		$this->selectNode($n);
	}

	public function cloneRange() {
		$cl = new DOMRange($this->doc);
		$cl->START_CONTAINER = $this->START_CONTAINER;
		$cl->START_OFFSET = $this->START_OFFSET;
		$cl->END_CONTAINER = $this->END_CONTAINER;
		$cl->END_OFFSET = $this->END_OFFSET;
		$cl->collapsed = $this->collapsed;
		$cl->commonAncestorContainer = $this->commonAncestorContainer;
		
		return $cl;
	}

	private function _getSelectedNode($container, $offset) {
		$child;

		if ($container->nodeType === 3 ) {
			return $container;
		}

		if ($offset < 0) {
			return $container;
		}

		$child = $container->firstChild;
		while ($child && $offset > 0) {
			--$offset;
			$child = $child->nextSibling;
		}

		if ($child) {
			return $child;
		}

		return $container;
	}

	private function _isCollapsed() {
		return ($this->START_CONTAINER === $this->END_CONTAINER && $this->START_OFFSET === $this->END_OFFSET);
	}

	private function _compareBoundaryPoints(DOMNode $containerA, $offsetA, DOMNode $containerB, $offsetB) {
		$c;
		$offsetC;
		$n;
		$cmnRoot;
		$childA;
		$childB;

		// In the first case the boundary-points have the same container. A is before B
		// if its offset is less than the offset of B, A is equal to B if its offset is
		// equal to the offset of B, and A is after B if its offset is greater than the
		// offset of B.
		if ($containerA === $containerB) {
			if ($offsetA === $offsetB) {
				return 0; // equal
			}
			if ($offsetA < $offsetB) {
				return -1; // before
			}

			return 1; // after
		}

		// In the second case a child node C of the container of A is an ancestor
		// container of B. In this case, A is before B if the offset of A is less than or
		// equal to the index of the child node C and A is after B otherwise.
		$c = $containerB;
		while ($c && $c->parentNode !== $containerA) {
			$c = $c->parentNode;
		}

		if ($c) {
			$offsetC = 0;
			$n = $containerA->firstChild;

			while ($n !== $c && $offsetC < $offsetA) {
				$offsetC++;
				$n = $n->nextSibling;
			}

			if ($offsetA <= $offsetC) {
				return -1; // before
			}

			return 1; // after
		}

		// In the third case a child node C of the container of B is an ancestor container
		// of A. In this case, A is before B if the index of the child node C is less than
		// the offset of B and A is after B otherwise.
		$c = $containerA;
		while ($c && $c->parentNode !== $containerB) {
			$c = $c->parentNode;
		}

		if ($c) {
			$offsetC = 0;
			$n = $containerB->firstChild;

			while ($n !== $c && $offsetC < $offsetB) {
				$offsetC++;
				$n = $n->nextSibling;
			}

			if ($offsetC < $offsetB) {
				return -1; // before
			}

			return 1; // after
		}

		// In the fourth case, none of three other cases hold: the containers of A and B
		// are siblings or descendants of sibling nodes. In this case, A is before B if
		// the container of A is before the container of B in a pre-order traversal of the
		// Ranges' context tree and A is after B otherwise.
		$cmnRoot = $this->findCommonAncestor($containerA, $containerB);
		$childA = $containerA;

		while ($childA && $childA->parentNode !== $cmnRoot) {
			$childA = $childA->parentNode;
		}

		if (!$childA) {
			$childA = $cmnRoot;
		}

		$childB = $containerB;
		while ($childB && $childB->parentNode !== $cmnRoot) {
			$childB = $childB->parentNode;
		}

		if (!$childB) {
			$childB = $cmnRoot;
		}

		if ($childA === $childB) {
			return 0; // equal
		}

		$n = $cmnRoot->firstChild;
		while ($n) {
			if ($n === $childA) {
				return -1; // before
			}

			if ($n === $childB) {
				return 1; // after
			}

			$n = $n->nextSibling;
		}
	}

	private function _setEndPoint($st, DOMNode $n, $o) {
		$ec;
		$sc;

		if ($st) {
			$this->START_CONTAINER = $n;
			$this->START_OFFSET = $o;
		} else {
			$this->END_CONTAINER = $n;
			$this->END_OFFSET = $o;
		}
		
		//die($this->doc);

		// If one boundary-point of a Range is set to have a root container
		// other than the current one for the Range, the Range is collapsed to
		// the new position. This enforces the restriction that both boundary-
		// points of a Range must have the same root container.
		$ec = $this->END_CONTAINER;
		while ($ec->parentNode) {
			$ec = $ec->parentNode;
		}

		$sc = $this->START_CONTAINER;
		while ($sc->parentNode) {
			$sc = $sc->parentNode;
		}

		if ($sc === $ec) {
			// The start position of a Range is guaranteed to never be after the
			// end position. To enforce this restriction, if the start is set to
			// be at a position after the end, the Range is collapsed to that
			// position.
			
			
			if ($this->_compareBoundaryPoints($this->START_CONTAINER, $this->START_OFFSET, $this->END_CONTAINER, $this->END_OFFSET) > 0) {
				$this->collapse($st);
			}
		} else {
			$this->collapse($st);
		}

		$this->collapsed = $this->_isCollapsed();
		$this->commonAncestorContainer = $this->findCommonAncestor($this->START_CONTAINER, $this->END_CONTAINER);
	}

	private function _traverse($how) {
		$c;
		$endContainerDepth = 0;
		$startContainerDepth = 0;
		$p;
		$depthDiff;
		$startNode;
		$endNode;
		$sp;
		$ep;

		if ($this->START_CONTAINER === $this->END_CONTAINER) {
			return $this->_traverseSameContainer($how);
		}

		for ($c = $this->END_CONTAINER, $p = $c->parentNode; $p; $c = $p, $p = $p->parentNode) {
			if ($p === $this->START_CONTAINER) {
				return $this->_traverseCommonStartContainer($c, $how);
			}

			++$endContainerDepth;
		}

		for ($c = $this->START_CONTAINER, $p = $c->parentNode; $p; $c = $p, $p = $p->parentNode) {
			if ($p === $this->END_CONTAINER) {
				return $this->_traverseCommonEndContainer($c, $how);
			}

			++$startContainerDepth;
		}

		$depthDiff = $startContainerDepth - $endContainerDepth;

		$startNode = $this->START_CONTAINER;
		while ($depthDiff > 0) {
			$startNode = $startNode->parentNode;
			$depthDiff--;
		}

		$endNode = $this->END_CONTAINER;
		while ($depthDiff < 0) {
			$endNode = $endNode->parentNode;
			$depthDiff++;
		}

		// ascend the ancestor hierarchy until we have a common parent.
		for ($sp = $startNode->parentNode, $ep = $endNode->parentNode; $sp !== $ep; $sp = $sp->parentNode, $ep = $ep->parentNode) {
			$startNode = $sp;
			$endNode = $ep;
		}
		
		return $this->_traverseCommonAncestors($startNode, $endNode, $how);
	}

	private function _traverseSameContainer($how) {
		$frag;
		$s;
		$sub;
		$n;
		$cnt;
		$sibling;
		$xferNode;
		$start;
		$len;

		if ($how !== self::DELETE) {
			$frag = $this->createDocumentFragment();
		}

		// If selection is empty, just return the fragment
		if ($this->START_OFFSET === $this->END_OFFSET) {
			return $frag;
		}

		// Text node needs special case handling
		if ($this->START_CONTAINER->nodeType === 3 ) {
			// get the substring
			$s = $this->START_CONTAINER->nodeValue;
			$len = $this->END_OFFSET - $this->START_OFFSET;
			$sub = mb_substr($s, $this->START_OFFSET, $len, "utf-8");
			
			// set the original text node to its new value
			if ($how !== self::CLONAR) {
				$n = $this->START_CONTAINER;
				$start = $this->START_OFFSET;
				
				
				if ($start === 0 && $len >= mb_strlen($n->nodeValue)) {
					$n->parentNode->removeChild($n);
				} else {
					$n->deleteData($start, $len);
				}

				// Nothing is partially selected, so collapse to start point
				$this->collapse(TRUE);
			}

			if ($how === self::DELETE) {
				return;
			}

			if (mb_strlen($sub) > 0) {
				$frag->appendChild($this->doc->createTextNode($sub));
			}

			return $frag;
		}

		// Copy nodes between the start/end offsets.
		$n = $this->_getSelectedNode($this->START_CONTAINER, $this->START_OFFSET);
		$cnt = $this->END_OFFSET - $this->START_OFFSET;

		while ($n && $cnt > 0) {
			$sibling = $n->nextSibling;
			$xferNode = $this->_traverseFullySelected($n, $how);

			if ($frag) {
				$frag->appendChild($xferNode);
			}

			--$cnt;
			$n = $sibling;
		}

		// Nothing is partially selected, so collapse to start point
		if ($how !== self::CLONAR) {
			$this->collapse(TRUE);
		}

		return $frag;
	}

	private function _traverseCommonStartContainer($endAncestor, $how) {
		$frag;
		$n;
		$endIdx;
		$cnt;
		$sibling;
		$xferNode;

		if ($how !== DELETE) {
			$frag = $this->createDocumentFragment();
		}

		$n = $this->_traverseRightBoundary($endAncestor, $how);

		if ($frag) {
			$frag->appendChild($n);
		}

		$endIdx = $this->$snodeIndex($endAncestor);
		$cnt = $endIdx - $this->START_OFFSET;

		if ($cnt <= 0) {
			// Collapse to just before the endAncestor, which
			// is partially selected.
			if ($how !== self::CLONAR) {
				$this->setEndBefore($endAncestor);
				$this->collapse(FALSE);
			}

			return $frag;
		}

		$n = $endAncestor->previousSibling;
		while ($cnt > 0) {
			$sibling = $n->previousSibling;
			$xferNode = $this->_traverseFullySelected($n, $how);

			if ($frag) {
				$frag->insertBefore($xferNode, $frag->firstChild);
			}

			--$cnt;
			$n = $sibling;
		}

		// Collapse to just before the endAncestor, which
		// is partially selected.
		if ($how !== self::CLONAR) {
			$this->setEndBefore($endAncestor);
			$this->collapse(FALSE);
		}

		return $frag;
	}

	private function _traverseCommonEndContainer($startAncestor, $how) {
		$frag;
		$startIdx;
		$n;
		$cnt;
		$sibling;
		$xferNode;

		if ($how !== self::DELETE) {
			$frag = $this->createDocumentFragment();
		}

		$n = $this->_traverseLeftBoundary($startAncestor, $how);
		if ($frag) {
			$frag->appendChild($n);
		}

		$startIdx = $this->nodeIndex($startAncestor);
		++$startIdx; // Because we already traversed it

		$cnt = $this->END_OFFSET - $startIdx;
		$n = $startAncestor->nextSibling;
		while ($n && $cnt > 0) {
			$sibling = $n->nextSibling;
			$xferNode = $this->_traverseFullySelected($n, $how);

			if ($frag) {
				$frag->appendChild($xferNode);
			}

			--$cnt;
			$n = $sibling;
		}

		if ($how !== self::CLONAR) {
			$this->setStartAfter($startAncestor);
			$this->collapse(TRUE);
		}

		return $frag;
	}

	private function _traverseCommonAncestors(DOMNode $startAncestor, DOMNode $endAncestor, $how) {
		$n;
		$frag;
		$commonParent;
		$startOffset;
		$endOffset;
		$cnt;
		$sibling;
		$nextSibling;

		if ($how !== self::DELETE) {
			$frag = $this->createDocumentFragment();
		}

		$n = $this->_traverseLeftBoundary($startAncestor, $how);
		if ($frag) {
			$frag->appendChild($n);
		}
		

		$commonParent = $startAncestor->parentNode;
		$startOffset = $this->nodeIndex($startAncestor);
		$endOffset = $this->nodeIndex($endAncestor);
		++$startOffset;

		$cnt = $endOffset - $startOffset;
		$sibling = $startAncestor->nextSibling;

		while ($cnt > 0) {
			$nextSibling = $sibling->nextSibling;
			$n = $this->_traverseFullySelected($sibling, $how);

			if ($frag) {
				$frag->appendChild($n);
			}

			$sibling = $nextSibling;
			--$cnt;
		}

		$n = $this->_traverseRightBoundary($endAncestor, $how);

		if ($frag) {
			$frag->appendChild($n);
		}

		if ($how !== self::CLONAR) {
			$this->setStartAfter($startAncestor);
			$this->collapse(TRUE);
		}

		return $frag;
	}

	private function _traverseRightBoundary($root, $how) {
		$next = $this->_getSelectedNode($this->END_CONTAINER, $this->END_OFFSET - 1);
		$parent;
		$clonedParent;
		$prevSibling;
		$clonedChild;
		$clonedGrandParent;
		$isFullySelected = $next !== $this->END_CONTAINER;

		if ($next === $root) {
			return $this->_traverseNode($next, $isFullySelected, FALSE, $how);
		}

		$parent = $next->parentNode;
		$clonedParent = $this->_traverseNode($parent, FALSE, FALSE, $how);

		while ($parent) {
			while ($next) {
				$prevSibling = $next->previousSibling;
				$clonedChild = $this->_traverseNode($next, $isFullySelected, FALSE, $how);

				if ($how !== self::DELETE) {
					$clonedParent->insertBefore($clonedChild, $clonedParent->firstChild);
				}

				$isFullySelected = TRUE;
				$next = $prevSibling;
			}

			if ($parent === $root) {
				return $clonedParent;
			}

			$next = $parent->previousSibling;
			$parent = $parent->parentNode;

			$clonedGrandParent = $this->_traverseNode($parent, FALSE, FALSE, $how);

			if ($how !== self::DELETE) {
				$clonedGrandParent->appendChild($clonedParent);
			}

			$clonedParent = $clonedGrandParent;
		}
	}

	private function _traverseLeftBoundary($root, $how) {
		$next = $this->_getSelectedNode($this->START_CONTAINER, $this->START_OFFSET);
		$isFullySelected = $next !== $this->START_CONTAINER;
		$parent;
		$clonedParent;
		$nextSibling;
		$clonedChild;
		$clonedGrandParent;

		if ($next === $root) {
			return $this->_traverseNode($next, $isFullySelected, TRUE, $how);
		}

		$parent = $next->parentNode;
		$clonedParent = $this->_traverseNode($parent, FALSE, TRUE, $how);

		while ($parent) {
			while ($next) {
				$nextSibling = $next->nextSibling;
				$clonedChild = $this->_traverseNode($next, $isFullySelected, TRUE, $how);

				if ($how !== self::DELETE) {
					$clonedParent->appendChild($clonedChild);
				}

				$isFullySelected = TRUE;
				$next = $nextSibling;
			}

			if ($parent === $root) {
				return $clonedParent;
			}

			$next = $parent->nextSibling;
			$parent = $parent->parentNode;

			$clonedGrandParent = $this->_traverseNode($parent, FALSE, TRUE, $how);

			if ($how !== self::DELETE) {
				$clonedGrandParent->appendChild($clonedParent);
			}

			$clonedParent = $clonedGrandParent;
		}
	}

	private function _traverseNode(DOMNode $n, $isFullySelected, $isLeft, $how) {
		$txtValue;
		$newNodeValue;
		$oldNodeValue;
		$offset;
		$newNode;

		if ($isFullySelected) {
			return $this->_traverseFullySelected($n, $how);
		}

		if ($n->nodeType === 3 ) {
			$txtValue = $n->nodeValue;

			if ($isLeft) {
				$offset = $this->START_OFFSET;
				$newNodeValue = mb_substr($txtValue,$offset);
				$oldNodeValue = mb_substr($txtValue, 0, $offset);
			} else {
				$offset = $this->END_OFFSET;
				$newNodeValue = mb_substr($txtValue, 0, $offset);
				$oldNodeValue = mb_substr($txtValue, $offset);
			}

			if ($how !== self::CLONAR) {
				$n->nodeValue = $oldNodeValue;
			}

			if ($how === self::DELETE) {
				return;
			}

			$newNode = $this->clonar($n, FALSE);
			$newNode->nodeValue = $newNodeValue;

			return $newNode;
		}

		if ($how === self::DELETE) {
			return;
		}

		return $this->clonar($n, FALSE);
	}

	private function _traverseFullySelected(DOMNode $n, $how) {
		if ($how !== self::DELETE) {
			return $how === self::CLONAR ? $this->clonar($n, TRUE) : $n;
		}

		$n->parentNode->removeChild($n);
	}
	
	private function findCommonAncestor(DOMNode $a, DOMNode $b) {
		while($a){
			while($b){
				if($a === $b){
					return $a;
				}
				$b = $b->parentNode;
			}
			$a = $a->parentNode;
		}
		return null;
	}
	
	private function clonar(DOMNode $nd, $deep = false){
		return $nd->cloneNode($deep);
	}
	
	private function nodeIndex(DOMNode $n){
		$cnt = 0;
		foreach($n->parentNode->childNodes as $n2){
			if($n->isSameNode($n2))return $cnt;
			$cnt++;
		}
		return false;
	}

	private function insertAfter(DOMNode $n, DOMNode $cont){
		$pai = $cont->parentNode;
		if($cont === $pai->lastChild)$pai->appendChild($n);
		else $pai->insertBefore($n,$cont->nextSibling);
	}
}
/*
$doc = new DOMDocument();
$doc->loadXML('<sa aoi="fda"><b>Samuel</b> Issamu <b>Kato</b></sa>');
$range = new DOMRange($doc);

$range->setStart($doc->documentElement->firstChild->firstChild, 1);
$range->setEnd($doc->documentElement->childNodes->item(1), 2);
$frag = $range->extractContents();

echo $doc->saveXML($frag);
*/