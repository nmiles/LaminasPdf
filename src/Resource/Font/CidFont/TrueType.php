<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   LaminasPdf
 */

namespace LaminasPdf\Resource\Font\CidFont;

use LaminasPdf as Pdf;
use LaminasPdf\BinaryParser\Font\OpenType as OpenTypeFontParser;
use LaminasPdf\InternalType;
use LaminasPdf\Resource\Font as FontResource;

/**
 * Type 2 CIDFonts implementation
 *
 * For Type 2, the CIDFont program is actually a TrueType font program, which has
 * no native notion of CIDs. In a TrueType font program, glyph descriptions are
 * identified by glyph index values. Glyph indices are internal to the font and are not
 * defined consistently from one font to another. Instead, a TrueType font program
 * contains a 'cmap' table that provides mappings directly from character codes to
 * glyph indices for one or more predefined encodings.
 *
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Fonts
 */
class TrueType extends AbstractCidFont
{
    /**
     * Object constructor
     *
     * @param \LaminasPdf\BinaryParser\Font\OpenType\TrueType $fontParser Font parser
     *   object containing parsed TrueType file.
     * @param integer $embeddingOptions Options for font embedding.
     * @throws \LaminasPdf\Exception\ExceptionInterface
     * @todo Joing this class with \LaminasPdf\Resource\Font\Simple\Parsed\TrueType
     *
     */
    public function __construct(OpenTypeFontParser\TrueType $fontParser, $embeddingOptions)
    {
        parent::__construct($fontParser, $embeddingOptions);

        $this->_fontType = Pdf\Font::TYPE_CIDFONT_TYPE_2;

        $this->_resource->Subtype = new InternalType\NameObject('CIDFontType2');

        $fontDescriptor = FontResource\FontDescriptor::factory($this, $fontParser, $embeddingOptions);
        $this->_resource->FontDescriptor = $this->_objectFactory->newObject($fontDescriptor);

        /* Prepare CIDToGIDMap */
        // Initialize 128K string of null characters (65536 2 byte integers)
        $cidToGidMapData = str_repeat("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00", 8192);
        // Fill the index
        $charGlyphs = $this->_cmap->getCoveredCharactersGlyphs();
        foreach ($charGlyphs as $charCode => $glyph) {
            $cidToGidMapData[$charCode * 2] = chr($glyph >> 8);
            $cidToGidMapData[$charCode * 2 + 1] = chr($glyph & 0xFF);
        }
        // Store CIDToGIDMap within compressed stream object
        $cidToGidMap = $this->_objectFactory->newStreamObject($cidToGidMapData);
        $cidToGidMap->dictionary->Filter = new InternalType\NameObject('FlateDecode');
        $this->_resource->CIDToGIDMap = $cidToGidMap;
    }
}
