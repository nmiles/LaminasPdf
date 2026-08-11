<?php
namespace LaminasPdf\Resource;

use LaminasPdf\Color;
use LaminasPdf\Exception;
use LaminasPdf\InternalType;
use LaminasPdf\ObjectFactory;

class Shading extends AbstractResource
{
    /**
     * Exponent used for the segment entering an auto-expanded plateau (e.g.
     * darkblue -> lightblue at the left edge of a plateau). Large (>1) is
     * the mirror image of the exit exponent - barely any visible change
     * until right near the outer edge, then a quick snap. Tune to taste -
     * larger = sharper.
     */
    private const PLATEAU_ENTRY_EXPONENT = 1.8;

    /**
     * Exponent used for the segment leaving an auto-expanded plateau (e.g.
     * lightblue -> darkblue at the right edge of a plateau). Small (<1)
     * makes the color change happen quickly right at the outer edge, then
     * barely move for the rest of the zone. Tune to taste - smaller = sharper.
     */
    private const PLATEAU_EXIT_EXPONENT = 0.5;

    /**
     * Default, linear interpolation - used everywhere except the sharpened
     * entry/exit segments above.
     */
    private const LINEAR_EXPONENT = 1.0;

    // Deliberately NOT calling parent::__construct() - it would allocate
    // its own factory, disconnected from the one the Function object needs to share.
    protected function __construct(InternalType\DictionaryObject $shadingDict, ObjectFactory $factory)
    {
        $this->_objectFactory = $factory;
        $this->_resource = $factory->newObject($shadingDict);
    }


    // ===================== Low-level builders =====================

    /**
     * Axial (Type 2 / "linear") shading between two points, through 2 or more colors.
     *
     * @param float $x1,$y1  Gradient axis start point
     * @param float $x2,$y2  Gradient axis end point
     * @param array $colors  List of 2+ color stops - see class docblock for accepted formats
     * @param bool  $extend  Extend the end colors beyond the axis endpoints
     */
    public static function axial($x1, $y1, $x2, $y2, array $colors, $extend = true)
    {
        $factory = ObjectFactory::createFactory(1);
        $functionObj = self::_buildColorFunction($factory, $colors);

        $shadingDict = new InternalType\DictionaryObject();
        $shadingDict->ShadingType = new InternalType\NumericObject(2);
        $shadingDict->ColorSpace  = new InternalType\NameObject('DeviceRGB');
        $shadingDict->Coords = new InternalType\ArrayObject([
            new InternalType\NumericObject($x1),
            new InternalType\NumericObject($y1),
            new InternalType\NumericObject($x2),
            new InternalType\NumericObject($y2),
        ]);
        $shadingDict->Function = $functionObj;
        $shadingDict->Extend = new InternalType\ArrayObject([
            new InternalType\BooleanObject($extend),
            new InternalType\BooleanObject($extend),
        ]);

        return new self($shadingDict, $factory);
    }

    /**
     * Radial (Type 3) shading between two circles, through 2 or more colors.
     *
     * @param float $x0,$y0,$r0  Inner circle (center + radius)
     * @param float $x1,$y1,$r1  Outer circle (center + radius)
     * @param array $colors  List of 2+ color stops - see class docblock for accepted formats
     * @param bool  $extend  Extend the outer color beyond r1
     */
    public static function radial($x0, $y0, $r0, $x1, $y1, $r1, array $colors, $extend = true)
    {
        $factory = ObjectFactory::createFactory(1);
        $functionObj = self::_buildColorFunction($factory, $colors);

        $shadingDict = new InternalType\DictionaryObject();
        $shadingDict->ShadingType = new InternalType\NumericObject(3);
        $shadingDict->ColorSpace  = new InternalType\NameObject('DeviceRGB');
        $shadingDict->Coords = new InternalType\ArrayObject([
            new InternalType\NumericObject($x0),
            new InternalType\NumericObject($y0),
            new InternalType\NumericObject($r0),
            new InternalType\NumericObject($x1),
            new InternalType\NumericObject($y1),
            new InternalType\NumericObject($r1),
        ]);
        $shadingDict->Function = $functionObj;
        $shadingDict->Extend = new InternalType\ArrayObject([
            new InternalType\BooleanObject($extend),
            new InternalType\BooleanObject($extend),
        ]);

        return new self($shadingDict, $factory);
    }


    // ===================== Convenience factories (rect-relative) =====================

