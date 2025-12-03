<?php

use MoloniOn\Configurations;

define('MOLONI_ON_DIR', __DIR__);
define('ABSPATH', 'DUMMY_VALUE');
define('PLUGIN_VERSION', ltrim(getenv('PLUGIN_VERSION') ?: 'v0.0.01', 'v'));

require_once __DIR__ . '/src/Configurations.php';
require_once __DIR__ . '/src/Exceptions/Core/MoloniException.php';

const INCLUDE_DIRS = [
    'src',
    'languages',
    'images',
    'config',
    'assets',
    'vendor',
];
const INCLUDE_FILES = [
    'readme.txt',
    'readme.md',
    'moloni_dev.php',
    'composer.json',
    'composer.lock',
];

function copyDir($src, $dst, $exclude = [])
{
    $dir = opendir($src);
    @mkdir($dst, 0755, true);
    while (false !== ($file = readdir($dir))) {
        if ($file === '.' || $file === '..') continue;
        if (in_array($file, $exclude)) continue;

        $srcPath = "$src/$file";
        $dstPath = "$dst/$file";

        if (is_dir($srcPath)) {
            copyDir($srcPath, $dstPath, $exclude);
        } else {
            @mkdir(dirname($dstPath), 0755, true);
            copy($srcPath, $dstPath);
        }
    }
    closedir($dir);
}

function buildAndDeploy()
{
    $base = __DIR__;

    $platform = (new Configurations())->getAll();

    $folderName = $platform['folder_name'];
    $zipName = $platform['zip_name'];

    $buildDir = "$base/build/$folderName";

    // Clean old build
    if (is_dir($buildDir)) {
        PHP_OS_FAMILY === 'Windows' ?
            shell_exec("rd /s /q \"$buildDir\"") :
            shell_exec("rm -rf " . escapeshellarg($buildDir));

        echo "✅ Deleted existing build \n";
    }

    // Step 1: Copy allowlisted root files
    @mkdir($buildDir, 0777, true);
    foreach (INCLUDE_FILES as $fileName) {
        $srcPath = "$base/$fileName";
        $dstPath = "$buildDir/$fileName";

        if (!is_file($srcPath)) {
            continue;
        }

        copy($srcPath, $dstPath);
    }

    // Step 2: Copy allowlisted directories
    foreach (INCLUDE_DIRS as $dirName) {
        $srcDir = "$base/$dirName";

        if (!is_dir($srcDir)) {
            continue;
        }

        copyDir($srcDir, "$buildDir/$dirName");
    }

    // Step 2.1: Copy JS development files to assets/js/raw
    $devJsDir = "$base/.dev/js";
    $rawJsDir = "$buildDir/assets/js/raw";

    if (is_dir($devJsDir)) {
        @mkdir($rawJsDir, 0777, true);
        copyDir($devJsDir, $rawJsDir);
        echo "✅ Copied JS development files to assets/js/raw \n";
    }

    // Step 3: Update version placeholders
    // Step 3.1: Update the main file version
    $buildMainPath = "$buildDir/moloni-on.php";

    $buildMain = file_get_contents($buildMainPath);
    $buildMain = str_replace('#VERSION#', PLUGIN_VERSION, $buildMain); // Replace version placeholder

    file_put_contents($buildMainPath, $buildMain);

    // Step 3.2: Update the readme version
    $buildReadmePath = "$buildDir/readme.txt";

    $buildReadme = file_get_contents($buildReadmePath);
    $buildReadme = str_replace('#VERSION#', PLUGIN_VERSION, $buildReadme); // Replace version placeholder

    file_put_contents($buildReadmePath, $buildReadme);

    // Step 4: Zip the build folder
    $zip = new ZipArchive();
    $zipPath = "$base/build/$zipName.zip";
    $zipFolderName = $zipName;

    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($buildDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($buildDir) + 1);

            // Normalize to forward slashes
            $relativePath = str_replace('\\', '/', $relativePath);
            $zipPathInArchive = "$zipFolderName/$relativePath";
            $zipPathInArchive = str_replace('\\', '/', $zipPathInArchive);

            $zip->addFile($filePath, $zipPathInArchive);
        }

        $zip->close();
        echo "✅ Created ZIP: $zipPath\n";
    } else {
        echo "❌ Failed to create ZIP: $zipPath\n";
    }
}

buildAndDeploy();
