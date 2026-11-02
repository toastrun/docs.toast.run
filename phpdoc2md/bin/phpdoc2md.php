<?php

require __DIR__ . '/../vendor/autoload.php';

use Phpdoc2md\Codes\ThingsFinder;

/*
 * Generate the API reference pages of one version.
 *
 *   phpdoc2md.php <source-dir> <dest-dir> --version=<ref> [options]
 *
 * Options:
 *   --version=<ref>   version label, written to the front matter and used in the
 *                     page titles
 *   --label=<text>    title of the version landing page, defaults to the version
 *   --group=<title>   navigation entry above every version
 *   --order=<n>       sidebar position of the version landing page
 */

$positional = [];
$options = [];
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--')) {
        [$key, $value] = array_pad(explode('=', substr($arg, 2), 2), 2, true);
        $options[$key] = $value;
    } else {
        $positional[] = $arg;
    }
}

if (count($positional) < 2) {
    fwrite(STDERR, "usage: phpdoc2md.php <source-dir> <dest-dir> --version=<ref> [--label=<text>] [--group=<title>]\n");
    exit(1);
}

[$swowDir, $destDir] = $positional;
$version = $options['version'] ?? null;
$group = $options['group'] ?? 'Swow API Reference';
$label = $options['label'] ?? $version;

$finder = new ThingsFinder(
    skipPrivate: false,
    parentPage: $group,
    grandParentPage: null,
    version: $version,
    versionLabel: $label,
    navOrder: isset($options['order']) ? (int) $options['order'] : null,
);

$swowDir = realpath($swowDir);
if (!is_dir($swowDir)) {
    throw new Exception('not a dir');
}
$destDir = rtrim($destDir, '/');

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
    $path = $destDir . DIRECTORY_SEPARATOR . $name;
    @mkdir(dirname($path), recursive: true);
    print("generate $path\n");
    file_put_contents($path, $content);
}
