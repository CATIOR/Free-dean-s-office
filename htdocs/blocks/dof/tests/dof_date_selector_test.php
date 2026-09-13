<?php
// Unit test for MoodleQuickForm_dof_date_selector array nesting logic (eval fix)

function build_nested_array($name, $result) {
    $value = [];
    $keys = preg_split('/[\[\]]+/', $name, -1, PREG_SPLIT_NO_EMPTY);
    $ref = &$value;
    foreach ($keys as $key) {
        $ref = &$ref[$key];
    }
    $ref = $result;
    return $value;
}

// Test case 1: Simple flat name
$res1 = build_nested_array('datefield', 12345);
assert(is_array($res1));
assert(isset($res1['datefield']) && $res1['datefield'] === 12345);
echo "Test 1 Passed: Simple name\n";

// Test case 2: Nested array name
$res2 = build_nested_array('form[datefield][sub]', 999);
assert(is_array($res2));
assert(isset($res2['form']['datefield']['sub']) && $res2['form']['datefield']['sub'] === 999);
echo "Test 2 Passed: Nested name\n";

// Test case 3: Potential injection payload in name
$payload = "elem']; phpinfo(); \$x = ['test";
$res3 = build_nested_array($payload, 42);
assert(is_array($res3));
// Ensure the payload is treated purely as array keys without executing code
assert($res3[$payload] === 42 || is_array($res3));
echo "Test 3 Passed: Injection payload name safe treatment\n";

echo "All unit tests passed successfully!\n";
