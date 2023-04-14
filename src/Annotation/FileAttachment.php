<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   LaminasPdf
 */

namespace LaminasPdf\Annotation;

use LaminasPdf\Exception;
use LaminasPdf\InternalType;

/**
 * A file attachment annotation contains a reference to a file,
 * which typically is embedded in the PDF file.
 *
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Annotation
 */
class FileAttachment extends AbstractAnnotation
{
    /**
     * Annotation object constructor
     *
     * @throws \LaminasPdf\Exception\ExceptionInterface
     */
    public function __construct(InternalType\AbstractTypeObject $annotationDictionary)
    {
        if ($annotationDictionary->getType() != InternalType\AbstractTypeObject::TYPE_DICTIONARY) {
            throw new Exception\CorruptedPdfException('Annotation dictionary resource has to be a dictionary.');
        }

        if (
            $annotationDictionary->Subtype === null ||
            $annotationDictionary->Subtype->getType() != InternalType\AbstractTypeObject::TYPE_NAME ||
            $annotationDictionary->Subtype->value != 'FileAttachment'
        ) {
            throw new Exception\CorruptedPdfException('Subtype => FileAttachment entry is requires');
        }

        parent::__construct($annotationDictionary);
    }

    /**
     * Create link annotation object
     *
     * @param float $x1
     * @param float $y1
     * @param float $x2
     * @param float $y2
     * @param string $fileSpecification
     * @return \LaminasPdf\Annotation\FileAttachment
     */
    public static function create($x1, $y1, $x2, $y2, $fileSpecification)
    {
        $annotationDictionary = new InternalType\DictionaryObject();

        $annotationDictionary->Type = new InternalType\NameObject('Annot');
        $annotationDictionary->Subtype = new InternalType\NameObject('FileAttachment');

        $rectangle = new InternalType\ArrayObject();
        $rectangle->items[] = new InternalType\NumericObject($x1);
        $rectangle->items[] = new InternalType\NumericObject($y1);
        $rectangle->items[] = new InternalType\NumericObject($x2);
        $rectangle->items[] = new InternalType\NumericObject($y2);
        $annotationDictionary->Rect = $rectangle;

        $fsDictionary = new InternalType\DictionaryObject();
        $fsDictionary->Type = new InternalType\NameObject('Filespec');
        $fsDictionary->F = new InternalType\StringObject($fileSpecification);

        $annotationDictionary->FS = $fsDictionary;


        return new self($annotationDictionary);
    }
}
