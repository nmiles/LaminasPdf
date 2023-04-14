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
 * \LaminasPdf\InternalType\NameObject
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
class NameTest extends \PHPUnit\Framework\TestCase
{
    public function testPDFName()
    {
        $nameObj = new InternalType\NameObject('MyName');
        $this->assertTrue($nameObj instanceof InternalType\NameObject);
    }

    public function testPDFNameBadString()
    {
        $this->expectException('\\' . \LaminasPdf\Exception\RuntimeException::class);
        $this->expectExceptionMessage('Null character is not allowed');
        $nameObj = new InternalType\NameObject("MyName\x00");
    }

    public function testGetType()
    {
        $nameObj = new InternalType\NameObject('MyName');
        $this->assertEquals($nameObj->getType(), InternalType\AbstractTypeObject::TYPE_NAME);
    }

    public function testToString()
    {
        $nameObj = new InternalType\NameObject('MyName');
        $this->assertEquals($nameObj->toString(), '/MyName');
    }

    public function testEscape()
    {
        $this->assertEquals(InternalType\NameObject::escape('My Cool Name()'), 'My#20Cool#20Name#28#29');
    }

    public function testUnescape()
    {
        $this->assertEquals(InternalType\NameObject::unescape('My#20Cool#20Name#28#29'), 'My Cool Name()');
    }
}
