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
 * \LaminasPdf\InternalType\DictionaryObject
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
class DictionaryTest extends \PHPUnit\Framework\TestCase
{
    public function testPDFDictionary1()
    {
        $dictionaryObj = new InternalType\DictionaryObject();
        $this->assertTrue($dictionaryObj instanceof InternalType\DictionaryObject);
    }

    public function testPDFDictionary2()
    {
        $srcArray = [];
        $srcArray['Bool'] = new InternalType\BooleanObject(false);
        $srcArray['Number'] = new InternalType\NumericObject(100.426);
        $srcArray['Name'] = new InternalType\NameObject('MyName');
        $srcArray['Text'] = new InternalType\StringObject('some text');
        $srcArray['BinaryText'] = new InternalType\BinaryStringObject('some text');

        $dictionaryObj = new InternalType\DictionaryObject($srcArray);
        $this->assertTrue($dictionaryObj instanceof InternalType\DictionaryObject);
    }

    public function testPDFDictionaryBadInput1()
    {
        $this->expectException('\\' . \LaminasPdf\Exception\RuntimeException::class);
        $this->expectExceptionMessage('must be an array');
        $dictionaryObj = new InternalType\DictionaryObject(346);
    }

    public function testPDFDictionaryBadInput2()
    {
        $this->expectException('\\' . \LaminasPdf\Exception\RuntimeException::class);
        $this->expectExceptionMessage('must be \LaminasPdf\InternalType\AbstractTypeObject');

        $srcArray = [];
        $srcArray['Bool'] = new InternalType\BooleanObject(false);
        $srcArray['Number'] = new InternalType\NumericObject(100.426);
        $srcArray['Name'] = new InternalType\NameObject('MyName');
        $srcArray['Text'] = new InternalType\StringObject('some text');
        $srcArray['BinaryText'] = new InternalType\BinaryStringObject('some text');
        $srcArray['bad value'] = 24;
        $dictionaryObj = new InternalType\DictionaryObject($srcArray);
    }

    public function testPDFDictionaryBadInput3()
    {
        $this->expectException('\\' . \LaminasPdf\Exception\RuntimeException::class);
        $this->expectExceptionMessage('keys must be strings');
        $srcArray = [];
        $srcArray['Bool'] = new InternalType\BooleanObject(false);
        $srcArray['Number'] = new InternalType\NumericObject(100.426);
        $srcArray['Name'] = new InternalType\NameObject('MyName');
        $srcArray['Text'] = new InternalType\StringObject('some text');
        $srcArray['BinaryText'] = new InternalType\BinaryStringObject('some text');
        $srcArray[5] = new InternalType\StringObject('bad name');
        $dictionaryObj = new InternalType\DictionaryObject($srcArray);
    }

    public function testGetType()
    {
        $dictionaryObj = new InternalType\DictionaryObject();
        $this->assertEquals($dictionaryObj->getType(), InternalType\AbstractTypeObject::TYPE_DICTIONARY);
    }

    public function testToString()
    {
        $srcArray = [];
        $srcArray['Bool'] = new InternalType\BooleanObject(false);
        $srcArray['Number'] = new InternalType\NumericObject(100.426);
        $srcArray['Name'] = new InternalType\NameObject('MyName');
        $srcArray['Text'] = new InternalType\StringObject('some text');
        $srcArray['BinaryText'] = new InternalType\BinaryStringObject("\x01\x02\x00\xff");
        $dictionaryObj = new InternalType\DictionaryObject($srcArray);
        $this->assertEquals($dictionaryObj->toString(),
            '<</Bool false /Number 100.426 /Name /MyName /Text (some text) /BinaryText <010200FF> >>');
    }

    public function testAdd()
    {
        $dictionaryObj = new InternalType\DictionaryObject();
        $dictionaryObj->add(new InternalType\NameObject('Var1'), new InternalType\BooleanObject(false));
        $dictionaryObj->add(new InternalType\NameObject('Var2'), new InternalType\NumericObject(100.426));
        $dictionaryObj->add(new InternalType\NameObject('Var3'), new InternalType\NameObject('MyName'));
        $dictionaryObj->add(new InternalType\NameObject('Var4'), new InternalType\StringObject('some text'));
        $this->assertEquals($dictionaryObj->toString(),
            '<</Var1 false /Var2 100.426 /Var3 /MyName /Var4 (some text) >>');
    }

}
