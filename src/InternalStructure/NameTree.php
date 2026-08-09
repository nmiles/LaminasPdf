<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   LaminasPdf
 */

namespace LaminasPdf\InternalStructure;

use ArrayAccess;
use Countable;
use Iterator;
use LaminasPdf\Exception;
use LaminasPdf\InternalType;

/**
 * PDF name tree representation class
 *
 * @todo implement lazy resource loading so resources will be really loaded at access time
 *
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Internal
 */
class NameTree implements ArrayAccess, Countable, Iterator
{
    /**
     * Elements
     * Array of name => object tree entries
     *
     * @var array
     */
    protected $_items = [];

    /**
     * Object constructor
     *
     * @param $rootDictionary $rootDictionary of name dictionary
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function __construct(InternalType\AbstractTypeObject $rootDictionary)
    {
        if ($rootDictionary->getType() != InternalType\AbstractTypeObject::TYPE_DICTIONARY) {
            throw new Exception\CorruptedPdfException('Name tree root must be a dictionary.');
        }

        $intermediateNodes = [];
        $leafNodes = [];
        if ($rootDictionary->Kids !== null) {
            $intermediateNodes[] = $rootDictionary;
        } else {
            $leafNodes[] = $rootDictionary;
        }

        while (count($intermediateNodes) != 0) {
            $newIntermediateNodes = [];
            foreach ($intermediateNodes as $node) {
                foreach ($node->Kids->items as $childNode) {
                    if ($childNode->Kids !== null) {
                        $newIntermediateNodes[] = $childNode;
                    } else {
                        $leafNodes[] = $childNode;
                    }
                }
            }
            $intermediateNodes = $newIntermediateNodes;
        }

        foreach ($leafNodes as $leafNode) {
            $destinationsCount = (is_countable($leafNode->Names->items) ? count($leafNode->Names->items) : 0) / 2;
            for ($count = 0; $count < $destinationsCount; $count++) {
                $this->_items[$leafNode->Names->items[$count * 2]->value] = $leafNode->Names->items[$count * 2 + 1];
            }
        }
    }

    public function current(): mixed
    {
        return current($this->_items);
    }


    public function next(): void
    {
        next($this->_items);
    }


    public function key(): mixed
    {
        return key($this->_items);
    }


    public function valid(): bool
    {
        return current($this->_items) !== false;
    }


    public function rewind(): void
    {
        reset($this->_items);
    }


    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->_items);
    }


    public function offsetGet($offset): mixed
    {
        return $this->_items[$offset];
    }


    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            $this->_items[] = $value;
        } else {
            $this->_items[$offset] = $value;
        }
    }


    public function offsetUnset($offset): void
    {
        unset($this->_items[$offset]);
    }


    public function clear(): void
    {
        $this->_items = [];
    }

    public function count(): int
    {
        return count($this->_items);
    }
}
