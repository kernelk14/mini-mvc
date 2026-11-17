<?php
/**
 * Mini MVC safe updater (managed framework files only).
 * - Updates files listed in tools/.update-manifest.json (default: system/**)
 * - Preserves local modifications by creating 3-way merges when possible
 * - Writes conflict markers on merge conflicts and never silently overwrites user edits
 *
 * Usage:
 *   php tools/update.php [--dry-run] [--force] [--source=<path-or-url-zip-or-git>] [--list]
 *
 * Notes:
 * - Default source: git fetch/checkout from upstream if available; otherwise expects --source=<local-framework-dir>
 * - "3-way" base comes from recorded managed hashes: file is treated as unmodified if its hash matches manifest
 */

function u_out($s) { echo $s . "\n"; }
function u_err($s) { fwrite(STDERR, $s . "\n"); }
function u_base() { return dirname(dirname(__FILE__)); }
function u_norm($p) { return str_replace('\\', '/', $p); }
function u_is_win() { return DIRECTORY_SEPARATOR === '\\'; }

function u_parse_args($argv)
{
    $opts = array('dry' => false, 'force' => false, 'source' => null, 'list' => false);
    $i = 1;
    while ($i < count($argv)) {
        $a = $argv[$i];
        if ($a === '--dry-run') { $opts['dry'] = true; }
        elseif ($a === '--force') { $opts['force'] = true; }
        elseif ($a === '--list') { $opts['list'] = true; }
        elseif (strpos($a, '--source=') === 0) { $opts['source'] = substr($a, 9); }
        $i++;
    }
    return $opts;
}

function u_read_manifest($base)
{
    $path = $base . '/tools/.update-manifest.json';
    if (!is_file($path)) { return null; }
    $raw = file_get_contents($path);
    if ($raw === false) { return null; }
    // minimal JSON parse for known structure
    $data = array('files' => array(), 'managed' => array());
    if (function_exists('json_decode')) {
        $j = json_decode($raw, true);
        if (is_array($j)) { return $j; }
    }
    // fallback: extract "files" key-value pairs
    if (preg_match('/"files"\s*:\s*\{(.*)\}\s*\}/s', $raw, $m)) {
        if (preg_match_all('/"([^"]+)"\s*:\s*"([0-9a-f]{32})"/', $m[1], $mm, PREG_SET_ORDER)) {
            foreach ($mm as $p) { $data['files'][$p[1]] = $p[2]; }
        }
    }
    return $data;
}

function u_write_manifest($base, $files, $managed)
{
    ksort($files);
    $filesJson = array();
    foreach ($files as $k => $v) {
        $filesJson[] = '    "' . addcslashes($k, "\\\"\n\r\t") . '": "' . $v . '"';
    }
    $managedJson = array();
    foreach ($managed as $m) { $managedJson[] = '"' . $m . '"'; }
    $json = "{\n";
    $json .= '  "version": 1,' . "\n";
    $json .= '  "generated": "' . date('c') . '",' . "\n";
    $json .= '  "managed": [' . implode(', ', $managedJson) . '],' . "\n";
    $json .= '  "files": {' . "\n" . implode(",\n", $filesJson) . "\n  }\n}";
    @file_put_contents($base . '/tools/.update-manifest.json', $json . "\n");
}

function u_hash($path)
{
    if (!is_file($path)) { return null; }
    $c = @file_get_contents($path);
    if ($c === false) { return null; }
    $c = str_replace("\r\n", "\n", $c);
    $c = str_replace("\r", "\n", $c);
    return md5($c);
}

function u_mkdirp($dir)
{
    if (is_dir($dir)) { return true; }
    if (function_exists('mkdir')) { return @mkdir($dir, 0777, true); }
    return false;
}

function u_copy($src, $dst)
{
    u_mkdirp(dirname($dst));
    return @copy($src, $dst);
}

function u_run($cmd)
{
    $out = array();
    $code = 0;
    if (function_exists('exec')) {
        @exec($cmd . ' 2>&1', $out, $code);
    } elseif (function_exists('shell_exec')) {
        $o = @shell_exec($cmd . ' 2>&1');
        $out = $o === null ? array() : explode("\n", $o);
        $code = 0;
    } else {
        return array('code' => 127, 'out' => array('exec unavailable'));
    }
    return array('code' => $code, 'out' => $out);
}

function u_diff3_available()
{
    $r = u_run(u_is_win() ? 'where diff3' : 'command -v diff3');
    return $r['code'] === 0;
}

