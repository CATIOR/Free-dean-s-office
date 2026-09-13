<?php
/**
 * Test for dof_parse_langstring and dof_get_langstring security and functionality.
 */

require_once(__DIR__ . '/../lib/utils.php');

$tempLangDir = sys_get_temp_dir() . '/dof_test_lang_' . uniqid();
mkdir($tempLangDir, 0777, true);

$tempLangFile = $tempLangDir . '/test_lang.php';
$langContent = "<?php\n" .
    "\$string['simple'] = 'Simple translation string';\n" .
    "\$string['scalar_a'] = 'Hello {\$a}!';\n" .
    "\$string['object_a'] = 'User {\$a->name} (id: {\$a->id})';\n" .
    "\$string['array_a'] = 'Item {\$a[\'title\']} costs {\$a[\'price\']}';\n" .
    "\$string['malicious'] = 'Injected string <?php system(\"id\"); ?> {\$a}';\n";

file_put_contents($tempLangFile, $langContent);

$failures = 0;

function assert_equals($expected, $actual, $testName, &$failures) {
    if ($expected === $actual) {
        echo "[PASS] $testName\n";
    } else {
        echo "[FAIL] $testName\n  Expected: " . var_export($expected, true) . "\n  Got:      " . var_export($actual, true) . "\n";
        $failures++;
    }
}

// Test 1: Simple string
assert_equals('Simple translation string', dof_get_langstring($tempLangFile, 'simple'), 'Simple lang string lookup', $failures);

// Test 2: Missing string
assert_equals('[[missing_id]]', dof_get_langstring($tempLangFile, 'missing_id'), 'Missing lang string fallback', $failures);

// Test 3: Scalar variable replacement
assert_equals('Hello World!', dof_get_langstring($tempLangFile, 'scalar_a', 'World'), 'Scalar $a replacement', $failures);

// Test 4: Object property replacement
$user = (object)['name' => 'Alice', 'id' => 42];
assert_equals('User Alice (id: 42)', dof_get_langstring($tempLangFile, 'object_a', $user), 'Object $a replacement', $failures);

// Test 5: Array element replacement
$item = ['title' => 'Book', 'price' => '10$'];
assert_equals('Item Book costs 10$', dof_get_langstring($tempLangFile, 'array_a', $item), 'Array $a replacement', $failures);

// Test 6: Security - Code injection payload in $a
$maliciousA = 'User"; system("id"); //';
assert_equals('Hello User"; system("id"); //!', dof_get_langstring($tempLangFile, 'scalar_a', $maliciousA), 'Security: Code injection in $a not executed', $failures);

// Test 7: Security - Malicious string in lang file
assert_equals('Injected string <?php system("id"); ?> SafeValue', dof_get_langstring($tempLangFile, 'malicious', 'SafeValue'), 'Security: PHP tags in lang file not executed', $failures);

// Cleanup
unlink($tempLangFile);
rmdir($tempLangDir);

if ($failures > 0) {
    echo "\n$failures test(s) failed.\n";
    exit(1);
} else {
    echo "\nAll tests passed successfully.\n";
    exit(0);
}
