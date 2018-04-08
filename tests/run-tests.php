<?php
use Com\Tqdev\CrudApi\Config;
use Com\Tqdev\CrudApi\Api;
use Com\Tqdev\CrudApi\Request;
use Com\Tqdev\CrudApi\Database\GenericDB;

spl_autoload_register(function ($class) { 
    include str_replace('\\',DIRECTORY_SEPARATOR,"..\\src\\$class.php"); 
});

function runDir($api,$dir) {
    $dir_handle = opendir($dir);
    $success = 0;
    $total = 0;
    while (($entry = readdir($dir_handle)) !== false) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $file = $dir . DIRECTORY_SEPARATOR . $entry;
        if (is_file($file)) {
            if (substr($entry, -4) != '.log') {
                continue;
            }
            $success += runTest($api,$file);
            $total += 1;
        }
    }
    $failed = $total - $success;
    return compact('total','success','failed');
}

function runTest($api,$file): int {
    $title = ucwords(str_replace('_',' ',substr(basename($file),0,-4)));
    $parts = preg_split('/^[=]+[\r\n]+/m',file_get_contents($file),2);
    $recording = count($parts)!=2;
    $in = $recording?trim($parts[0]):$parts[0];
    $exp = $recording?'':$parts[1];
    $out = $api->handle(Request::fromString($in));
    $success = 1;
    if ($recording) {
        file_put_contents($file, "$in\n===\n$out");
    }
    else if ($out != $exp) {
        $title = "=====[$title]=====";
        $len = strlen($title);
        $line = str_repeat("=",$len);
        echo "\n$title\n$exp\n$line\n$out\n$line\n";
        $success = 0;
    }
    return $success;
}

function loadFixture(Config $config) {
    $driver = $config->getDriver();
    $filename = 'fixtures'.DIRECTORY_SEPARATOR."blog_$driver.sql";
    $file = file_get_contents($filename);
    $db = new GenericDB(
        $config->getDriver(),
        $config->getAddress(),
        $config->getPort(),
        $config->getDatabase(),
        $config->getUsername(),
        $config->getPassword()
    );
    $pdo = $db->pdo();
    $file = preg_replace('/--.*$/m','',$file);
    $statements = preg_split('/(?<=;)\n/s',$file);
    foreach ($statements as $i=>$statement) {
        $statement = trim($statement);
        if ($statement) {
            try {
                $pdo->exec($statement);
            } catch (\PDOException $e) {
                $error = print_r($pdo->errorInfo(),true);
                $statement = var_export($statement,true);
                echo "Loading '$filename' failed on statemement #$i:\n$statement\nwith error:\n$error\n";
                exit(1);
            }
        }
    }
}

function run() {
    $dir = __DIR__;
    $start = microtime(true);
    $ini = parse_ini_file("config.ini");
    $config = new Config($ini);
    loadFixture($config);
    $api = new Api($config);
    $stats = runDir($api,$dir);
    $end = microtime(true);
    $time = ($end-$start)*1000;
    $total = $stats['total'];
    $failed = $stats['failed'];
    echo sprintf("%d tests ran in %d ms, %d failed\n", $total, $time, $failed);
}

run();