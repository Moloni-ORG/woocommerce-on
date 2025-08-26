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

function loadPlatforms(): array
{
    $base = __DIR__;
    $directoryPath = "$base/.platforms";

    $folders = [];
    $platforms = [];

    $items = scandir($directoryPath) ?: [];

    foreach ($items as $item) {
        if ($item != '.' && $item != '..') {
            $folders[] = $item;
        }
    }

    foreach ($folders as $folder) {
        $platforms[] = (new Configurations(['PLATFORM' => $folder, 'IS_DEV' => true]))->getAll();
    }

    return $platforms;
}

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

function processPlatform($platform)
{
    $base = __DIR__;

    $folderName = $platform['folder_name'];
    $zipName = $platform['zip_name'];
    $mainFileName = $platform['main_file_name'];

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

    // Step 3: Copy platform-specific overrides from .platforms/{platform}/ to build
    $platformOverride = "$base/.platforms/$folderName";
    if (is_dir($platformOverride)) {
        // Step 3.1: Copy platform-specific assets and configurations
        copyDir("$platformOverride/config", "$buildDir/config"); // Copy configs
        copyDir("$platformOverride/images", "$buildDir/images"); // Copy images
        copyDir("$platformOverride/css", "$buildDir/assets/css"); // Copy CSS

        // Step 3.2: Replace the main file header
        $platformMainPath = "$platformOverride/main.php";
        $buildMainPath = "$buildDir/moloni_dev.php";

        $platformMain = file_get_contents($platformMainPath);
        $buildMain = file_get_contents($buildMainPath);

        $fileCommentRegex = '/\/\*.*?\*\//s';

        $updatedBuildMain = preg_replace($fileCommentRegex, $platformMain, $buildMain);
        $updatedBuildMain = str_replace('#VERSION#', PLUGIN_VERSION, $updatedBuildMain); // Replace version placeholder

        file_put_contents($buildMainPath, $updatedBuildMain);

        // Step 3.3: Replace the readme file header
        $platformReadmePath = "$platformOverride/readme.txt";
        $buildReadmePath = "$buildDir/readme.txt";

        $platformReadme = file_get_contents($platformReadmePath);
        $buildReadme = file_get_contents($buildReadmePath);

        $combinedReadme = $platformReadme . "\n" . $buildReadme;// Combine the contents
        $combinedReadme = str_replace('#VERSION#', PLUGIN_VERSION, $combinedReadme); // Replace version placeholder

        file_put_contents($buildReadmePath, $combinedReadme);

        // Step 3.4: Rename the main plugin file
        $oldMainFile = "$buildDir/moloni_dev.php";
        $newMainFile = "$buildDir/$mainFileName.php";

        rename($oldMainFile, $newMainFile);
    }

    // Step 4: Replace the platform reference in the configuration file
    $configurationsFile = "$buildDir/src/Configurations.php";
    $content = file_get_contents($configurationsFile);
    $content = str_replace("parse_ini_file(MOLONI_ON_DIR . '/.env')", "['PLATFORM' => '$folderName']", $content);
    file_put_contents($configurationsFile, $content);

    // Step 5: Zip the build folder
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

$platforms = loadPlatforms();

foreach ($platforms as $platform) {
    processPlatform($platform);
}
