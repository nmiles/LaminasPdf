<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   LaminasPdf
 */

namespace LaminasPdf\BinaryParser\Image;

use LaminasPdf\BinaryParser;

/**
 * \LaminasPdf\Image related file parsers abstract class.
 *
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Image
 */
abstract class AbstractImage extends BinaryParser\AbstractBinaryParser
{
    /**
     * Image Type
     *
     * @var integer
     */
    protected $imageType;

    /**
     * Object constructor.
     *
     * Validates the data source and enables debug logging if so configured.
     *
     * @param \LaminasPdf\BinaryParser\DataSource\AbstractDataSource $dataSource
     */
    public function __construct(\LaminasPdf\BinaryParser\DataSource\AbstractDataSource $dataSource)
    {
        parent::__construct($dataSource);
        $this->imageType = \LaminasPdf\Image::TYPE_UNKNOWN;
    }
}
