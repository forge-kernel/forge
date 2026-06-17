<?php

define("BASE_PATH", __DIR__);
const FRAMEWORK_REPO_URL = 'https://github.com/forge-kernel/kernel-registry';
const FRAMEWORK_FORGE_JSON_PATH_IN_REPO = 'forge.json';
const FRAMEWORK_REPO_BRANCH = 'main';
const FRAMEWORK_MODULE_REGISTRY = BASE_PATH . '/kernel/Core/Module/module_registry.php';
const LOCAL_FORGE_JSON_PATH = BASE_PATH . '/forge.json';

$specifiedVersion = null;
for ($i = 1; $i < count($argv); $i++) {
    if (strpos($argv[$i], '--version=') === 0) {
        $specifiedVersion = substr($argv[$i], strlen('--version='));
        break;
    } elseif ($argv[$i] === '--version') {
        if (isset($argv[$i + 1])) {
            $specifiedVersion = $argv[$i + 1];
            break;
        } else {
            echo "Error: --version option requires a version number.\n";
            displayHelp();
            exit(1);
        }
    } elseif ($argv[$i] === 'latest' || is_numeric(str_replace('.', '', $argv[$i]))) {
        $specifiedVersion = $argv[$i];
        break;
    }
}

// Read the local forge.json file or create a new one if it doesn't exist
if (!file_exists(LOCAL_FORGE_JSON_PATH)) {
    echo "forge.json file not found. Creating a new one...\n";
    $localForgeData = [
        "name" => "Forge Kernel",
        "kernel" => [
            "name" => "forge-kernel",
            "version" => "latest"
        ],
        "modules" => []
    ];
    $versionToInstall = 'latest';
} else {
    $localForgeJson = file_get_contents(LOCAL_FORGE_JSON_PATH);
    $localForgeData = json_decode($localForgeJson, true);

    if (!$localForgeData || !is_array($localForgeData)) {
        die("Error decoding local forge.json.\n");
    }

    // Check for the kernel entry
    $engineEntry = $localForgeData['kernel'] ?? null;
    if ($engineEntry) {
        $versionToInstall = $engineEntry['version'] ?? null;
        if (!$versionToInstall) {
            die("Error: 'version' not defined in 'kernel' entry of forge.json.\n");
        }
        echo "Installing framework version from forge.json: {$versionToInstall}\n";
    } else {
        // If no kernel entry is found, add it after the name entry
        $localForgeData['kernel'] = [
            'name' => 'forge-kernel',
            'version' => 'latest'
        ];
        $versionToInstall = 'latest';
        echo "No 'kernel' entry found in forge.json. Adding default entry and installing latest version.\n";

        // Save the updated forge.json file
        if (!file_put_contents(LOCAL_FORGE_JSON_PATH, json_encode($localForgeData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
            die("Error saving updated forge.json file.\n");
        }
    }
}

// Override the version to install if specified via command line
if ($specifiedVersion) {
    $versionToInstall = $specifiedVersion;
    echo "Installing specified framework version: {$versionToInstall}\n";
}

$frameworkForgeJsonUrl = generateRawGithubUrl(FRAMEWORK_REPO_URL, FRAMEWORK_REPO_BRANCH, FRAMEWORK_FORGE_JSON_PATH_IN_REPO);
echo "Fetching framework manifest from: " . $frameworkForgeJsonUrl . "\n";

$frameworkManifestJson = @file_get_contents($frameworkForgeJsonUrl);
if (!$frameworkManifestJson) {
    die("Error fetching framework manifest from GitHub. URL: " . $frameworkForgeJsonUrl . "\n");
}
$frameworkManifest = json_decode($frameworkManifestJson, true);
if (!$frameworkManifest || !is_array($frameworkManifest)) {
    die("Error decoding framework manifest JSON from GitHub.\n");
}

if ($versionToInstall === 'latest') {
    $versionToInstall = $frameworkManifest['versions']['latest'] ?? null;
    echo "Installing latest framework version: {$versionToInstall}\n";
    if (!$versionToInstall) {
        die("Error: 'latest' version not defined in framework manifest.\n");
    }
} else {
    echo "Installing specified framework version: {$versionToInstall}\n";
    if (!isset($frameworkManifest['versions'][$versionToInstall])) {
        die("Error: Specified version '{$versionToInstall}' not found in framework manifest.\n");
    }
}

$versionDetails = $frameworkManifest['versions'][$versionToInstall];
if (!$versionDetails) {
    die("Version details for '{$versionToInstall}' not found in framework manifest.\n");
}

$downloadZipRelativePath = $versionDetails['download_url'];
$downloadUrl = generateRawGithubUrl(FRAMEWORK_REPO_URL, FRAMEWORK_REPO_BRANCH, $downloadZipRelativePath);
$integrityHash = $versionDetails['integrity'];

echo "Downloading Forge Framework version {$versionToInstall} from: " . $downloadUrl . "\n";
$zipFilePath = downloadFile($downloadUrl, 'forge-framework.zip');
if (!$zipFilePath) {
    die("Error downloading framework ZIP file.\n");
}

echo "Verifying integrity...\n";
if (!verifyFileIntegrity($zipFilePath, $integrityHash)) {
    unlink($zipFilePath);
    die("Integrity check failed! Downloaded file is corrupted or tampered.\n");
}

echo "Preparing kernel folder and extracting framework files...\n";
$extractionPath = './kernel';

if (is_dir($extractionPath)) {
    echo "Deleting existing kernel folder...\n";
    if (!recursiveDeleteDirectory($extractionPath)) {
        die("Error deleting existing kernel folder: " . $extractionPath . "\n");
    }
}
echo "Creating kernel folder: " . $extractionPath . "\n";
if (!mkdir($extractionPath, 0755, true)) {
    die("Error creating kernel folder: " . $extractionPath . "\n");
}

if (!extractZip($zipFilePath, $extractionPath)) {
    unlink($zipFilePath);
    recursiveDeleteDirectory($extractionPath);
    die("Error extracting framework files to kernel folder.\n");
}

unlink($zipFilePath);
unlink(FRAMEWORK_MODULE_REGISTRY);

// Update the forge.json file with the installed version
if ($specifiedVersion === 'latest') {
    $localForgeData['kernel']['version'] = 'latest';
} else {
    $localForgeData['kernel']['version'] = $versionToInstall;
}
if (!file_put_contents(LOCAL_FORGE_JSON_PATH, json_encode($localForgeData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
    die("Error saving updated forge.json file.\n");
}

echo "\nForge Framework version {$versionToInstall} installed successfully inside the 'kernel' folder!\n";
echo "You can now use 'php forge.php' to manage your project and modules.\n";
echo "Run 'php forge.php list' to see available commands.\n";

/**
 * Generates the raw GitHub URL for a file in a repository.
 *
 * @param string $repoUrl GitHub repository URL (e.g., https://github.com/user/repo)
 * @param string $branch Branch name (e.g., main)
 * @param string $filePathInRepo Path to the file within the repository (e.g., forge.json)
 * @return string Raw GitHub URL
 */
function generateRawGithubUrl(string $repoUrl, string $branch, string $filePathInRepo): string
{
    $repoBaseRawUrl = rtrim(str_replace('github.com', 'raw.githubusercontent.com', $repoUrl), '/');
    return $repoBaseRawUrl . '/' . $branch . '/' . $filePathInRepo;
}

/**
 * Downloads a file from a URL to a destination path.
 *
 * @param string $url URL of the file to download.
 * @param string $destinationPath Path to save the downloaded file.
 * @return string|bool Path to the downloaded file on success, false on failure.
 */
function downloadFile(string $url, string $destinationPath): string|bool
{
    $fileContent = @file_get_contents($url);
    if ($fileContent === false) {
        return false;
    }
    if (file_put_contents($destinationPath, $fileContent) !== false) {
        return $destinationPath;
    }
    return false;
}

/**
 * Verifies the SHA256 integrity of a file.
 *
 * @param string $filePath Path to the file to verify.
 * @param string $expectedHash Expected SHA256 hash.
 * @return bool True if integrity is verified, false otherwise.
 */
function verifyFileIntegrity(string $filePath, string $expectedHash): bool
{
    if (!file_exists($filePath)) {
        return false;
    }
    $calculatedHash = hash_file('sha256', $filePath);
    return $calculatedHash === $expectedHash;
}

/**
 * Extracts a ZIP archive to a destination directory.
 *
 * @param string $zipPath Path to the ZIP archive.
 * @param string $destinationPath Path to extract the contents to.
 * @return bool True on successful extraction, false otherwise.
 */
function extractZip(string $zipPath, string $destinationPath): bool
{
    $zip = new \ZipArchive();
    if ($zip->open($zipPath) === true) {
        if (!is_dir($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }
        $zip->extractTo($destinationPath);
        $zip->close();
        return true;
    } else {
        return false;
    }
}

/**
 * Recursively deletes a directory and its contents.
 *
 * @param string $dirPath Path to the directory to delete.
 * @return bool True on success, false on failure.
 */
function recursiveDeleteDirectory(string $dirPath): bool
{
    if (!is_dir($dirPath)) {
        return false;
    }

    $files = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($dirPath, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $fileinfo) {
        if ($fileinfo->isDir()) {
            if (!rmdir($fileinfo->getRealPath())) {
                return false;
            }
        } else {
            if (!unlink($fileinfo->getRealPath())) {
                return false;
            }
        }
    }
    return rmdir($dirPath);
}

function displayHelp(): void
{
    echo "Forge Framework Installer (install.php)\n\n";
    echo "Usage: php install.php [options]\n\n";
    echo "Options:\n";
    echo "  --version=<version>   Specify the framework version to install (e.g., --version=0.1.0).\n";
    echo "  --version <version>   Specify the framework version to install (e.g., --version 0.1.0).\n";
    echo "  latest                Install the latest framework version.\n";
    echo "  help                  Displays this help message.\n";
    echo "\nInstalls the latest framework version if no options are provided.\n";
}
