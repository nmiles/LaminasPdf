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
use LaminasPdf\Color;
use LaminasPdf\InternalType;
use LaminasPdf\Resource\Shading;

/** \LaminasPdf */

/** \LaminasPdf\Page */

/** \LaminasPdf\Resource\Shading */


/** PHPUnit Test Case */

/**
 * @category   Zend
 * @package    LaminasPdf
 * @subpackage UnitTests
 * @group      LaminasPdf
 */
class ShadingTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Stores the original set timezone
     * @var string
     */
    private $_originaltimezone;

    public function setUp(): void
    {
        $this->_originaltimezone = date_default_timezone_get();
        date_default_timezone_set('GMT');
    }

    /**
     * Teardown environment
     */
    public function tearDown(): void
    {
        date_default_timezone_set($this->_originaltimezone);
    }

    public function testDrawRectangleWithShading()
    {
        $pdf = new Pdf\PdfDocument();

        $pdf->pages[] = ($page = $pdf->newPage('A4'));

        $shading1 = Shading::angled(60, 400, 500, 700, [[1, 0, 0], [0, 0, 1]], 0);
        $page->drawRectangleWithShading($shading1, 60, 400, 500, 700);

        // A second, independent shaded rect on the same page - exercises resource
        // dedup/naming (should get its own "S2" resource, not collide with the first)
        $shading2 = Shading::angled(60, 100, 500, 350, [[0, 1, 0], [1, 1, 0]], 0);
        $page->drawRectangleWithShading($shading2, 60, 100, 500, 350);

        $pdf->save(__DIR__ . '/_files/output.pdf');
        unset($pdf);

        $pdf1 = Pdf\PdfDocument::load(__DIR__ . '/_files/output.pdf');
        $this->assertTrue($pdf1 instanceof Pdf\PdfDocument);
        unset($pdf1);

        unlink(__DIR__ . '/_files/output.pdf');
    }

    public function testShadingResourceAttachment()
    {
        $pdf = new Pdf\PdfDocument();
        $pdf->pages[] = ($page = $pdf->newPage('A4'));

        $shading = Shading::angled(60, 400, 500, 700, [[1, 0, 0], [0, 0, 1]], 0);
        $page->drawRectangleWithShading($shading, 60, 400, 500, 700);

        $resources = $page->extractResources();
        $this->assertNotNull($resources->Shading);

        $shadingKeys = $resources->Shading->getKeys();
        $this->assertCount(1, $shadingKeys);
        $this->assertEquals('S1', $shadingKeys[0]);
    }

    public function testShadingDictionaryContents()
    {
        $shading = Shading::axial(60, 350, 60, 700, [[1, 0, 0], [0, 0, 1]]);

        $resource = $shading->getResource();
        $this->assertTrue($resource instanceof InternalType\IndirectObject);
        $this->assertEquals(2, $resource->ShadingType->value);
        $this->assertEquals('DeviceRGB', $resource->ColorSpace->value);
        $this->assertNotNull($resource->Function);

        $coords = $resource->Coords->items;
        $this->assertEquals(60, $coords[0]->value);
        $this->assertEquals(350, $coords[1]->value);
        $this->assertEquals(60, $coords[2]->value);
        $this->assertEquals(700, $coords[3]->value);
    }

    public function testAngledZeroDegreesIsVertical()
    {
        // 0deg should collapse to a purely vertical axis: same x for both axis endpoints
        $shading = Shading::angled(0, 0, 100, 50, [[1, 0, 0], [0, 0, 1]], 0);
        $coords = $shading->getResource()->Coords->items;

        $this->assertEqualsWithDelta($coords[0]->value, $coords[2]->value, 0.0001);
    }

    public function testAngledNinetyDegreesIsHorizontal()
    {
        // 90deg should collapse to a purely horizontal axis: same y for both endpoints
        $shading = Shading::angled(0, 0, 100, 50, [[1, 0, 0], [0, 0, 1]], 90);
        $coords = $shading->getResource()->Coords->items;

        $this->assertEqualsWithDelta($coords[1]->value, $coords[3]->value, 0.0001);
    }

    public function testTwoColorFunctionIsType2()
    {
        // Exactly 2 stops bypass the stitching machinery entirely - plain Type 2
        $shading = Shading::axial(0, 0, 0, 100, [[1, 0, 0], [0, 0, 1]]);

        $function = $shading->getResource()->Function;
        $this->assertEquals(2, $function->FunctionType->value);
    }

    public function testMultiStopUsesStitchingFunction()
    {
        // 3 DIFFERENT colors, all with equal (auto-filled) shares - the middle
        // stop's share never exceeds its neighbors combined, so this does NOT
        // trigger plateau auto-expansion; stays a plain 2-segment stitching function.
        $shading = Shading::axial(0, 0, 0, 100, [[0, 0, 0.4], [0.3, 0.5, 1], [0, 0, 0.2]]);

        $function = $shading->getResource()->Function;
        $this->assertEquals(3, $function->FunctionType->value);
        $this->assertCount(2, $function->Functions->items);
        $this->assertCount(1, $function->Bounds->items);

        foreach ($function->Functions->items as $subFunction) {
            $this->assertEquals(2, $subFunction->FunctionType->value);
        }
    }

    public function testEvenSpacingWithNoPercentages()
    {
        // 5 distinct colors, no percentages given -> even 20% shares each -> 4
        // equal 25%-wide segments -> 3 boundaries at 0.25/0.5/0.75. No interior
        // stop's share (20%) exceeds its neighbors combined (20+20=40), so
        // plateau expansion never triggers here.
        $shading = Shading::axial(
            0,
            0,
            0,
            100,
            [[1, 0, 0], [1, 0.5, 0], [1, 1, 0], [0, 1, 0], [0, 0, 1]]
        );

        $function = $shading->getResource()->Function;
        $this->assertCount(3, $function->Bounds->items);

        $this->assertEqualsWithDelta(0.25, $function->Bounds->items[0]->value, 0.0001);
        $this->assertEqualsWithDelta(0.5, $function->Bounds->items[1]->value, 0.0001);
        $this->assertEqualsWithDelta(0.75, $function->Bounds->items[2]->value, 0.0001);
    }

    public function testOverHundredPercentNormalizesInsteadOfThrowing()
    {
        // 50%+50%+50% = 150% total, 3 DIFFERENT colors - should normalize to
        // 33.3% each rather than throw. At exactly equal thirds the plateau
        // formula's numerator (1.5*33.33-50) lands at ~0, so this stays a plain
        // 2-segment gradient, symmetric shares -> centered 0.5 boundary.
        $shading = Shading::axial(0, 0, 0, 100, ['red 50%', 'yellow 50%', 'blue 50%']);

        $function = $shading->getResource()->Function;
        $this->assertCount(1, $function->Bounds->items);
        $this->assertEqualsWithDelta(0.5, $function->Bounds->items[0]->value, 0.001);
    }

    public function testPlateauAutoExpansionProducesFlatRegion()
    {
        // Middle stop's 80% share exceeds its neighbors combined (10+10=20),
        // so this SHOULD auto-expand into a real plateau: 4 segments (not 2),
        // 3 bounds at exactly 0.1 / 0.5 / 0.9 (algebraic solve, no rescale needed
        // since the resolved shares already sum to 100).
        $shading = Shading::axial(0, 0, 0, 100, ['darkblue 10%', 'lightblue 80%', 'darkblue 10%']);

        $function = $shading->getResource()->Function;
        $this->assertEquals(3, $function->FunctionType->value);
        $this->assertCount(4, $function->Functions->items);
        $this->assertCount(3, $function->Bounds->items);

        $this->assertEqualsWithDelta(0.1, $function->Bounds->items[0]->value, 0.0001);
        $this->assertEqualsWithDelta(0.5, $function->Bounds->items[1]->value, 0.0001);
        $this->assertEqualsWithDelta(0.9, $function->Bounds->items[2]->value, 0.0001);

        // The two middle sub-functions (indices 1 and 2) should be flat -
        // identical C0/C1, since they're both lightblue-to-lightblue.
        $middleFunction1 = $function->Functions->items[1];
        $c0 = $middleFunction1->C0->items;
        $c1 = $middleFunction1->C1->items;
        for ($i = 0; $i < 3; $i++) {
            $this->assertEqualsWithDelta($c0[$i]->value, $c1[$i]->value, 0.0001);
        }
    }

    public function testAutoFillPlusPlateauExpansion()
    {
        // Same shape as above but via auto-fill (two blanks) rather than
        // explicit outer percentages - should resolve to the identical result.
        $shading = Shading::axial(0, 0, 0, 100, ['darkblue', 'lightblue 80%', 'darkblue']);

        $function = $shading->getResource()->Function;
        $this->assertCount(3, $function->Bounds->items);
        $this->assertEqualsWithDelta(0.1, $function->Bounds->items[0]->value, 0.0001);
        $this->assertEqualsWithDelta(0.9, $function->Bounds->items[2]->value, 0.0001);
    }

    public function testPlateauEntryAndExitExponents()
    {
        // The entry segment (index 0, darkblue -> lightblue) and exit segment
        // (last index, lightblue -> darkblue) should carry the sharpening
        // exponents; the two flat interior segments are irrelevant to N but
        // should stay at the default linear value.
        $shading = Shading::axial(0, 0, 0, 100, ['darkblue 10%', 'lightblue 80%', 'darkblue 10%']);

        $function = $shading->getResource()->Function;
        $functions = $function->Functions->items;

        $this->assertEqualsWithDelta(1.8, $functions[0]->N->value, 0.0001);
        $this->assertEqualsWithDelta(1.0, $functions[1]->N->value, 0.0001);
        $this->assertEqualsWithDelta(1.0, $functions[2]->N->value, 0.0001);
        $this->assertEqualsWithDelta(0.5, $functions[3]->N->value, 0.0001);
    }

    public function testSmallShareDoesNotTriggerPlateauExpansion()
    {
        // Middle stop's share (20%) does NOT exceed its neighbors combined
        // (40+40=80), so no expansion - stays a plain 2-segment gradient.
        $shading = Shading::axial(0, 0, 0, 100, ['darkblue 40%', 'lightblue 20%', 'darkblue 40%']);

        $function = $shading->getResource()->Function;
        $this->assertCount(2, $function->Functions->items);
        $this->assertCount(1, $function->Bounds->items);
    }

    public function testSingleColorAutoShades()
    {
        // A single color auto-expands into lighter -> base -> darker via
        // Color\Html::shades() - equal (auto-filled) thirds, so this does NOT
        // trigger plateau expansion, just a plain 2-segment gradient.
        $shading = Shading::axial(0, 0, 0, 100, ['salmon']);

        $function = $shading->getResource()->Function;
        $this->assertEquals(3, $function->FunctionType->value);
        $this->assertCount(2, $function->Functions->items);

        $lighterC0 = [];
        foreach ($function->Functions->items[0]->C0->items as $n) {
            $lighterC0[] = $n->value;
        }

        $baseColor = [];
        foreach ($function->Functions->items[0]->C1->items as $n) {
            $baseColor[] = $n->value;
        }

        $darkerC1 = [];
        foreach ($function->Functions->items[1]->C1->items as $n) {
            $darkerC1[] = $n->value;
        }

        // Lighter should sum higher than base, darker should sum lower than base -
        // a reasonable proxy for "actually lighter"/"actually darker" without
        // needing an exact HSL round-trip assertion here.
        $this->assertGreaterThan(array_sum($baseColor), array_sum($lighterC0));
        $this->assertLessThan(array_sum($baseColor), array_sum($darkerC1));
    }

    public function testSingleColorAcceptsHexAndRgbArray()
    {
        $hexShading = Shading::axial(0, 0, 0, 100, ['#ff6347']);
        $this->assertEquals(3, $hexShading->getResource()->Function->FunctionType->value);

        $rgbShading = Shading::axial(0, 0, 0, 100, [[1, 0.39, 0.28]]);
        $this->assertEquals(3, $rgbShading->getResource()->Function->FunctionType->value);
    }

    public function testNamedColorResolvesCorrectRgb()
    {
        $shading = Shading::axial(0, 0, 0, 100, ['red', 'blue']);

        $function = $shading->getResource()->Function;
        $c0 = $function->C0->items;
        $this->assertEqualsWithDelta(1.0, $c0[0]->value, 0.001);
        $this->assertEqualsWithDelta(0.0, $c0[1]->value, 0.001);
        $this->assertEqualsWithDelta(0.0, $c0[2]->value, 0.001);
    }

    public function testGrayscaleNamedColorExpandsToRgbTriple()
    {
        // "black"/"white" resolve to GrayScale (1 component) via Color\Html -
        // must be expanded to an RGB triple since the shading dict is DeviceRGB
        $shading = Shading::axial(0, 0, 0, 100, ['black', 'white']);

        $function = $shading->getResource()->Function;
        $c0 = $function->C0->items;
        $this->assertCount(3, $c0);
        $this->assertEqualsWithDelta(0.0, $c0[0]->value, 0.001);
        $this->assertEqualsWithDelta(0.0, $c0[1]->value, 0.001);
        $this->assertEqualsWithDelta(0.0, $c0[2]->value, 0.001);
    }

    public function testHexColorRequiresLeadingHash()
    {
        // Color\Html::color() requires the '#' - a bare hex string falls
        // through to namedColor() and throws
        $this->expectException(\LaminasPdf\Exception\InvalidArgumentException::class);
        Shading::axial(0, 0, 0, 100, ['003366', 'ffffff']);
    }

    public function testRadialShadingType()
    {
        $shading = Shading::radialCentered(0, 0, 100, 100, [[1, 1, 1], [0, 0, 0.5]]);

        $resource = $shading->getResource();
        $this->assertEquals(3, $resource->ShadingType->value);

        $coords = $resource->Coords->items;
        $this->assertEquals(0, $coords[2]->value);
    }

    public function testDrawRoundedRectangleWithShading()
    {
        $pdf = new Pdf\PdfDocument();
        $pdf->pages[] = ($page = $pdf->newPage('A4'));

        $shading = Shading::radialCentered(60, 600, 300, 700, [[1, 1, 1], [0, 0, 0.5]]);
        $page->drawRoundedRectangleWithShading($shading, 60, 600, 300, 700, 20);

        $resources = $page->extractResources();
        $shadingKeys = $resources->Shading->getKeys();
        $this->assertCount(1, $shadingKeys);

        $pdf->save(__DIR__ . '/_files/output.pdf');
        unset($pdf);

        $pdf1 = Pdf\PdfDocument::load(__DIR__ . '/_files/output.pdf');
        $this->assertTrue($pdf1 instanceof Pdf\PdfDocument);
        unset($pdf1);

        unlink(__DIR__ . '/_files/output.pdf');
    }

    public function testDrawEllipseWithShading()
    {
        $pdf = new Pdf\PdfDocument();
        $pdf->pages[] = ($page = $pdf->newPage('A4'));

        $shading = Shading::radialCentered(60, 600, 300, 700, [[1, 1, 1], [0, 0, 0.5]]);
        $page->drawEllipseWithShading($shading, 60, 600, 300, 700);

        $resources = $page->extractResources();
        $this->assertCount(1, $resources->Shading->getKeys());
    }

    public function testDrawCircleWithShading()
    {
        $pdf = new Pdf\PdfDocument();
        $pdf->pages[] = ($page = $pdf->newPage('A4'));

        $shading = Shading::radialCentered(50, 50, 150, 150, [[1, 1, 0.6], [0.8, 0.2, 0]]);
        $page->drawCircleWithShading($shading, 100, 100, 50);

        $resources = $page->extractResources();
        $this->assertCount(1, $resources->Shading->getKeys());
    }

    public function testDrawCircleWithShadingPieSlice()
    {
        $pdf = new Pdf\PdfDocument();
        $pdf->pages[] = ($page = $pdf->newPage('A4'));

        $shading = Shading::angled(50, 50, 150, 150, ['red', 'orange'], 0);
        $page->drawCircleWithShading($shading, 100, 100, 50, 0, M_PI);

        $resources = $page->extractResources();
        $this->assertCount(1, $resources->Shading->getKeys());

        $pdf->save(__DIR__ . '/_files/output.pdf');
        unset($pdf);

        $pdf1 = Pdf\PdfDocument::load(__DIR__ . '/_files/output.pdf');
        $this->assertTrue($pdf1 instanceof Pdf\PdfDocument);
        unset($pdf1);

        unlink(__DIR__ . '/_files/output.pdf');
    }
}