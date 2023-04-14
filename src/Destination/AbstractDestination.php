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

use LaminasPdf as Pdf;
use LaminasPdf\Exception;
use LaminasPdf\InternalType;

/**
 * Abstract PDF destination representation class
 *
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Destination
 */
abstract class AbstractDestination extends Pdf\InternalStructure\NavigationTarget
{
    /**
     * Load Destination object from a specified resource
     *
     * @param $destinationArray
     * @return \LaminasPdf\Destination\AbstractDestination
     * @internal
     */
    public static function load(InternalType\AbstractTypeObject $resource)
    {
        if ($resource->getType() == InternalType\AbstractTypeObject::TYPE_NAME || $resource->getType() == InternalType\AbstractTypeObject::TYPE_STRING) {
            return new Named($resource);
        }

        if ($resource->getType() != InternalType\AbstractTypeObject::TYPE_ARRAY) {
            throw new Exception\CorruptedPdfException('An explicit destination must be a direct or an indirect array object.');
        }
        if ((is_countable($resource->items) ? count($resource->items) : 0) < 2) {
            throw new Exception\CorruptedPdfException('An explicit destination array must contain at least two elements.');
        }

        switch ($resource->items[1]->value) {
            case 'XYZ':
                return new Zoom($resource);
                break;

            case 'Fit':
                return new Fit($resource);
                break;

            case 'FitH':
                return new FitHorizontally($resource);
                break;

            case 'FitV':
                return new FitVertically($resource);
                break;

            case 'FitR':
                return new FitRectangle($resource);
                break;

            case 'FitB':
                return new FitBoundingBox($resource);
                break;

            case 'FitBH':
                return new FitBoundingBoxHorizontally($resource);
                break;

            case 'FitBV':
                return new FitBoundingBoxVertically($resource);
                break;

            default:
                return new Unknown($resource);
                break;
        }
    }
}
