<?php
$html = file_get_contents(__DIR__ . '/home_live.html');
preg_match_all('/.{0,40}ArafahGift.{0,40}/', $html, $m);
echo "Exact case matches for 'ArafahGift': " . count($m[0]) . "\n";
foreach ($m[0] as $match) {
    echo "EXACT: " . trim($match) . "\n";
}