    /**
     * Shading for a gradient at an arbitrary angle over the given rectangle,
     * matching CSS's linear-gradient(<angle>deg, ...) numeric-angle behavior.
     *
     * 0deg = bottom to top, increasing clockwise (90deg = left to right,
     * 180deg = top to bottom, 270deg = right to left) - same convention as CSS.
     *
     * @param float $x1,$y1,$x2,$y2  Rectangle bounds
     * @param array $colors  List of 2+ color stops - see class docblock for accepted formats
     * @param float $degrees
     */
    public static function angled($x1, $y1, $x2, $y2, array $colors, $degrees)
    {
        $theta = deg2rad($degrees);
        $dirX = sin($theta);
        $dirY = cos($theta);

        $width = $x2 - $x1;
        $height = $y2 - $y1;

        // Half-length needed so the gradient line, at this angle, still covers the full box
        $halfLength = (abs($width * $dirX) + abs($height * $dirY)) / 2;

        $centerX = ($x1 + $x2) / 2;
        $centerY = ($y1 + $y2) / 2;

        $axisX1 = $centerX - $halfLength * $dirX;
        $axisY1 = $centerY - $halfLength * $dirY;
        $axisX2 = $centerX + $halfLength * $dirX;
        $axisY2 = $centerY + $halfLength * $dirY;

        return self::axial($axisX1, $axisY1, $axisX2, $axisY2, $colors);
    }

    /**
     * Shading for a radial gradient centered on the given rectangle.
     * Outer radius reaches the corners (half the diagonal) so the whole
     * rect gets shaded. For a custom origin/radius, use radial() directly.
     *
     * @param float $x1,$y1,$x2,$y2  Rectangle bounds
     * @param array $colors  List of 2+ color stops - see class docblock for accepted formats
     */
    public static function radialCentered($x1, $y1, $x2, $y2, array $colors)
    {
        $centerX = ($x1 + $x2) / 2;
        $centerY = ($y1 + $y2) / 2;
        $outerRadius = sqrt((($x2 - $x1) / 2) ** 2 + (($y2 - $y1) / 2) ** 2);

        return self::radial($centerX, $centerY, 0, $centerX, $centerY, $outerRadius, $colors);
    }


    // ===================== Color stop parsing =====================
    //
    // Each entry in $colors can be:
    //   - [r, g, b]                    plain RGB triple, even spacing (unchanged from before)
    //   - [[r, g, b], $percent]        RGB triple with an explicit share of the total width
    //   - "colorname"                  resolved via Color\Html, even spacing
    //   - "colorname 10%"              resolved via Color\Html, explicit 10% share
    //   - "#rrggbb" / "#rrggbb 10%"    hex resolved via Color\Html - the '#' is REQUIRED,
    //                                  Color\Html::color() does not accept bare hex
    //
    // Percent is a SHARE of the total axis width. Missing shares split whatever's left
    // over evenly; the whole set is then normalized to sum to exactly 100 regardless of
    // what was actually given, so over/under-100% totals degrade gracefully rather than
    // throwing. Segment boundaries are placed using the AVERAGE of each segment's two
    // neighboring stops' shares (NOT a running cumulative sum from the start), so
    // symmetric stop patterns produce a symmetric result.
    //
    // A single interior stop can't be a genuine FLAT plateau by itself - with only one
    // boundary available either side of it, its declared share only ever influences the
    // scale of two full gradual transitions, never carves out a flat region. So: any
    // interior stop flanked by two DIFFERENT colors is automatically expanded into three
    // duplicate-color points - a share matching each neighbor, plus a "core" share solved
    // algebraically so the RESULTING flat region comes out to EXACTLY this stop's declared
    // percentage. The two real transition segments this creates (entering and leaving the
    // plateau) use a sharpened, non-linear interpolation (PLATEAU_ENTRY_EXPONENT /
    // PLATEAU_EXIT_EXPONENT above) rather than a plain linear ramp, so the visible color
    // change concentrates near each outer edge instead of spreading evenly across the
    // whole transition zone. If the target is too extreme relative to its neighbors, the
    // stop is left as a single point (existing single-point behavior).
    // e.g. ['darkblue', 'lightblue 80%', 'darkblue'] auto-expands to
    //      ['darkblue 10%', 'lightblue 10%', 'lightblue 70%', 'lightblue 10%', 'darkblue 10%']
    // with the two 10%-wide transition segments sharpened per the constants above.

