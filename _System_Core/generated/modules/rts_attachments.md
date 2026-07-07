---
title: rts_attachments
source: includes/rts_attachments.php
generated: 2026-07-07T11:03:41+02:00
type: module
function_count: 6
---


# rts_attachments

> Source: `includes/rts_attachments.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## Includes

- `includes/rts_storage.php`

## rts_attachment_filename_valid {#rts_attachment_filename_valid}

**Callers (4):**

- `includes/rts_attachments.php`:62 — `if (($kind === 'image' || $kind === 'video') && rts_attachment_filename_valid($file)) {`
- `includes/rts_attachments.php`:70 — `if ($lp !== '' && rts_attachment_filename_valid($lp)) {`
- `includes/rts_attachments.php`:119 — `if (!rts_attachment_filename_valid($file)) {`
- `includes/rts_case_handlers.php`:385 — `if (!rts_attachment_filename_valid($docPhoto)) {`

## rts_damaged_tag {#rts_damaged_tag}

**Callers (2):**

- `includes/rts_case_handlers.php`:191 — `$case['damaged_tag'] = rts_damaged_tag(isset($case['damaged_reason']) ? (string) $case['damaged_reason'] : null);`
- `includes/rts_case_handlers.php`:453 — `'damaged_tag' => rts_damaged_tag($damagedReason),`

## rts_attachments_decode {#rts_attachments_decode}

**Callers (4):**

- `includes/rts_case_handlers.php`:186 — `$case['documentation_attachments'] = rts_attachments_decode(`
- `includes/rts_case_handlers.php`:792 — `$row['documentation_attachments'] = rts_attachments_decode(`
- `includes/rts_case_handlers.php`:852 — `$row['documentation_attachments'] = rts_attachments_decode(`
- `records.php`:676 — `$docAttachments = rts_attachments_decode(`

## rts_attachments_first_image {#rts_attachments_first_image}

**Callers (1):**

- `includes/rts_case_handlers.php`:449 — `'documentation_photo' => rts_attachments_first_image($attachList),`

## rts_attachments_normalize_request {#rts_attachments_normalize_request}

**Callers (1):**

- `includes/rts_case_handlers.php`:379 — `$normAtt = rts_attachments_normalize_request($data['documentation_attachments'] ?? null);`

## rts_documentation_save_upload {#rts_documentation_save_upload}

**Callers (2):**

- `api/upload_documentation.php`:53 — `$saved = rts_documentation_save_upload(`
- `includes/rts_case_handlers.php`:1029 — `return rts_documentation_save_upload(`

