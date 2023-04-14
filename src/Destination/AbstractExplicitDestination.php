<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   LaminasPdf
 */

namespace LaminasPdf\Destination;

use LaminasPdf\Exception;
use LaminasPdf\InternalType;

/**
 * Abstract PDF explicit destination representation class
 *
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Destination
 */
abstract class AbstractExplicitDestination extends AbstractDestination
{
    /**
     * Destination description array
     *
     * @var \LaminasPdf\InternalType\ArrayObject
     */
    protected $_destinationArray;

    /**
     * True if it's a remote destination
     *
     * @var boolean
     */
    protected $_isRemote;

    /**
     * AbstractExplicitDestination destination object constructor
     *
     * @param \LaminasPdf\InternalType\AbstractTypeObject $destinationArray
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function __construct(InternalType\AbstractTypeObject $destinationArray)
    {
        if ($destinationArray->getType() != InternalType\AbstractTypeObject::TYPE_ARRAY) {
            throw new Exception\CorruptedPdfException('Explicit destination resource Array must be a direct or an indirect array object.');
        }

        $this->_destinationArray = $destinationArray;

        switch (is_countable($this->_destinationArray->items) ? count($this->_destinationArray->items) : 0) {
            case 0:
                throw new Exception\CorruptedPdfException('Destination array must contain a page reference.');
                break;

            case 1:
                throw new Exception\CorruptedPdfException('Destination array must contain a destination type name.');
                break;

            default:
                // Do nothing
                break;
        }

        switch ($this->_destinationArray->items[0]->getType()) {
            case InternalType\AbstractTypeObject::TYPE_NUMERIC:
                $this->_isRemote = true;
                break;

            case InternalType\AbstractTypeObject::TYPE_DICTIONARY:
                $this->_isRemote = false;
                break;

            default:
                throw new Exception\CorruptedPdfException('Destination target must be a page number or page dictionary object.');
                break;
        }
    }

    /**
     * Returns true if it's a remote destination
     *
     * @return boolean
     */
    public function isRemote()
    {
        return $this->_isRemote;
    }

    /**
     * Get resource
     *
     * @return \LaminasPdf\InternalType\AbstractTypeObject
     * @internal
     */
    public function getResource()
    {
        return $this->_destinationArray;
    }
}
