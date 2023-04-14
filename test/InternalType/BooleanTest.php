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
 * \LaminasPdf\InternalType\BooleanObject
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
class BooleanTest extends \PHPUnit\Framework\TestCase
{
    public function testPDFBoolean()
    {
        $boolObj = new InternalType\BooleanObject(false);
        $this->assertTrue($boolObj instanceof InternalType\BooleanObject);
    }

    public function testPDFBooleanBadArgument()
    {
        $this->expectException('\\' . \LaminasPdf\Exception\RuntimeException::class);
        $this->expectExceptionMessage('must be boolean');
        $boolObj = new InternalType\BooleanObject('some input');
    }

    public function testGetType()
    {
        $boolObj = new InternalType\BooleanObject((boolean)100);
        $this->assertEquals($boolObj->getType(), InternalType\AbstractTypeObject::TYPE_BOOL);
    }

    public function testToString()
    {
        $boolObj = new InternalType\BooleanObject(true);
        $this->assertEquals($boolObj->toString(), 'true');
    }
}
