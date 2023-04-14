<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   LaminasPdf
 */

namespace LaminasPdf\Resource\Font\Simple\Parsed;

use LaminasPdf as Pdf;
use LaminasPdf\BinaryParser\Font\OpenType as OpenTypeFontParser;
use LaminasPdf\InternalType;
use LaminasPdf\Resource\Font as FontResource;

/**
 * TrueType fonts implementation
 *
 * Font objects should be normally be obtained from the factory methods
 * {@link \LaminasPdf\Font::fontWithName} and {@link \LaminasPdf\Font::fontWithPath}.
 *
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Fonts
 */
class TrueType extends AbstractParsed
{
    /**
     * Object constructor
     *
     * @param \LaminasPdf\BinaryParser\Font\OpenType\TrueType $fontParser Font parser
     *   object containing parsed TrueType file.
     * @param integer $embeddingOptions Options for font embedding.
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function __construct(OpenTypeFontParser\TrueType $fontParser, $embeddingOptions)
    {
        parent::__construct($fontParser, $embeddingOptions);

        $this->_fontType = Pdf\Font::TYPE_TRUETYPE;

        $this->_resource->Subtype = new InternalType\NameObject('TrueType');

        $fontDescriptor = FontResource\FontDescriptor::factory($this, $fontParser, $embeddingOptions);
        $this->_resource->FontDescriptor = $this->_objectFactory->newObject($fontDescriptor);
    }
}
