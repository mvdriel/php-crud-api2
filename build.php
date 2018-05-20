<?php

function runDir(String $dir, array &$lines): int
{
    $count = 0;
    $entries = scandir($dir);
    rsort($entries);
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $filename = "$dir/$entry";
        if (is_dir($filename)) {
            $count += runDir($filename, $lines);
        }
    }
    foreach ($entries as $entry) {
        $filename = "$dir/$entry";
        if (is_file($filename)) {
            if (substr($entry, -4) != '.php') {
                continue;
            }
            $data = file_get_contents($filename);
            foreach (explode("\n", $data) as $line) {
                if (!preg_match('/^<\?php|^namespace |^use |spl_autoload_register|^\s*\/\//', $line)) {
                    array_push($lines, $line);
                }
            }
            $count++;
        }
    }
    return $count;
}

function addHeader(array &$lines)
{
    $head = <<<EOF
<?php
/**
 * PHP-CRUD-API                 License: MIT
 * Maurits van der Schee: maurits@vdschee.nl
 * https://github.com/mevdschee/php-crud-api
 **/

namespace Com\Tqdev\CrudApi;
EOF;
    foreach (explode("\n", $head) as $line) {
        array_push($lines, $line);
    }
}

function run(String $dir, String $filename)
{
    $lines = [];
    $start = microtime(true);
    addHeader($lines);
    $count = runDir($dir, $lines);
    file_put_contents($filename . '.tmp', implode("\n", $lines));
    ob_start();
    include $filename . '.tmp';
    ob_end_clean();
    unlink($filename . '.tmp');
    file_put_contents($filename, implode("\n", $lines));
    $end = microtime(true);
    $time = ($end - $start) * 1000;
    echo sprintf("%d files combined in %d ms into '%s'\n", $count, $time, $filename);
}

run(__DIR__ . '/src', 'api.php');
