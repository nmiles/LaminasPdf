<?php
/**
 * Standalone demo - NOT a PHPUnit test.
 * Produces demo-shading.pdf so you can eyeball the actual gradient output
 * in a real PDF viewer, rather than just checking dictionary contents.
 *
 * Run from the repo root:
 *   php examples/demo-shading.php
 */

require __DIR__ . '/../vendor/autoload.php';

use LaminasPdf as Pdf;
use LaminasPdf\Color;
use LaminasPdf\Resource\Shading;

$pdf = new Pdf\PdfDocument();
$font = Pdf\Font::fontWithName(Pdf\Font::FONT_HELVETICA);


// ===================== PAGE 1: angled() basics =====================

$pdf->pages[] = ($page1 = $pdf->newPage('A4'));
$page1->setFont($font, 12)->setFillColor(new Color\GrayScale(0));

$page1->drawText('1. angled(0deg) - bottom to top (red -> blue)', 60, 770);
$page1->drawRectangleWithShading(
    Shading::angled(60, 690, 535, 760, [[1, 0, 0], [0, 0, 1]], 0),
    60,
    690,
    535,
    760
);

$page1->drawText('2. angled(90deg) - left to right (green -> yellow)', 60, 670);
$page1->drawRectangleWithShading(
    Shading::angled(60, 590, 535, 660, [[0, 0.6, 0], [1, 1, 0]], 90),
    60,
    590,
    535,
    660
);

$page1->drawText('3. Same rect, four different angles (0/30/60/90deg)', 60, 570);
$page1->drawRectangleWithShading(
    Shading::angled(60, 460, 165, 560, [[1, 0.5, 0], [0.5, 0, 0.8]], 0),
    60,
    460,
    165,
    560
);
$page1->drawRectangleWithShading(
    Shading::angled(180, 460, 285, 560, [[1, 0.5, 0], [0.5, 0, 0.8]], 30),
    180,
    460,
    285,
    560
);
$page1->drawRectangleWithShading(
    Shading::angled(300, 460, 405, 560, [[1, 0.5, 0], [0.5, 0, 0.8]], 60),
    300,
    460,
    405,
    560
);
$page1->drawRectangleWithShading(
    Shading::angled(420, 460, 525, 560, [[1, 0.5, 0], [0.5, 0, 0.8]], 90),
    420,
    460,
    525,
    560
);

$page1->drawText('4. radialCentered (white -> deep blue)', 60, 440);
$page1->drawRectangleWithShading(
    Shading::radialCentered(60, 290, 250, 410, [[1, 1, 1], [0, 0, 0.5]]),
    60,
    290,
    250,
    410
);

$page1->drawText('5. Gradient text via angled(0deg):', 60, 250);
$page1->setFont($font, 48);
$titleShading = Shading::angled(60, 200, 60, 240, [[1, 0, 0], [1, 0.5, 0]], 0);
$page1->drawTextWithShading($titleShading, 'GRADIENT TEXT', 60, 210);
$page1->setFont($font, 12); // reset - drawTextWithShading leaves the last-set size active


// ===================== PAGE 2: percent-weighted stops, plateau auto-expansion =====================

$pdf->pages[] = ($page2 = $pdf->newPage('A4'));
$page2->setFont($font, 12)->setFillColor(new Color\GrayScale(0));

// --- 6. "Curved pipe" via plateau auto-expansion: one interior stop with a
// large enough share automatically becomes a genuine flat plateau, with
// sharpened (non-linear) transitions at each edge - not a plain 3-stop blend.
$page2->drawText('6. "Curved pipe": darkblue 10% / lightblue 80% / darkblue 10%', 60, 770);
$page2->drawRectangleWithShading(
    Shading::angled(60, 690, 535, 760, ['darkblue 10%', 'lightblue 80%', 'darkblue 10%'], 0),
    60,
    690,
    535,
    760
);

// --- 7. Same idea via hex, left to right ---
$page2->drawText('7. Same idea, hex colors, left to right', 60, 670);
$page2->drawRectangleWithShading(
    Shading::angled(60, 590, 535, 660, ['#003366 10%', '#66aaff 80%', '#003366 10%'], 90),
    60,
    590,
    535,
    660
);

// --- 8. Auto-fill: two blank stops share whatever's left after the explicit one,
// still triggers the same plateau expansion as the fully-explicit version above ---
$page2->drawText('8. Auto-fill: darkblue (blank) / lightblue 80% / darkblue (blank)', 60, 570);
$page2->drawRectangleWithShading(
    Shading::angled(60, 490, 535, 560, ['darkblue', 'lightblue 80%', 'darkblue'], 0),
    60,
    490,
    535,
    560
);

// --- 9. Resilience: percentages add up to well over 100%, normalizes rather than throwing ---
$page2->drawText('9. Over-100% input (50%+50%+50%=150%), normalizes automatically', 60, 470);
$page2->drawRectangleWithShading(
    Shading::angled(60, 390, 535, 460, ['red 50%', 'yellow 50%', 'blue 50%'], 0),
    60,
    390,
    535,
    460
);

