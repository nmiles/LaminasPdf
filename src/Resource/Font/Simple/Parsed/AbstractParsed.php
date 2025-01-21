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

/**
 * Parsed and (optionaly) embedded fonts implementation
 *
 * OpenType fonts can contain either TrueType or PostScript Type 1 outlines.
 *
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Fonts
 */
abstract class AbstractParsed extends \LaminasPdf\Resource\Font\Simple\AbstractSimple
{
    /**
     * Object constructor
     *
     * @param \LaminasPdf\BinaryParser\Font\OpenType\AbstractOpenType $fontParser Font parser object containing OpenType file.
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function __construct(OpenTypeFontParser\AbstractOpenType $fontParser)
    {
        parent::__construct();


        $fontParser->parse();

        /* Object properties */

        $this->_fontNames = $fontParser->names;

        $this->_isBold = $fontParser->isBold;
        $this->_isItalic = $fontParser->isItalic;
        $this->_isMonospace = $fontParser->isMonospace;

        $this->_underlinePosition = $fontParser->underlinePosition;
        $this->_underlineThickness = $fontParser->underlineThickness;
        $this->_strikePosition = $fontParser->strikePosition;
        $this->_strikeThickness = $fontParser->strikeThickness;

        $this->_unitsPerEm = $fontParser->unitsPerEm;

        $this->_ascent = $fontParser->ascent;
        $this->_descent = $fontParser->descent;
        $this->_lineGap = $fontParser->lineGap;

        $this->_glyphWidths = $fontParser->glyphWidths;
        $this->_missingGlyphWidth = $this->_glyphWidths[0];


        $this->_cmap = $fontParser->cmap;


        /* Resource dictionary */

        $baseFont = $this->getFontName(Pdf\Font::NAME_POSTSCRIPT, 'en', 'UTF-8');
        $this->_resource->BaseFont = new InternalType\NameObject($baseFont);

        $this->_resource->FirstChar = new InternalType\NumericObject(0);
        $this->_resource->LastChar = new InternalType\NumericObject((is_countable($this->_glyphWidths) ? count($this->_glyphWidths) : 0) - 1);

        /* Now convert the scalar glyph widths to \LaminasPdf\InternalType\NumericObect objects.
         */
        $pdfWidths = [];
        foreach ($this->_glyphWidths as $width) {
            $pdfWidths[] = new InternalType\NumericObject($this->toEmSpace($width));
        }
        /* Create the \LaminasPdf\InternalType\ArrayObject object and add it to the font's
         * object factory and resource dictionary.
         */
        $widthsArrayElement = new InternalType\ArrayObject($pdfWidths);
        $widthsObject = $this->_objectFactory->newObject($widthsArrayElement);
        $this->_resource->Widths = $widthsObject;
    }
}
