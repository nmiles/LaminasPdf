<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   LaminasPdf
 */

namespace LaminasPdfTest;

use LaminasPdf as Pdf;

/** \LaminasPdf\Page */

/** PHPUnit Test Case */

/**
 * @category   Zend
 * @package    LaminasPdf
 * @subpackage UnitTests
 * @group      LaminasPdf
 */
class PdfDocumentTest extends \PHPUnit\Framework\TestCase
{
    public function testRenderInvalidEncoding()
    {
        $pdf = new Pdf\PdfDocument();

        $pdf->properties['Creator'] = "\xf8make_mb_detect_encoding_fail";

        $resource = tmpfile();

        $string = $pdf->render(false, $resource);
        $this->assertIsString(
            $string,
            'Render should return a valid string'
        );
    }
}
