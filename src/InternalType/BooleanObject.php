<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   LaminasPdf
 */

namespace LaminasPdf\InternalType;

use LaminasPdf as Pdf;
use LaminasPdf\Exception;

/**
 * PDF file 'boolean' element implementation
 *
 * @category   Zend
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Internal
 */
class BooleanObject extends AbstractTypeObject
{
    /**
     * Object value
     *
     * @var boolean
     */
    public $value;


    /**
     * Object constructor
     *
     * @param boolean $val
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function __construct($val)
    {
        if (!is_bool($val)) {
            throw new Exception\RuntimeException('Argument must be boolean.');
        }

        $this->value = $val;
    }


    /**
     * Return type of the element.
     *
     * @return integer
     */
    public function getType()
    {
        return AbstractTypeObject::TYPE_BOOL;
    }


    /**
     * Return object as string
     *
     * @param \LaminasPdf\ObjectFactory $factory
     * @return string
     */
    public function toString(Pdf\ObjectFactory $factory = null)
    {
        return $this->value ? 'true' : 'false';
    }
}
