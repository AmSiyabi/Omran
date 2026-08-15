<?php

/**
 * Engineering law #5: physical-direction Tailwind utilities are forbidden.
 * Logical properties only (ms-/me-, ps-/pe-, start-/end-, text-start/end,
 * border-s/e, rounded-s/e). This test scans every Blade, CSS, JS and PHP
 * source file and fails on any violation.
 */
$forbiddenPatterns = [
    'physical margin/padding (use ms-/me-/ps-/pe-)' => '/(?<![\w.-])-?(?:ml|mr|pl|pr)-(?:\d|\[|px|auto|full)/u',
    'physical inset (use start-/end-)' => '/(?<![\w.-])-?(?:left|right)-(?:\d|\[|px|auto|full)/u',
    'text-left|text-right (use text-start/end)' => '/(?<![\w.-])text-(?:left|right)(?![\w-])/u',
    'border-l|border-r (use border-s/e)' => '/(?<![\w.-])border-[lr](?:-|(?![\w]))/u',
    'rounded-l|rounded-r (use rounded-s/e)' => '/(?<![\w.-])rounded-(?:l|r|tl|tr|bl|br)(?:-|(?![\w]))/u',
];

$projectRoot = dirname(__DIR__, 2);

$scannedRoots = [
    $projectRoot.'/resources/views',
    $projectRoot.'/resources/css',
    $projectRoot.'/resources/js',
    $projectRoot.'/app',
];

test('no physical-direction Tailwind utility appears in any source file', function () use ($forbiddenPatterns, $scannedRoots, $projectRoot) {
    $violations = [];

    foreach ($scannedRoots as $root) {
        if (! is_dir($root)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! in_array($file->getExtension(), ['php', 'css', 'js'], true)) {
                continue;
            }

            $lines = file($file->getPathname());

            foreach ($lines as $number => $line) {
                foreach ($forbiddenPatterns as $label => $pattern) {
                    if (preg_match($pattern, $line)) {
                        $violations[] = sprintf(
                            '%s:%d — %s — %s',
                            str_replace($projectRoot.DIRECTORY_SEPARATOR, '', $file->getPathname()),
                            $number + 1,
                            $label,
                            trim($line),
                        );
                    }
                }
            }
        }
    }

    expect($violations)->toBeEmpty(
        "Physical-direction utilities found:\n".implode("\n", $violations)
    );
});