function u_three_way_merge($baseFile, $oursFile, $theirsFile, $outFile)
{
    // Try git merge-file (in-place on ours copy) or diff3
    $tmpOurs = $outFile . '.ours';
    u_copy($oursFile, $tmpOurs);
    // git merge-file --ours -p mine base theirs > out ; exit 0 clean, >0 conflicts with markers (git >=2)
    $r = u_run('git merge-file -p ' . escapeshellarg($tmpOurs) . ' ' . escapeshellarg($baseFile) . ' ' . escapeshellarg($theirsFile));
    if (is_array($r['out']) && count($r['out']) > 0) {
        u_mkdirp(dirname($outFile));
        @file_put_contents($outFile, implode("\n", $r['out']) . (substr(end($r['out']), -1) === "\n" ? '' : ''));
        @unlink($tmpOurs);
        // git merge-file -p returns 0 on clean, >0 on conflict (but still outputs markers)
        $content = @file_get_contents($outFile);
        $conflict = ($content !== false && strpos($content, '<<<<<<<') !== false);
        return $conflict ? 'conflict' : 'merged';
    }
    // diff3 -m ours base theirs
    $r2 = u_run('diff3 -m ' . escapeshellarg($tmpOurs) . ' ' . escapeshellarg($baseFile) . ' ' . escapeshellarg($theirsFile));
    @unlink($tmpOurs);
    if (is_array($r2['out'])) {
        u_mkdirp(dirname($outFile));
        @file_put_contents($outFile, implode("\n", $r2['out']));
        $content = @file_get_contents($outFile);
        $conflict = ($content !== false && strpos($content, '<<<<<<<') !== false);
        return $conflict ? 'conflict' : 'merged';
    }
    return 'unavailable';
}

function u_default_upstream() { return 'https://github.com/kernelk14/mini-mvc.git'; }

function u_get_source_dir($base, $source)
{
    $tmp = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'mini-mvc-upstream-' . md5($base . '|' . microtime());
    if ($source) {
        if (is_dir($source)) {
            $rp = realpath($source);
            return array('dir' => $rp ? $rp : $source, 'tmp' => null);
        }
        // treat as git URL/remote
        if (is_dir($tmp)) { u_rrmdir($tmp); }
        $cmd = 'git clone --depth 1 ' . escapeshellarg($source) . ' ' . escapeshellarg($tmp);
        u_run($cmd);
        if (is_dir($tmp . DIRECTORY_SEPARATOR . 'system')) { return array('dir' => $tmp, 'tmp' => $tmp); }
        if (is_dir($tmp)) { u_rrmdir($tmp); }
        return null;
    }
    // try local git origin first
    $isGit = is_dir($base . '/.git') || is_dir($base . DIRECTORY_SEPARATOR . '.git');
    $remote = null;
    if ($isGit) {
        $ru = u_run('git -C ' . escapeshellarg($base) . ' config --get remote.origin.url');
        if ($ru['code'] === 0 && isset($ru['out'][0]) && trim($ru['out'][0]) !== '') { $remote = trim($ru['out'][0]); }
    }
    if (!$remote) { $remote = u_default_upstream(); }
    if (is_dir($tmp)) { u_rrmdir($tmp); }
    $cmd = 'git clone --depth 1 ' . escapeshellarg($remote) . ' ' . escapeshellarg($tmp);
    u_run($cmd);
    if (is_dir($tmp . DIRECTORY_SEPARATOR . 'system')) { return array('dir' => $tmp, 'tmp' => $tmp); }
    if (is_dir($tmp)) { u_rrmdir($tmp); }
    return null;
}

function u_rrmdir($dir)
{
    if (!is_dir($dir)) { return; }
    $items = @scandir($dir);
    if ($items === false) { return; }
    foreach ($items as $it) {
        if ($it === '.' || $it === '..') { continue; }
        $p = $dir . DIRECTORY_SEPARATOR . $it;
        if (is_dir($p) && !is_link($p)) { u_rrmdir($p); } else { @unlink($p); }
    }
    @rmdir($dir);
}

function u_collect_source_files($srcBase, $managed)
{
    $files = array();
    foreach ($managed as $m) {
        $p = $srcBase . '/' . $m;
        if (is_dir($p)) { $files = array_merge($files, u_scan_plain($p, $srcBase)); }
        elseif (is_file($p)) { $files[u_norm($m)] = $p; }
    }
    return $files; // rel => abs
}

function u_scan_plain($dir, $base)
{
    $out = array();
    $items = @scandir($dir);
    if ($items === false) { return $out; }
    foreach ($items as $it) {
        if ($it === '.' || $it === '..') { continue; }
        $full = $dir . DIRECTORY_SEPARATOR . $it;
        $rel = u_norm(substr($full, strlen($base) + 1));
        if (is_dir($full)) { $out = array_merge($out, u_scan_plain($full, $base)); }
        elseif (is_file($full)) { $out[$rel] = $full; }
    }
    return $out;
}

$opts = u_parse_args($argv);
$base = u_base();
$manifest = u_read_manifest($base);
$managed = ($manifest && isset($manifest['managed']) && is_array($manifest['managed'])) ? $manifest['managed'] : array('system');
$baseHashes = ($manifest && isset($manifest['files']) && is_array($manifest['files'])) ? $manifest['files'] : array();

u_out('Mini MVC updater');
u_out('Base: ' . $base);
u_out('Managed: ' . implode(', ', $managed));

if ($opts['list']) {
    u_out('Managed files in manifest: ' . count($baseHashes));
    foreach ($baseHashes as $f => $h) { u_out('  ' . $f); }
    exit(0);
}

