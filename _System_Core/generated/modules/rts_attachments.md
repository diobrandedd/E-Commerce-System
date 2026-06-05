---
title: rts_attachments
source: includes/rts_attachments.php
generated: 2026-06-05T04:27:32+02:00
type: module
function_count: 5
---


# rts_attachments

> Source: `includes/rts_attachments.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## rts_attachment_filename_valid {#rts_attachment_filename_valid}

**Callers (4):**

- `api/case.php`:435 — `if (!rts_attachment_filename_valid($docPhoto)) {`
- `includes/rts_attachments.php`:50 — `if (($kind === 'image' || $kind === 'video') && rts_attachment_filename_valid($file)) {`
- `includes/rts_attachments.php`:58 — `if ($lp !== '' && rts_attachment_filename_valid($lp)) {`
- `includes/rts_attachments.php`:107 — `if (!rts_attachment_filename_valid($file)) {`

## rts_damaged_tag {#rts_damaged_tag}

**Callers (3):**

- `api/case.php`:67 — `$case['damaged_tag'] = rts_damaged_tag(isset($case['damaged_reason']) ? (string) $case['damaged_reason'] : null);`
- `api/case.php`:297 — `$damagedTag = rts_damaged_tag($damagedDb);`
- `api/case.php`:489 — `$damagedTag = rts_damaged_tag($damagedDb);`

## rts_attachments_decode {#rts_attachments_decode}

**Callers (2):**

- `api/case.php`:62 — `$case['documentation_attachments'] = rts_attachments_decode(`
- `records.php`:670 — `$docAttachments = rts_attachments_decode(`

## rts_attachments_first_image {#rts_attachments_first_image}

**Callers (2):**

- `api/case.php`:261 — `$docPhotoCol = rts_attachments_first_image($attachList);`
- `api/case.php`:454 — `$docPhotoCol = rts_attachments_first_image($attachList);`

## rts_attachments_normalize_request {#rts_attachments_normalize_request}

**Callers (2):**

- `api/case.php`:243 — `$normAtt = rts_attachments_normalize_request($data['documentation_attachments'] ?? null);`
- `api/case.php`:427 — `$normAtt = rts_attachments_normalize_request($data['documentation_attachments'] ?? null);`

