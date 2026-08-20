<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

function assertSame(mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(sprintf(
            'Expected %s, got %s',
            var_export($expected, true),
            var_export($actual, true),
        ));
    }
}

/**
 * @param class-string<Throwable> $exceptionClass
 */
function expectException(string $exceptionClass, callable $callback): void
{
    try {
        $callback();
    } catch (Throwable $throwable) {
        if ($throwable instanceof $exceptionClass) {
            return;
        }

        throw new RuntimeException(sprintf(
            'Expected exception %s, got %s',
            $exceptionClass,
            $throwable::class,
        ));
    }

    throw new RuntimeException(sprintf('Expected exception %s, none thrown', $exceptionClass));
}

$files = [
    __DIR__ . '/Standalone/OilServiceProjectionCalculatorTest.php',
];

$passed = 0;
$failed = 0;

foreach ($files as $file) {
    $tests = require $file;

    foreach ($tests as $name => $test) {
        try {
            $test();
            $passed++;
            echo "[PASS] {$name}\n";
        } catch (Throwable $throwable) {
            $failed++;
            echo "[FAIL] {$name}: {$throwable->getMessage()}\n";
        }
    }
}

echo "\n{$passed} passed, {$failed} failed\n";

exit($failed === 0 ? 0 : 1);
