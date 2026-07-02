---
date: 2026-07-02
type: problem-record
status: fixed
area: backend-config
tags: [problem, db, apache, vhost]
---

# Problem: DB target had to be edited manually before deploy

> Main hub note — port-based runtime DB selection so one committed file works for both local dev and production vhosts.

## Symptom

Developers risked pointing production traffic to the wrong database when swapping DB names manually before commit/pull between Apache `:80` and `:8080` hosts.

## Cause

→ [[causes/db-name-manual-toggle-risk]]

## Fix

→ [[fixes/db-name-port-based-runtime-selection]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/db.php` |
| Related | [[generated/modules/db]] |

## Deploy

- PHP only + regenerate memory bank

## Links

- **Fragile edges:** [[003_Fragile_Edges#db.php side effect]]
- **Plan (if any):** [[plans/archive/port-based_db_toggle_0b2adb6b]]

## AI rules (optional)

Prefer runtime environment checks over manual pre-commit DB config edits when one codebase serves multiple vhosts on the same server.