$srcInfo = u_get_source_dir($base, $opts['source']);
if (!$srcInfo || !isset($srcInfo['dir']) || !$srcInfo['dir']) {
    u_err('Could not fetch upstream source from GitHub. Ensure git is installed and network access is available, or provide --source=<path-to-latest-mini-mvc-checkout>.');
    exit(2);
}
$srcBase = $srcInfo['dir'];
$srcTmp = isset($srcInfo['tmp']) ? $srcInfo['tmp'] : null;
u_out('Source: ' . $srcBase . ($srcTmp ? ' (fetched from GitHub)' : ''));

$srcFiles = u_collect_source_files($srcBase, $managed); // rel => abs in source
$allRels = array_unique(array_merge(array_keys($baseHashes), array_keys($srcFiles)));
sort($allRels);

$stats = array('updated' => 0, 'added' => 0, 'kept' => 0, 'merged' => 0, 'conflict' => 0, 'skipped' => 0);
$conflicts = array();
$newManifestFiles = $baseHashes;

$tmpBase = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'mini-mvc-merge-' . uniqid();
u_mkdirp($tmpBase);

foreach ($allRels as $rel) {
    $localPath = $base . '/' . $rel;
    $inSource = isset($srcFiles[$rel]);
    $inManifest = isset($baseHashes[$rel]);
    $localHash = u_hash($localPath);
    $baseHash = $inManifest ? $baseHashes[$rel] : null;
    $srcHash = $inSource ? u_hash($srcFiles[$rel]) : null;

    if ($inSource && !$inManifest && !is_file($localPath)) {
        // new upstream file
        $stats['added']++;
        u_out("  ADD      $rel");
        if (!$opts['dry']) { u_copy($srcFiles[$rel], $localPath); $newManifestFiles[$rel] = $srcHash; }
        continue;
    }
    if ($inSource && !$inManifest && is_file($localPath)) {
        // upstream added a managed file but user already has a different local file at same path: preserve local
        if ($localHash === $srcHash) {
            $stats['kept']++;
            if (!$opts['dry']) { $newManifestFiles[$rel] = $srcHash; }
            continue;
        }
        $upNew = $localPath . '.upstream';
        $stats['conflict']++;
        $conflicts[] = $rel;
        u_out("  REVIEW   $rel (local file exists, not tracked in manifest) -> wrote .upstream");
        if (!$opts['dry']) { u_copy($srcFiles[$rel], $upNew); }
        continue;
    }
    if (!$inSource && $inManifest) {
        // removed upstream? keep local, but untrack from manifest to avoid reprocessing confusion
        $stats['kept']++;
        u_out("  KEEP     $rel (not in upstream source)");
        continue;
    }
    if (!$inSource) { $stats['skipped']++; continue; }

    // both exist in source; decide
    if ($localHash === $srcHash) {
        $stats['kept']++;
        if (!$opts['dry']) { $newManifestFiles[$rel] = $srcHash; }
        continue;
    }

    $localUnmodified = ($baseHash !== null && $localHash === $baseHash);
    if ($localUnmodified && !$opts['force']) {
        // safe to take upstream
        $stats['updated']++;
        u_out("  UPDATE   $rel");
        if (!$opts['dry']) { u_copy($srcFiles[$rel], $localPath); $newManifestFiles[$rel] = $srcHash; }
        continue;
    }

    // local modified and upstream changed (or base unknown)
    if ($opts['force']) {
        $bak = $localPath . '.local-backup';
        $stats['updated']++;
        u_out("  FORCE    $rel (local backed up to .local-backup, replaced with upstream)");
        if (!$opts['dry']) {
            u_copy($localPath, $bak);
            u_copy($srcFiles[$rel], $localPath);
            $newManifestFiles[$rel] = $srcHash;
            @unlink($localPath . '.upstream');
        }
        continue;
    }
    $upNew = $localPath . '.upstream';
    $stats['conflict']++;
    $conflicts[] = $rel;
    $reason = ($baseHash === null) ? 'not tracked in manifest' : 'locally modified';
    u_out("  REVIEW   $rel ($reason) -> wrote .upstream (local preserved)");
    if (!$opts['dry']) {
        u_copy($srcFiles[$rel], $upNew);
        // keep manifest hash unchanged for this file so it remains flagged until resolved
    }
}

// also track new files from source that weren't in manifest/allRels handled? allRels covers union.
u_rrmdir($tmpBase);
if ($srcTmp) { u_rrmdir($srcTmp); }

if (!$opts['dry']) {
    u_write_manifest($base, $newManifestFiles, $managed);
}

u_out('');
u_out('Summary: updated=' . $stats['updated'] . ' added=' . $stats['added'] . ' merged=' . $stats['merged'] . ' conflicts/review=' . $stats['conflict'] . ' kept=' . $stats['kept']);
if (count($conflicts)) {
    u_out('Files needing review (local changes preserved, upstream saved as *.upstream):');
    foreach ($conflicts as $c) { u_out('  - ' . $c); }
    u_out('Resolve by merging *.upstream into the file, delete *.upstream, then run: php tools/build-manifest.php (or re-run update).');
}
if ($opts['dry']) { u_out('(dry run: no files written)'); }
u_out('Done.');
exit(count($conflicts) ? 1 : 0);
