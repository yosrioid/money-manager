#!/usr/bin/env bash

set -euo pipefail

required_documents=(
    "docs/STRICT_RULES.md"
    "docs/AI_WORKFLOW.md"
    "docs/MASTER_PLAN.md"
    "docs/FEATURE_CATALOG.md"
    "docs/PRODUCT_SCOPE.md"
    "docs/ARCHITECTURE.md"
    "docs/CODING_STANDARDS.md"
    "docs/GIT_WORKFLOW.md"
    "docs/PROGRESS.md"
    "docs/RELEASE_PROCESS.md"
    "docs/RELEASE_PROGRESS.md"
    "docs/WORKLOG.md"
)

ai_entrypoints=(
    "AGENTS.md"
    "CLAUDE.md"
    "GEMINI.md"
    ".github/copilot-instructions.md"
    ".github/instructions/project.instructions.md"
    ".cursor/rules/project-governance.mdc"
    ".windsurfrules"
    ".clinerules"
    ".roo/rules/project-governance.md"
    ".continue/rules/project-governance.md"
    ".junie/guidelines.md"
    ".amazonq/rules/project-governance.md"
)

for path in "${required_documents[@]}"; do
    if [[ ! -f "$path" ]]; then
        echo "Missing required governance document: $path" >&2
        exit 1
    fi
done

for path in "${ai_entrypoints[@]}"; do
    if [[ ! -f "$path" ]]; then
        echo "Missing AI instruction entrypoint: $path" >&2
        exit 1
    fi

    if ! grep -qF "docs/AI_WORKFLOW.md" "$path"; then
        echo "AI instruction entrypoint does not reference docs/AI_WORKFLOW.md: $path" >&2
        exit 1
    fi
done

if ! grep -qF "## Entries" docs/WORKLOG.md || ! grep -qF -- "- **Next:**" docs/WORKLOG.md; then
    echo "docs/WORKLOG.md must contain at least one valid handoff checkpoint." >&2
    exit 1
fi

if ! grep -qF "## Mandatory Release Note Format" docs/RELEASE_PROCESS.md \
    || ! grep -qF "## Release Roadmap" docs/RELEASE_PROGRESS.md; then
    echo "Release governance documents must contain the required format and tracker." >&2
    exit 1
fi

catalog_ids="$(
    grep -oE '\| (P[0-9]+-[0-9]+|D-[0-9]+) \|' docs/FEATURE_CATALOG.md \
        | sed -E 's/[| ]//g' \
        | sort
)"

duplicate_ids="$(printf '%s\n' "$catalog_ids" | uniq -d)"

if [[ -n "$duplicate_ids" ]]; then
    echo "Duplicate feature IDs found:" >&2
    printf '%s\n' "$duplicate_ids" >&2
    exit 1
fi

referenced_ids="$(
    grep -RhoE 'P[0-8]-[0-9]{2}' \
        AGENTS.md \
        docs/MASTER_PLAN.md \
        docs/PROGRESS.md \
        .github/pull_request_template.md \
        | sort -u
)"

missing_ids="$(comm -23 <(printf '%s\n' "$referenced_ids") <(printf '%s\n' "$catalog_ids"))"

if [[ -n "$missing_ids" ]]; then
    echo "Referenced feature IDs missing from docs/FEATURE_CATALOG.md:" >&2
    printf '%s\n' "$missing_ids" >&2
    exit 1
fi

echo "Governance checks passed."
