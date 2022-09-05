<?php

require __DIR__ . '/../vendor/autoload.php';

use Phpdoc2md\Codes\ThingsFinder;

$ver = $argv[3] ?? null;
$parentPage = "Swow API Reference";
if ($ver) {
    $parentPage = "Swow API Reference ($ver)";
}

$finder = new ThingsFinder(false, $parentPage);

$swowDir = realpath($argv[1]);
if (!is_dir($swowDir)) {
    throw new Exception('not a dir');
}
$destDir = rtrim($argv[2], '/');

// main stub
$stubPath = "$swowDir/lib/swow-stub/src/Swow.php";
print("parse stub $stubPath\n");
$input = file_get_contents($stubPath);
$finder->findThings($input);

// libraries code
$finder->skipPrivate = true;
$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$swowDir/lib/swow-library/src"));
foreach ($iter as $fileInfo) {
    /** @var SplFileInfo $fileInfo */
    if (!str_ends_with($fileInfo->getFileName(), '.php')) {
        continue;
    }
    print("parse library source " . $fileInfo->getPathName() . "\n");
    $input = file_get_contents($fileInfo->getPathName());
    $finder->findThings($input);
}

foreach ($finder->dumpAll() as $name => $content) {
    //var_dump($name, $page);
    // if ($name === '') {
    //     $name = 'index';
    // }
    $path = $destDir . DIRECTORY_SEPARATOR . $name;
    @mkdir(dirname($path), recursive: true);
    print("generate $path\n");
    file_put_contents($path, $content);
}
