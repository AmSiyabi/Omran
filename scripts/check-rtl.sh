#!/usr/bin/env bash
# Engineering law #5: physical-direction Tailwind utilities are forbidden.
# Fails (exit 1) if any ml-/mr-/pl-/pr-/left-/right-/text-left/text-right/
# border-l|r/rounded-l|r utility appears in source. Mirrors tests/Unit/RtlComplianceTest.php.
# Note: -P accepts a single pattern only, so all rules are one alternation.
set -u

pattern='(?<![\w.-])(-?(?:ml|mr|pl|pr)-(?:\d|\[|px|auto|full)|-?(?:left|right)-(?:\d|\[|px|auto|full)|text-(?:left|right)(?![\w-])|border-[lr](?:-|(?![\w]))|rounded-(?:l|r|tl|tr|bl|br)(?:-|(?![\w])))'

matches=$(grep -rPn \
    --include='*.php' --include='*.css' --include='*.js' \
    -e "$pattern" \
    resources app)

if [ -n "$matches" ]; then
    echo "❌ Physical-direction Tailwind utilities found (use logical: ms/me, ps/pe, start/end, border-s/e, rounded-s/e):"
    echo "$matches"
    exit 1
fi

echo "✓ RTL check passed — no physical-direction utilities."
