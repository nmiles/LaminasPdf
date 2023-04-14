<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   LaminasPdf
 */

namespace LaminasPdfTest\InternalType;

use LaminasPdf\InternalType;

/**
 * \LaminasPdf\InternalType\ArrayObject
 */

/**
 * PHPUnit Test Case
 */

/**
 * @category   Zend
 * @package    LaminasPdf
 * @subpackage UnitTests
 * @group      LaminasPdf
 */
class ArrayTest extends \PHPUnit\Framework\TestCase
{
    public function testPDFArray1()
    {
        $arrayObj = new InternalType\ArrayObject();
        $this->assertTrue($arrayObj instanceof InternalType\ArrayObject);
    }

    public function testPDFArray2()
    {
        $srcArray = [];
        $srcArray[] = new InternalType\BooleanObject(false);
        $srcArray[] = new InternalType\NumericObject(100.426);
        $srcArray[] = new InternalType\NameObject('MyName');
        $srcArray[] = new InternalType\StringObject('some text');
        $srcArray[] = new InternalType\BinaryStringObject('some text');

        $arrayObj = new InternalType\ArrayObject($srcArray);
        $this->assertTrue($arrayObj instanceof InternalType\ArrayObject);
    }

    public function testPDFArrayBadInput1()
    {
        $this->expectException('\\' . \LaminasPdf\Exception\RuntimeException::class);
        $this->expectExceptionMessage('Argument must be an array');
        $arrayObj = new InternalType\ArrayObject(346);
    }

    public function testPDFArrayBadInput2()
    {
        $this->expectException('\\' . \LaminasPdf\Exception\RuntimeException::class);
        $this->expectExceptionMessage('Array elements must be \LaminasPdf\InternalType\AbstractTypeObject objects');

        $srcArray = [];
        $srcArray[] = new InternalType\BooleanObject(false);
        $srcArray[] = new InternalType\NumericObject(100.426);
        $srcArray[] = new InternalType\NameObject('MyName');
        $srcArray[] = new InternalType\StringObject('some text');
        $srcArray[] = new InternalType\BinaryStringObject('some text');
        $srcArray[] = 24;
        $arrayObj = new InternalType\ArrayObject($srcArray);
    }

    public function testGetType()
    {
        $arrayObj = new InternalType\ArrayObject();
        $this->assertEquals($arrayObj->getType(), InternalType\AbstractTypeObject::TYPE_ARRAY);
    }

    public function testToString()
    {
        $srcArray = [];
        $srcArray[] = new InternalType\BooleanObject(false);
        $srcArray[] = new InternalType\NumericObject(100.426);
        $srcArray[] = new InternalType\NameObject('MyName');
        $srcArray[] = new InternalType\StringObject('some text');
        $arrayObj = new InternalType\ArrayObject($srcArray);
        $this->assertEquals($arrayObj->toString(), '[false 100.426 /MyName (some text) ]');
    }

    /**
     * @todo \LaminasPdf\InternalType\ArrayObject::add() does not exist
     */
    /*
    public function testAdd()
    {
        $arrayObj = new \LaminasPdf\InternalType\ArrayObject($srcArray);
        $arrayObj->add(new \LaminasPdf\InternalType\BooleanObject(false));
        $arrayObj->add(new \LaminasPdf\InternalType\NumericObject(100.426));
        $arrayObj->add(new \LaminasPdf\InternalType\NameObject('MyName'));
        $arrayObj->add(new \LaminasPdf\InternalType\StringObject('some text'));
        $this->assertEquals($arrayObj->toString(), '[false 100.426 /MyName (some text) ]' );
    }
    //*/

    /**
     * @todo \LaminasPdf\InternalType\ArrayObject::add() does not exist
     */
    /*
    public function testAddBadArgument()
    {
        $this->setExpectedException(
            '\LaminasPdf\Exception\RuntimeException',
            'Array elements must be \LaminasPdf\InternalType\AbstractTypeObject objects'
        );

        $arrayObj = new ZPDFPDFArray();
        $arrayObj->add(100.426);
    }
    //*/
}
