<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   LaminasPdf
 */

namespace LaminasPdfTest\Color;

use LaminasPdf\Color\Html;

/** \LaminasPdf\Color\Html */

/** PHPUnit Test Case */

/**
 * @category   Zend
 * @package    LaminasPdf
 * @subpackage UnitTests
 * @group      LaminasPdf
 */
class HtmlTest extends \PHPUnit\Framework\TestCase
{
    public function testShadesReturnsThreeRgbTriples()
    {
        $shades = Html::shades('blue');

        $this->assertCount(3, $shades);
        foreach ($shades as $rgb) {
            $this->assertCount(3, $rgb);
        }
    }

    public function testShadesFromNamedColor()
    {
        [$lighter, $base, $darker] = Html::shades('salmon');

        $this->assertIsArray($lighter);
        $this->assertIsArray($base);
        $this->assertIsArray($darker);
    }

    public function testShadesFromHex()
    {
        [$lighter, $base, $darker] = Html::shades('#ff6347');

        $this->assertCount(3, $lighter);
        $this->assertCount(3, $base);
        $this->assertCount(3, $darker);
    }

    public function testShadesFromRgbArray()
    {
        [$lighter, $base, $darker] = Html::shades([1, 0.39, 0.28]);

        $this->assertEqualsWithDelta(1.0, $base[0], 0.0001);
        $this->assertEqualsWithDelta(0.39, $base[1], 0.0001);
        $this->assertEqualsWithDelta(0.28, $base[2], 0.0001);
    }

    public function testShadesBaseColorIsUnchanged()
    {
        // The middle stop should be the exact input RGB, not a value that's
        // been round-tripped through HSL and back (which could introduce
        // tiny floating-point drift).
        $input = [0.5, 0.25, 0.75];
        [, $base, ] = Html::shades($input);

        $this->assertEquals($input, $base);
    }

    public function testShadesLighterIsActuallyLighter()
    {
        [$lighter, $base, $darker] = Html::shades('salmon');

        $lighterLightness = $this->_lightnessOf($lighter);
        $baseLightness = $this->_lightnessOf($base);
        $darkerLightness = $this->_lightnessOf($darker);

        $this->assertGreaterThan($baseLightness, $lighterLightness);
        $this->assertLessThan($baseLightness, $darkerLightness);
    }

    public function testShadesPreservesHueAndSaturation()
    {
        [$lighter, $base, $darker] = Html::shades('salmon');

        [$baseHue, $baseSat] = $this->_hueSaturationOf($base);
        [$lighterHue, $lighterSat] = $this->_hueSaturationOf($lighter);
        [$darkerHue, $darkerSat] = $this->_hueSaturationOf($darker);

        $this->assertEqualsWithDelta($baseHue, $lighterHue, 0.001);
        $this->assertEqualsWithDelta($baseHue, $darkerHue, 0.001);
        $this->assertEqualsWithDelta($baseSat, $lighterSat, 0.001);
        $this->assertEqualsWithDelta($baseSat, $darkerSat, 0.001);
    }

    public function testShadesClampsAtWhite()
    {
        // Lightening white further should stay at white, not overshoot past 1.0
        [$lighter, , ] = Html::shades('white');

        foreach ($lighter as $component) {
            $this->assertLessThanOrEqual(1.0, $component);
            $this->assertEqualsWithDelta(1.0, $component, 0.0001);
        }
    }

    public function testShadesClampsAtBlack()
    {
        // Darkening black further should stay at black, not go below 0.0
        [, , $darker] = Html::shades('black');

        foreach ($darker as $component) {
            $this->assertGreaterThanOrEqual(0.0, $component);
            $this->assertEqualsWithDelta(0.0, $component, 0.0001);
        }
    }

    public function testShadesCustomAmounts()
    {
        [$lighterSmall, $baseSmall, ] = Html::shades('salmon', 0.05, 0.05);
        [$lighterLarge, $baseLarge, ] = Html::shades('salmon', 0.30, 0.30);

        $smallDelta = $this->_lightnessOf($lighterSmall) - $this->_lightnessOf($baseSmall);
        $largeDelta = $this->_lightnessOf($lighterLarge) - $this->_lightnessOf($baseLarge);

        $this->assertGreaterThan($smallDelta, $largeDelta);
    }

    public function testShadesOnGrayscaleNamedColor()
    {
        // "gray" resolves to GrayScale (1 component) via Html::color() - must
        // expand to a proper RGB triple before HSL math, not be passed through raw.
        [$lighter, $base, $darker] = Html::shades('gray');

        $this->assertCount(3, $base);
        $this->assertEquals($base[0], $base[1]);
        $this->assertEquals($base[1], $base[2]);
    }

    /**
     * Lightness (L) via the same RGB->HSL formula Html::shades() itself uses -
     * duplicated here deliberately, so the test verifies against the algorithm's
     * definition rather than against Html.php's own implementation.
     */
    private function _lightnessOf(array $rgb)
    {
        [$r, $g, $b] = $rgb;
        return (max($r, $g, $b) + min($r, $g, $b)) / 2;
    }

    /**
     * @return array [hue, saturation]
     */
    private function _hueSaturationOf(array $rgb)
    {
        [$r, $g, $b] = $rgb;
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max == $min) {
            return [0.0, 0.0];
        }

        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

        if ($max == $r) {
            $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
        } elseif ($max == $g) {
            $h = ($b - $r) / $d + 2;
        } else {
            $h = ($r - $g) / $d + 4;
        }
        $h /= 6;

        return [$h, $s];
    }
}