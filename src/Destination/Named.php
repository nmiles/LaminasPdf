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
 * Destination array: [page /Fit]
 *
 * Display the page designated by page, with its contents magnified just enough
 * to fit the entire page within the window both horizontally and vertically. If
 * the required horizontal and vertical magnification factors are different, use
 * the smaller of the two, centering the page within the window in the other
 * dimension.
 *
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Destination
 */
class Named extends AbstractDestination
{
    /**
     * Destination name
     *
     * @var \LaminasPdf\InternalType\NameObject|\LaminasPdf\InternalType\StringObject
     */
    protected $_nameElement;

    /**
     * Named destination object constructor
     *
     * @param $resource
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function __construct(InternalType\AbstractTypeObject $resource)
    {
        if ($resource->getType() != InternalType\AbstractTypeObject::TYPE_NAME && $resource->getType() != InternalType\AbstractTypeObject::TYPE_STRING) {
            throw new Exception\CorruptedPdfException('Named destination resource must be a PDF name or a PDF string.');
        }

        $this->_nameElement = $resource;
    }

    /**
     * Create named destination object
     *
     * @param string $name
     * @return \LaminasPdf\Destination\Named
     */
    public static function create($name)
    {
        return new self(new InternalType\StringObject($name));
    }

    /**
     * Get name
     *
     * @return \LaminasPdf\InternalType\AbstractTypeObject
     */
    public function getName()
    {
        return $this->_nameElement->value;
    }

    /**
     * Get resource
     *
     * @return \LaminasPdf\InternalType\AbstractTypeObject
     * @internal
     */
    public function getResource()
    {
        return $this->_nameElement;
    }
}