    /**
     * Build the color Function driving a shading, from 2 or more color stops.
     *
     * @return InternalType\IndirectObject
     */
    private static function _buildColorFunction(ObjectFactory $factory, array $colors)
    {
        if (count($colors) === 1) {
            // A single color auto-expands into a subtle lighter -> base -> darker
            // transition, via the same lightness-based shading Color\Html::shades() uses.
            $colors = Color\Html::shades($colors[0]);
        }

        $stopCount = count($colors);

        if ($stopCount < 2) {
            throw new Exception\InvalidArgumentException('Shading requires at least 2 colors.');
        }

        $parsedStops = array_map([self::class, '_parseColorStop'], $colors);
        $rgbColors = array_column($parsedStops, 'rgb');

        if ($stopCount === 2) {
            // Percent is meaningless for a straight 2-stop gradient - a single
            // segment spans the whole domain regardless of any weighting.
            return self::_buildType2Function($factory, $rgbColors[0], $rgbColors[1]);
        }

        $percents = self::_resolveStopPercents($parsedStops);

        [$rgbColors, $percents, $segmentExponents] = self::_expandPlateauStops($rgbColors, $percents);

        $segmentCount = count($rgbColors) - 1;

        // Each segment's width is the AVERAGE of its two endpoint stops' shares,
        // then rescaled so all segment widths sum to exactly 100.
        $segmentWidths = [];
        for ($i = 0; $i < $segmentCount; $i++) {
            $segmentWidths[$i] = ($percents[$i] + $percents[$i + 1]) / 2;
        }
        $widthTotal = array_sum($segmentWidths);
        $scale = $widthTotal > 0 ? 100 / $widthTotal : 100 / $segmentCount;

        $subFunctions = new InternalType\ArrayObject();
        $bounds = new InternalType\ArrayObject();
        $encode = new InternalType\ArrayObject();

        $cumulative = 0;
        for ($i = 0; $i < $segmentCount; $i++) {
            $subFunctions->items[] = self::_buildType2Function(
                $factory,
                $rgbColors[$i],
                $rgbColors[$i + 1],
                $segmentExponents[$i] ?? self::LINEAR_EXPONENT
            );

            $encode->items[] = new InternalType\NumericObject(0);
            $encode->items[] = new InternalType\NumericObject(1);

            if ($i < $segmentCount - 1) {
                $cumulative += $segmentWidths[$i] * $scale;
                $bounds->items[] = new InternalType\NumericObject($cumulative / 100);
            }
        }

        $stitchingFunction = new InternalType\DictionaryObject();
        $stitchingFunction->FunctionType = new InternalType\NumericObject(3);
        $stitchingFunction->Domain = new InternalType\ArrayObject([
            new InternalType\NumericObject(0),
            new InternalType\NumericObject(1),
        ]);
        $stitchingFunction->Functions = $subFunctions;
        $stitchingFunction->Bounds = $bounds;
        $stitchingFunction->Encode = $encode;

        return $factory->newObject($stitchingFunction);
    }

    /**
     * Auto-expand any interior stop that has enough share to warrant a real flat
     * plateau (see class docblock). Stops already part of a same-color run, or
     * at either end of the array, are left untouched.
     *
     * Also returns per-segment exponent overrides: the segment entering an
     * expanded plateau gets PLATEAU_ENTRY_EXPONENT, the segment leaving it gets
     * PLATEAU_EXIT_EXPONENT, everything else stays LINEAR_EXPONENT.
     *
     * @param array $rgbColors  List of [r,g,b] triples
     * @param array $percents   Resolved percents (same count as $rgbColors, sums to 100)
     * @return array [expandedRgbColors, expandedPercents, segmentExponents]
     */
    private static function _expandPlateauStops(array $rgbColors, array $percents)
    {
        $count = count($rgbColors);
        $expandedRgb = [];
        $expandedPercents = [];
        $segmentExponents = [];
        $pendingExponent = self::LINEAR_EXPONENT;

        $pushStop = function ($color, $percent) use (
            &$expandedRgb,
            &$expandedPercents,
            &$segmentExponents,
            &$pendingExponent
        ) {
            if (count($expandedRgb) > 0) {
                $segmentExponents[] = $pendingExponent;
                $pendingExponent = self::LINEAR_EXPONENT;
            }
            $expandedRgb[] = $color;
            $expandedPercents[] = $percent;
        };

        for ($i = 0; $i < $count; $i++) {
            $ownColor = $rgbColors[$i];
            $targetPercent = $percents[$i]; // desired share of the FINAL rendered width

            $isFirst = ($i === 0);
            $isLast = ($i === $count - 1);
            $sameAsLeft = !$isFirst && $rgbColors[$i] === $rgbColors[$i - 1];
            $sameAsRight = !$isLast && $rgbColors[$i] === $rgbColors[$i + 1];

            if ($isFirst || $isLast || $sameAsLeft || $sameAsRight) {
                $pushStop($ownColor, $targetPercent);
                continue;
            }

            $leftPercent = $percents[$i - 1];
            $rightPercent = $percents[$i + 1];
            $sumOuter = $leftPercent + $rightPercent;

            $denominator = 100 - $targetPercent;
            $corePercent = $denominator > 0
                ? $sumOuter * (1.5 * $targetPercent - 50) / $denominator
                : 0;

            if ($corePercent <= 0) {
                // Not enough share to justify a real plateau - leave as a single point
                $pushStop($ownColor, $targetPercent);
                continue;
            }

            // Entering the plateau: sharpen so the change happens right at the edge
            $pendingExponent = self::PLATEAU_ENTRY_EXPONENT;
            $pushStop($ownColor, $leftPercent);

            // Flat interior - exponent is irrelevant since C0 == C1
            $pushStop($ownColor, $corePercent);
            $pushStop($ownColor, $rightPercent);

            // Leaving the plateau: sharpen the upcoming segment the same way, mirrored
            $pendingExponent = self::PLATEAU_EXIT_EXPONENT;
        }

        return [$expandedRgb, $expandedPercents, $segmentExponents];
    }

