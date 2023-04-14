<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   LaminasPdf
 */

namespace LaminasPdf\InternalType;

/**
 * PDF stream filter
 *
 * @package    LaminasPdf
 * @subpackage LaminasPdf\Internal
 */
interface StreamFilter
{
    /**
     * Encode data
     *
     * @param string $data
     * @param array $params
     * @return string
     * @throws \LaminasPdf\Exception
     */
    public static function encode($data, $params = null);

    /**
     * Decode data
     *
     * @param string $data
     * @param array $params
     * @return string
     * @throws \LaminasPdf\Exception
     */
    public static function decode($data, $params = null);
}
