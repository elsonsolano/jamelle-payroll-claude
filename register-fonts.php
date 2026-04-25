<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';

$options = new \Dompdf\Options();
$options->setFontDir(storage_path('fonts'));
$options->setFontCache(storage_path('fonts'));

$dompdf = new \Dompdf\Dompdf($options);
$fm = new \Dompdf\FontMetrics($dompdf->getCanvas(), $options);

// Use FontLib directly to parse the TTF and save metrics
$fonts = [
    ['ttf' => storage_path('fonts/NotoSans-Regular.ttf'), 'prefix' => 'NotoSans-Regular_normal', 'style' => 'normal'],
    ['ttf' => storage_path('fonts/NotoSans-Bold.ttf'),    'prefix' => 'NotoSans-Bold_bold',      'style' => 'bold'],
];

foreach ($fonts as $info) {
    echo "Processing {$info['ttf']}...\n";
    $font = \FontLib\Font::load($info['ttf']);
    $font->parse();
    $metricsBase = storage_path('fonts/' . $info['prefix']);
    $font->saveAdobeFontMetrics($metricsBase . '.ufm');
    $font->close();
    copy($info['ttf'], $metricsBase . '.ttf');
    echo "  Saved: {$info['prefix']}.ufm (" . filesize($metricsBase . '.ufm') . " bytes)\n";
}

// Register in font families JSON
$families = $fm->getFontFamilies();
$families['notosans'] = [
    'normal'      => storage_path('fonts/NotoSans-Regular_normal'),
    'bold'        => storage_path('fonts/NotoSans-Bold_bold'),
    'italic'      => storage_path('fonts/NotoSans-Regular_normal'),
    'bold_italic' => storage_path('fonts/NotoSans-Bold_bold'),
];
$fm->setFontFamilies($families);
$fm->saveFontFamilies();

echo "\nAll files in storage/fonts:\n";
foreach (glob(storage_path('fonts/*')) as $f) {
    echo '  ' . basename($f) . " (" . filesize($f) . " bytes)\n";
}

// Check if peso sign is in the UFM
$ufm = file_get_contents(storage_path('fonts/NotoSans-Regular_normal.ufm'));
echo "\nUFM contains '20b1': " . (stripos($ufm, '20b1') !== false ? 'YES' : 'NO') . "\n";
echo "UFM contains '8369': " . (strpos($ufm, '8369') !== false ? 'YES' : 'NO') . "\n";
