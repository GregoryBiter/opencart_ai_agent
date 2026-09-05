<?php
/**
 * Integrity and syntax validation script for OpenCart 3 modules.
 * Usage:
 *   php check_module.php dev-modules/my_module
 */

if ($argc < 2) {
    echo "Usage: php check_module.php <path_to_module_directory>\n";
    exit(1);
}

$moduleDir = rtrim($argv[1], '/\\');
if (!is_dir($moduleDir)) {
    echo "❌ ERROR: Module directory '{$moduleDir}' not found.\n";
    exit(1);
}

$moduleName = basename($moduleDir);
echo "=======================================================\n";
echo " Module Structure Check: {$moduleName}\n";
echo " Directory: {$moduleDir}\n";
echo "=======================================================\n";

$errors = [];
$warnings = [];

// 1. Check upload directory
$uploadDir = $moduleDir . '/upload';
if (!is_dir($uploadDir)) {
    $errors[] = "Missing 'upload/' directory. Module source files must reside in 'upload/'.";
}

// 2. Check Admin Controller
$adminCtrl = $uploadDir . "/admin/controller/extension/module/{$moduleName}.php";
if (!file_exists($adminCtrl)) {
    $warnings[] = "Admin controller not found: upload/admin/controller/extension/module/{$moduleName}.php";
} else {
    $content = file_get_contents($adminCtrl);
    // Check class name (e.g. ControllerExtensionModuleMyModule)
    $pascalName = str_replace('_', '', ucwords($moduleName, '_'));
    $expectedClass = "ControllerExtensionModule{$pascalName}";
    if (!preg_match("/class\s+{$expectedClass}\s+extends\s+Controller/i", $content)) {
        $warnings[] = "Class name in '{$adminCtrl}' might not conform to OpenCart convention ('{$expectedClass}').";
    }
}

// 3. Check language files
$langRu = $uploadDir . "/admin/language/ru-ru/extension/module/{$moduleName}.php";
$langEn = $uploadDir . "/admin/language/en-gb/extension/module/{$moduleName}.php";

if (!file_exists($langEn)) {
    $warnings[] = "Missing English language file: upload/admin/language/en-gb/extension/module/{$moduleName}.php";
}
if (!file_exists($langRu)) {
    $warnings[] = "Missing Russian language file: upload/admin/language/ru-ru/extension/module/{$moduleName}.php";
}

// 4. Check admin Twig template
$adminTwig = $uploadDir . "/admin/view/template/extension/module/{$moduleName}.twig";
if (!file_exists($adminTwig)) {
    $warnings[] = "Missing admin view template: upload/admin/view/template/extension/module/{$moduleName}.twig";
}

// 5. Check PHP syntax across all module PHP files
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($moduleDir));
$phpFiles = [];
foreach ($iterator as $item) {
    if ($item->isFile() && $item->getExtension() === 'php') {
        $phpFiles[] = $item->getPathname();
    }
}

$syntaxErrors = 0;
foreach ($phpFiles as $phpFile) {
    $output = [];
    $returnCode = 0;
    exec("php -l " . escapeshellarg($phpFile) . " 2>&1", $output, $returnCode);
    if ($returnCode !== 0) {
        $errors[] = "PHP syntax error in '{$phpFile}': " . implode(' ', $output);
        $syntaxErrors++;
    }
}

// 6. Check install.xml if present
$installXml = $moduleDir . '/install.xml';
if (file_exists($installXml)) {
    echo "\nFound install.xml. Running OCMOD validator...\n";
    $valScript = __DIR__ . '/../../ocmod/scripts/validate_ocmod.php';
    if (file_exists($valScript)) {
        passthru("php " . escapeshellarg($valScript) . " " . escapeshellarg($installXml), $valReturn);
        if ($valReturn !== 0) {
            $errors[] = "install.xml failed OCMOD validation check.";
        }
    }
}

// 7. Summary output
echo "\n-------------------------------------------------------\n";
echo "Scanned PHP files: " . count($phpFiles) . "\n";

if (!empty($errors)) {
    echo "❌ ERRORS (" . count($errors) . "):\n";
    foreach ($errors as $e) {
        echo "  • {$e}\n";
    }
}

if (!empty($warnings)) {
    echo "⚠️ WARNINGS (" . count($warnings) . "):\n";
    foreach ($warnings as $w) {
        echo "  • {$w}\n";
    }
}

if (empty($errors) && empty($warnings)) {
    echo "✅ Module structure and syntax conform to OpenCart 3 standards!\n";
} elseif (empty($errors)) {
    echo "✅ No critical errors found. Warnings do not block operation.\n";
}

exit(empty($errors) ? 0 : 1);
