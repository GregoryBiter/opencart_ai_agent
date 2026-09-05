<?php
/**
 * Static validation script for OCMOD XML files.
 * Zero external dependencies (no DOM/SimpleXML extension required).
 * Usage:
 *   php validate_ocmod.php path/to/install.xml
 */

if ($argc < 2) {
    echo "Usage: php validate_ocmod.php <path_to_file.xml>\n";
    exit(1);
}

$file = $argv[1];
if (!file_exists($file)) {
    echo "❌ ERROR: File '{$file}' not found.\n";
    exit(1);
}

$content = file_get_contents($file);

$errors = [];
$warnings = [];

// 1. Root tag validation
if (!preg_match('/<modification[\s>]/i', $content)) {
    $errors[] = "Missing opening tag <modification>.";
}
if (!preg_match('/<\/modification>/i', $content)) {
    $errors[] = "Missing closing tag </modification>.";
}

// 2. Metadata tags validation
if (!preg_match('/<code>(.*?)<\/code>/is', $content, $mCode) || trim($mCode[1]) === '') {
    $errors[] = "Missing or empty mandatory <code> tag.";
} else {
    $codeVal = trim($mCode[1]);
    if (strlen($codeVal) < 3) {
        $warnings[] = "Tag <code> is too short ('{$codeVal}'). Recommended format: 'author_modulename'.";
    }
}

if (!preg_match('/<name>(.*?)<\/name>/is', $content)) {
    $warnings[] = "Recommended tag <name> is missing.";
}
if (!preg_match('/<version>(.*?)<\/version>/is', $content)) {
    $warnings[] = "Recommended tag <version> is missing.";
}

// 3. Scan <file> blocks
preg_match_all('/<file\s+([^>]+)>(.*?)<\/file>/is', $content, $fileMatches, PREG_SET_ORDER);

if (empty($fileMatches)) {
    $warnings[] = "No <file>...</file> blocks found in modifier.";
}

foreach ($fileMatches as $fileIdx => $fMatch) {
    $fileAttrStr = $fMatch[1];
    $fileBody = $fMatch[2];

    if (!preg_match('/path=["\']([^"\']+)["\']/i', $fileAttrStr, $mPath)) {
        $errors[] = "<file> block #" . ($fileIdx + 1) . " is missing mandatory attribute path=\"...\".";
        continue;
    }
    $path = $mPath[1];

    // Scan operations
    preg_match_all('/<operation(\s+[^>]*)?>(.*?)<\/operation>/is', $fileBody, $opMatches, PREG_SET_ORDER);

    if (empty($opMatches)) {
        $warnings[] = "[{$path}] Contains no <operation> tags.";
        continue;
    }

    foreach ($opMatches as $opIdx => $opMatch) {
        $opNum = $opIdx + 1;
        $opBody = $opMatch[2];

        // Validate <search>
        if (!preg_match('/<search(\s+[^>]*)?>(.*?)<\/search>/is', $opBody, $sMatch)) {
            $errors[] = "[{$path}] Operation #{$opNum}: missing <search> tag.";
        } else {
            $searchAttrs = $sMatch[1] ?? '';
            $searchRaw = $sMatch[2];

            $isRegex = (bool)preg_match('/regex=["\']true["\']/i', $searchAttrs);
            $hasTrim = (bool)preg_match('/trim=["\']true["\']/i', $searchAttrs);

            // Check CDATA
            if (!preg_match('/<!\[CDATA\[(.*?)\]\]>/s', $searchRaw, $cdataMatch)) {
                $searchContent = $searchRaw;
                if (strpos($searchRaw, '<') !== false || strpos($searchRaw, '>') !== false || strpos($searchRaw, '&') !== false) {
                    $warnings[] = "[{$path}] Operation #{$opNum}: <search> contains XML special characters (<, >, &) without <![CDATA[ ... ]]> encapsulation.";
                }
            } else {
                $searchContent = $cdataMatch[1];
            }

            // Check multi-line constraint
            $cleanSearch = trim($searchContent, "\r\n");
            if (!$isRegex && strpos($cleanSearch, "\n") !== false) {
                $errors[] = "[{$path}] Operation #{$opNum}: <search> contains multi-line string! OpenCart OCMOD searches STRICTLY line-by-line. Shorten to a single line or set regex=\"true\".";
            }

            if (!$hasTrim && !$isRegex) {
                $warnings[] = "[{$path}] Operation #{$opNum}: adding trim=\"true\" to <search> is recommended to prevent indent mismatch.";
            }

            if ($isRegex) {
                $testRegex = '/' . str_replace('/', '\/', $searchContent) . '/';
                if (@preg_match($testRegex, '') === false) {
                    $errors[] = "[{$path}] Operation #{$opNum}: invalid regular expression in <search>: '{$searchContent}'.";
                }
            }
        }

        // Validate <add>
        if (!preg_match('/<add(\s+[^>]*)?>(.*?)<\/add>/is', $opBody, $aMatch)) {
            $errors[] = "[{$path}] Operation #{$opNum}: missing <add> tag.";
        } else {
            $addAttrs = $aMatch[1] ?? '';
            $addRaw = $aMatch[2];

            if (!preg_match('/position=["\']([^"\']+)["\']/i', $addAttrs, $mPos)) {
                $errors[] = "[{$path}] Operation #{$opNum}: <add> missing position=\"...\" attribute.";
            } else {
                $pos = strtolower($mPos[1]);
                if (!in_array($pos, ['before', 'after', 'replace'])) {
                    $errors[] = "[{$path}] Operation #{$opNum}: invalid position=\"{$pos}\". Allowed: before, after, replace.";
                } elseif ($pos === 'replace') {
                    $warnings[] = "[{$path}] Operation #{$opNum}: uses position=\"replace\". High risk of conflict with other modules. Prefer 'before' or 'after'.";
                }
            }

            // Check CDATA in add
            if (!preg_match('/<!\[CDATA\[(.*?)\]\]>/s', $addRaw)) {
                if (strpos($addRaw, '<') !== false || strpos($addRaw, '>') !== false || strpos($addRaw, '$') !== false) {
                    $warnings[] = "[{$path}] Operation #{$opNum}: code in <add> is not enclosed in <![CDATA[ ... ]]>.";
                }
            }
        }
    }
}

// 4. Output report
echo "\n=======================================================\n";
echo " OCMOD Validation Report: " . basename($file) . "\n";
echo "=======================================================\n";

if (!empty($errors)) {
    echo "\n❌ ERRORS (" . count($errors) . "):\n";
    foreach ($errors as $e) {
        echo "  • {$e}\n";
    }
}

if (!empty($warnings)) {
    echo "\n⚠️ WARNINGS (" . count($warnings) . "):\n";
    foreach ($warnings as $w) {
        echo "  • {$w}\n";
    }
}

if (empty($errors) && empty($warnings)) {
    echo "\n✅ Modifier is fully valid and conforms to OpenCart 3 standards!\n\n";
} elseif (empty($errors)) {
    echo "\n✅ No critical errors found. Modifier is ready for installation.\n\n";
} else {
    echo "\n🚫 Modifier contains critical errors and will fail to apply cleanly!\n\n";
}

exit(empty($errors) ? 0 : 1);
