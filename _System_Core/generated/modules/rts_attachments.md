---
title: rts_attachments
source: includes/rts_attachments.php
generated: 2026-06-17T10:42:42+02:00
type: module
function_count: 5
---


# rts_attachments

> Source: `includes/rts_attachments.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## rts_attachment_filename_valid {#rts_attachment_filename_valid}

**Callers (4):**

- `includes/rts_attachments.php`:50 — `if (($kind === 'image' || $kind === 'video') && rts_attachment_filename_valid($file)) {`
- `includes/rts_attachments.php`:58 — `if ($lp !== '' && rts_attachment_filename_valid($lp)) {`
- `includes/rts_attachments.php`:107 — `if (!rts_attachment_filename_valid($file)) {`
- `includes/rts_case_handlers.php`:195 — `if (!rts_attachment_filename_valid($docPhoto)) {`

## rts_damaged_tag {#rts_damaged_tag}

**Callers (2):**

- `includes/rts_case_handlers.php`:63 — `$case['damaged_tag'] = rts_damaged_tag(isset($case['damaged_reason']) ? (string) $case['damaged_reason'] : null);`
- `includes/rts_case_handlers.php`:239 — `'damaged_tag' => rts_damaged_tag($damagedReason === '' ? null : $damagedReason),`

## rts_attachments_decode {#rts_attachments_decode}

**Callers (3):**

- `includes/rts_case_handlers.php`:58 — `$case['documentation_attachments'] = rts_attachments_decode(`
- `includes/rts_case_handlers.php`:511 — `$row['documentation_attachments'] = rts_attachments_decode(`
- `records.php`:676 — `$docAttachments = rts_attachments_decode(`

## rts_attachments_first_image {#rts_attachments_first_image}

**Callers (1):**

- `includes/rts_case_handlers.php`:236 — `'documentation_photo' => rts_attachments_first_image($attachList),`

## rts_attachments_normalize_request {#rts_attachments_normalize_request}

**Callers (1):**

- `includes/rts_case_handlers.php`:189 — `$normAtt = rts_attachments_normalize_request($data['documentation_attachments'] ?? null);`

