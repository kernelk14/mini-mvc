<?php
/**
 * Build update manifest (hashes of managed framework files).
 * Usage: php tools/build-manifest.php
 */
error_reporting(E_ALL);

function mini_mf_base() { return dirname(dirname(__FILE__)); }
function mini_mf_norm($p) { return str_replace('\\', '/', $p); }
function mini_mf_hash($path)
{
    $c = @file_get_contents($path);
    if ($c === false) { return null; }
    // normalize line endings so manifest is stable across platforms/checkouts
    $c = str_replace("\r\n", "\n", $c);
    $c = str_replace("\r", "\n", $c);
    return md5($c);
}

function mini_mf_scan($dir, $base)
{
    $out = array();
    $items = @scandir($dir);
    if ($items === false) { return $out; }
    foreach ($items as $it) {
        if ($it === '.' || $it === '..') { continue; }
        $full = $dir . DIRECTORY_SEPARATOR . $it;
        $rel = mini_mf_norm(substr($full, strlen($base) + 1));
        if (is_dir($full)) {
            $out = array_merge($out, mini_mf_scan($full, $base));
        } elseif (is_file($full)) {
            $out[$rel] = mini_mf_hash($full);
        }
    }
    ksort($out);
    return $out;
}

$base = mini_mf_base();
$managed = array('system');
$files = array();
foreach ($managed as $m) {
    $dir = $base . DIRECTORY_SEPARATOR . $m;
    if (is_dir($dir)) {
        $files = array_merge($files, mini_mf_scan($dir, $base));
    } elseif (is_file($dir)) {
        $files[mini_mf_norm($m)] = mini_mf_hash($dir);
    }
}
ksort($files);
$manifest = array(
    'version' => 1,
    'generated' => date('c'),
    'managed' => $managed,
    'files' => $files,
);

$path = $base . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . '.update-manifest.json';
$filesJson = array();
foreach ($files as $k => $v) {
    $filesJson[] = '    "' . addcslashes($k, "\\\"\n\r\t") . '": "' . $v . '"';
}
$managedJson = array();
foreach ($managed as $m) { $managedJson[] = '"' . $m . '"'; }
$json = "{\n";
$json .= '  "version": 1,' . "\n";
$json .= '  "generated": "' . $manifest['generated'] . '",' . "\n";
$json .= '  "managed": [' . implode(', ', $managedJson) . '],' . "\n";
$json .= '  "files": {' . "\n" . implode(",\n", $filesJson) . "\n  }\n}";
file_put_contents($path, $json . "\n");
echo "Wrote manifest: $path\n";
echo "Files: " . count($files) . "\n";
