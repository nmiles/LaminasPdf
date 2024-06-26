<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   LaminasPdf
 */

namespace LaminasPdf;

use LaminasPdf\Exception;

/**
 * Abstract factory class which vends {@link \LaminasPdf\Resource\Image\AbstractImage} objects.
 *
 * This class is also the home for image-related constants because the name of
 * the true base class ({@link \LaminasPdf\Resource\Image\AbstractImage}) is not intuitive
 * for the end user.
 *
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Image
 */
abstract class Image
{
    /**** Class Constants ****/


    /* Image Types */

    public const TYPE_UNKNOWN = 0;
    public const TYPE_JPEG = 1;
    public const TYPE_PNG = 2;
    public const TYPE_TIFF = 3;

    /* TIFF Constants */

    public const TIFF_FIELD_TYPE_BYTE = 1;
    public const TIFF_FIELD_TYPE_ASCII = 2;
    public const TIFF_FIELD_TYPE_SHORT = 3;
    public const TIFF_FIELD_TYPE_LONG = 4;
    public const TIFF_FIELD_TYPE_RATIONAL = 5;

    public const TIFF_TAG_IMAGE_WIDTH = 256;
    public const TIFF_TAG_IMAGE_LENGTH = 257; //Height
    public const TIFF_TAG_BITS_PER_SAMPLE = 258;
    public const TIFF_TAG_COMPRESSION = 259;
    public const TIFF_TAG_PHOTOMETRIC_INTERPRETATION = 262;
    public const TIFF_TAG_STRIP_OFFSETS = 273;
    public const TIFF_TAG_SAMPLES_PER_PIXEL = 277;
    public const TIFF_TAG_STRIP_BYTE_COUNTS = 279;

    public const TIFF_COMPRESSION_UNCOMPRESSED = 1;
    public const TIFF_COMPRESSION_CCITT1D = 2;
    public const TIFF_COMPRESSION_GROUP_3_FAX = 3;
    public const TIFF_COMPRESSION_GROUP_4_FAX = 4;
    public const TIFF_COMPRESSION_LZW = 5;
    public const TIFF_COMPRESSION_JPEG = 6;
    public const TIFF_COMPRESSION_FLATE = 8;
    public const TIFF_COMPRESSION_FLATE_OBSOLETE_CODE = 32946;
    public const TIFF_COMPRESSION_PACKBITS = 32773;

    public const TIFF_PHOTOMETRIC_INTERPRETATION_WHITE_IS_ZERO = 0;
    public const TIFF_PHOTOMETRIC_INTERPRETATION_BLACK_IS_ZERO = 1;
    public const TIFF_PHOTOMETRIC_INTERPRETATION_RGB = 2;
    public const TIFF_PHOTOMETRIC_INTERPRETATION_RGB_INDEXED = 3;
    public const TIFF_PHOTOMETRIC_INTERPRETATION_CMYK = 5;
    public const TIFF_PHOTOMETRIC_INTERPRETATION_YCBCR = 6;
    public const TIFF_PHOTOMETRIC_INTERPRETATION_CIELAB = 8;

    /* PNG Constants */

    public const PNG_COMPRESSION_DEFAULT_STRATEGY = 0;
    public const PNG_COMPRESSION_FILTERED = 1;
    public const PNG_COMPRESSION_HUFFMAN_ONLY = 2;
    public const PNG_COMPRESSION_RLE = 3;

    public const PNG_FILTER_NONE = 0;
    public const PNG_FILTER_SUB = 1;
    public const PNG_FILTER_UP = 2;
    public const PNG_FILTER_AVERAGE = 3;
    public const PNG_FILTER_PAETH = 4;

    public const PNG_INTERLACING_DISABLED = 0;
    public const PNG_INTERLACING_ENABLED = 1;

    public const PNG_CHANNEL_GRAY = 0;
    public const PNG_CHANNEL_RGB = 2;
    public const PNG_CHANNEL_INDEXED = 3;
    public const PNG_CHANNEL_GRAY_ALPHA = 4;
    public const PNG_CHANNEL_RGB_ALPHA = 6;

    /**** Public Interface ****/


    /* Factory Methods */

    /**
     * Returns a {@link \LaminasPdf\Resource\Image\AbstractImage} object by file path.
     *
     * @param string $filePath Full path to the image file.
     * @return \LaminasPdf\Resource\Image\AbstractImage
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public static function imageWithPath($filePath)
    {
        $dataSource = null;
        /**
         * use old implementation
         * @todo switch to new implementation
         */
        return Resource\Image\ImageFactory::factory($filePath);


        /* Create a file parser data source object for this file. File path and
         * access permission checks are handled here.
         */
        $dataSource = new BinaryParser\DataSource\File($filePath);

        /* Attempt to determine the type of image. We can't always trust file
         * extensions, but try that first since it's fastest.
         */
        $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        /* If it turns out that the file is named improperly and we guess the
         * wrong type, we'll get null instead of an image object.
         */
        switch ($fileExtension) {
            case 'tif':
                //Fall through to next case;
            case 'tiff':
                $image = self::_extractTiffImage($dataSource);
                break;
            case 'png':
                $image = self::_extractPngImage($dataSource);
                break;
            case 'jpg':
                //Fall through to next case;
            case 'jpe':
                //Fall through to next case;
            case 'jpeg':
                $image = self::_extractJpegImage($dataSource);
                break;
            default:
                throw new Exception\DomainException('Cannot create image resource. File extension not known or unsupported type.');
                break;
        }

        /* Done with the data source object.
         */
        $dataSource = null;

        if ($image !== null) {
            return $image;
        } else {
            /* The type of image could not be determined. Give up.
             */
            throw new Exception\DomainException("Cannot determine image type: $filePath");
        }
    }



    /**** Internal Methods ****/


    /* Image Extraction Methods */

    /**
     * Attempts to extract a JPEG Image from the data source.
     *
     * @param \LaminasPdf\BinaryParser\DataSource\AbstractDataSource $dataSource
     * @return \LaminasPdf\Resource\Image\Jpeg May also return null if
     *   the data source does not appear to contain valid image data.
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    protected static function _extractJpegImage($dataSource)
    {
        throw new Exception\NotImplementedException('Jpeg image fileparser is not implemented. Old styly implementation has to be used.');

        $imageParser = new BinaryParser\Image\Jpeg($dataSource);
        $image = new Resource\Image\Jpeg($imageParser);
        unset($imageParser);

        return $image;
    }

    /**
     * Attempts to extract a PNG Image from the data source.
     *
     * @param \LaminasPdf\BinaryParser\DataSource\AbstractDataSource $dataSource
     * @return \LaminasPdf\Resource\Image\Png May also return null if
     *   the data source does not appear to contain valid image data.
     */
    protected static function _extractPngImage($dataSource)
    {
        $imageParser = new BinaryParser\Image\Png($dataSource);
        $image = new Resource\Image\Png($imageParser);
        unset($imageParser);

        return $image;
    }

    /**
     * Attempts to extract a TIFF Image from the data source.
     *
     * @param \LaminasPdf\BinaryParser\DataSource\AbstractDataSource $dataSource
     * @return \LaminasPdf\Resource\Image\Tiff May also return null if
     *   the data source does not appear to contain valid image data.
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    protected static function _extractTiffImage($dataSource)
    {
        throw new Exception\NotImplementedException('Tiff image fileparser is not implemented. Old styly implementation has to be used.');

        $imageParser = new BinaryParser\Image\Tiff($dataSource);
        $image = new Resource\Image\Tiff($imageParser);
        unset($imageParser);

        return $image;
    }
}
