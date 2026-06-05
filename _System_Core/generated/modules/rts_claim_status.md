---
title: rts_claim_status
source: includes/rts_claim_status.php
generated: 2026-06-05T06:54:01+02:00
type: module
function_count: 3
---


# rts_claim_status

> Source: `includes/rts_claim_status.php`

[[../130_Module_Index]] · [[../120_Database_Tables]] · [[../100_API_Index]]

## rts_claim_status_label {#rts_claim_status_label}

**Callers (3):**

- `api/case.php`:148 — `'claim_status_label' => rts_claim_status_label($claimStatus),`
- `includes/rts_claim_status.php`:30 — `$out[] = ['value' => $code, 'label' => rts_claim_status_label($code)];`
- `records.php`:745 — `<td class="td-claim-status"><?= htmlspecialchars(rts_claim_status_label($claimStatus), ENT_QUOTES, 'UTF-8') ?></td>`

## rts_claim_status_options {#rts_claim_status_options}

**Callers (1):**

- `records.php`:798 — `<?php foreach (rts_claim_status_options() as $opt): ?>`

## rts_claim_status_valid {#rts_claim_status_valid}

**Callers (2):**

- `api/case.php`:119 — `if (!rts_claim_status_valid($claimStatus)) {`
- `records.php`:681 — `if (!rts_claim_status_valid($claimStatus)) {`