// --- 10. Rainbow, 5 named colors, diagonal angle ---
$page2->drawText('10. Rainbow, 5 named colors, 45deg', 60, 370);
$page2->drawRectangleWithShading(
    Shading::angled(60, 260, 535, 360, ['red', 'orange', 'yellow', 'green', 'blue'], 45),
    60,
    260,
    535,
    360
);

// --- 11. Plateau-expanded gradient text ---
$page2->drawText('11. Plateau text (same auto-expansion as case 6):', 60, 220);
$page2->setFont($font, 48);
$pipeTextShading = Shading::angled(
    60,
    170,
    320,
    210,
    ['darkblue 10%', 'lightblue 80%', 'darkblue 10%'],
    90
);
$page2->drawTextWithShading($pipeTextShading, 'PIPE TEXT', 60, 170);
$page2->setFont($font, 12);


// ===================== PAGE 3: ellipse/circle shading, rounded rect, bar chart =====================

$pdf->pages[] = ($page3 = $pdf->newPage('A4'));
$page3->setFont($font, 12)->setFillColor(new Color\GrayScale(0));

// --- 12. Full ellipse, radial shading ---
$page3->drawText('12. drawEllipseWithShading (radial)', 60, 770);
$page3->drawEllipseWithShading(
    Shading::radialCentered(60, 650, 300, 750, ['white', '#003366']),
    60,
    650,
    300,
    750
);

// --- 13. Full circle, radial shading ---
$page3->drawText('13. drawCircleWithShading (radial)', 60, 610);
$page3->drawCircleWithShading(
    Shading::radialCentered(120, 550, 220, 650, [[1, 1, 0.6], [0.8, 0.2, 0]]),
    170,
    600,
    50
);

// --- 14. Partial circle (pie slice), angular clip via startAngle/endAngle ---
$page3->drawText('14. drawCircleWithShading, pie slice (0 to 3PI/2 radians)', 60, 480);
$page3->drawCircleWithShading(
    Shading::angled(320, 400, 480, 480, ['#ffcc00', 'red'], 0),
    400,
    440,
    50,
    0,
    M_PI * 1.5
);

// --- 15. Rounded rectangle with a radial shading ---
$page3->drawText('15. Rounded rectangle, radial shading', 60, 340);
$page3->drawRoundedRectangleWithShading(
    Shading::radialCentered(60, 260, 300, 330, [[1, 1, 1], [0, 0, 0.4]]),
    60,
    260,
    300,
    330,
    20
);

// --- 16. "Bar chart" of curved-pipe-style bars, using percent stops + angled(90deg) -
// each bar is a full plateau-expanded gradient, exercising the resource-dedup
// naming across many independent shadings on one page.
$page3->drawText('16. Bar chart using curved-pipe bars (percent stops + angled(90deg))', 60, 220);
$barColors = ['darkblue 10%', 'lightblue 10%', 'lightblue 60%', 'lightblue 10%', 'darkblue 10%'];
$barHeights = [60, 100, 75, 130, 90];
$barX = 60;
foreach ($barHeights as $h) {
    $page3->drawRectangleWithShading(
        Shading::angled($barX, 60, $barX + 60, 60 + $h, $barColors, 90),
        $barX,
        60,
        $barX + 60,
        60 + $h
    );
    $barX += 80;
}


// ===================== PAGE 4: single-color auto-shades =====================

$pdf->pages[] = ($page4 = $pdf->newPage('A4'));
$page4->setFont($font, 12)->setFillColor(new Color\GrayScale(0));

// --- 17. A single named color alone auto-expands into lighter -> base -> darker ---
$page4->drawText('17. One color only (named): [\'salmon\']', 60, 770);
$page4->drawRectangleWithShading(
    Shading::angled(60, 690, 535, 760, ['salmon'], 90),
    60,
    690,
    535,
    760
);

// --- 18. Same trick via hex ---
$page4->drawText('18. One color only (hex): [\'#4682b4\']', 60, 670);
$page4->drawRectangleWithShading(
    Shading::angled(60, 590, 535, 660, ['#4682b4'], 0),
    60,
    590,
    535,
    660
);

// --- 19. Same trick via a raw RGB array ---
$page4->drawText('19. One color only (RGB array): [[0.4, 0.7, 0.3]]', 60, 570);
$page4->drawRectangleWithShading(
    Shading::angled(60, 490, 535, 560, [[0.4, 0.7, 0.3]], 45),
    60,
    490,
    535,
    560
);

// --- 20. Bar chart where every bar is just a single named color - no manual
// lighter/darker specification needed, each bar auto-shades on its own.
$page4->drawText('20. Bar chart, each bar a single auto-shaded color', 60, 440);
$singleColorBars = ['crimson', 'goldenrod', 'seagreen', 'steelblue', 'orchid'];
$barX = 60;
foreach ($singleColorBars as $color) {
    $page4->drawRectangleWithShading(
        Shading::angled($barX, 60, $barX + 80, 400, [$color], 90),
        $barX,
        60,
        $barX + 80,
        400
    );
    $barX += 95;
}

$outputPath = __DIR__ . '/output/demo-shading.pdf';
$pdf->save($outputPath);

echo "Saved: $outputPath\n";