    /**
     * Parse one color-stop entry into ['rgb' => [r,g,b], 'percent' => float|null].
     */
    private static function _parseColorStop($stop)
    {
        if (is_string($stop)) {
            $stop = trim($stop);

            if (preg_match('/^(.*?)\s+(-?\d+(?:\.\d+)?)%\s*$/', $stop, $matches)) {
                $colorSpec = trim($matches[1]);
                $percent = (float) $matches[2];
            } else {
                $colorSpec = $stop;
                $percent = null;
            }

            $resolved = Color\Html::color($colorSpec);
            $components = $resolved->getComponents();

            // Html::color() returns GrayScale (1 component) whenever r==g==b,
            // e.g. "black"/"white"/"gray" or a hex like #808080 - but the
            // shading dict is always built as DeviceRGB, so a 1-component
            // result needs expanding to a triple, not passed through as-is.
            if (count($components) === 1) {
                $gray = $components[0];
                $rgb = [$gray, $gray, $gray];
            } else {
                $rgb = $components;
            }

            return ['rgb' => $rgb, 'percent' => $percent];
        }

        if (is_array($stop)) {
            // [[r,g,b], percent] pair
            if (isset($stop[0]) && is_array($stop[0])) {
                return ['rgb' => $stop[0], 'percent' => $stop[1] ?? null];
            }
            // plain [r,g,b] triple
            return ['rgb' => $stop, 'percent' => null];
        }

        throw new Exception\InvalidArgumentException(
            'Each color stop must be an [r,g,b] array, an [[r,g,b], percent] pair, '
            . 'or a "colorname" / "colorname N%" string (hex needs a leading #).'
        );
    }

    /**
     * Resolve a parsed stop list's percentages: fill in blanks by splitting
     * whatever's left evenly, then normalize the whole set to sum to exactly
     * 100 - this is what makes over/under-100% input totals non-fatal.
     *
     * @return float[]
     */
    private static function _resolveStopPercents(array $parsedStops)
    {
        $count = count($parsedStops);
        $percents = [];
        $explicitSum = 0;
        $nullIndexes = [];

        foreach ($parsedStops as $i => $stop) {
            if ($stop['percent'] === null) {
                $percents[$i] = null;
                $nullIndexes[] = $i;
            } else {
                $p = max(0, (float) $stop['percent']); // no negative shares
                $percents[$i] = $p;
                $explicitSum += $p;
            }
        }

        if (count($nullIndexes) > 0) {
            $remaining = max(0, 100 - $explicitSum);
            $share = $remaining / count($nullIndexes);
            foreach ($nullIndexes as $i) {
                $percents[$i] = $share;
            }
        }

        $total = array_sum($percents);

        if ($total <= 0) {
            // Degenerate case (e.g. every stop given 0%) - fall back to an even split
            return array_fill(0, $count, 100 / $count);
        }

        $scale = 100 / $total;
        return array_map(fn($p) => $p * $scale, $percents);
    }

    /**
     * Build a single Type 2 (exponential interpolation) function between two colors.
     *
     * @param float $exponent  PDF's /N - 1.0 is linear, <1 concentrates change
     *                         near the start, >1 concentrates it near the end.
     */
    private static function _buildType2Function(ObjectFactory $factory, array $c0Rgb, array $c1Rgb, $exponent = 1.0)
    {
        $function = new InternalType\DictionaryObject();
        $function->FunctionType = new InternalType\NumericObject(2);
        $function->Domain = new InternalType\ArrayObject([
            new InternalType\NumericObject(0),
            new InternalType\NumericObject(1),
        ]);
        $function->C0 = new InternalType\ArrayObject(array_map(
            fn($c) => new InternalType\NumericObject($c),
            $c0Rgb
        ));
        $function->C1 = new InternalType\ArrayObject(array_map(
            fn($c) => new InternalType\NumericObject($c),
            $c1Rgb
        ));
        $function->N = new InternalType\NumericObject($exponent);

        return $factory->newObject($function);
    }
}