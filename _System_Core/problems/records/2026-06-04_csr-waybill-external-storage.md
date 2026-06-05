---
date: 2026-06-04
type: problem-record
status: fixed
area: infrastructure
tags: [problem, storage, enhancement]
---

# Problem: CSR waybill files stored inside git tree

## Symptom

Stash PDFs/Excel filled `rts/storage/` in repo.

## Cause

→ [[causes/csr-tool-files-in-git-tree]]

## Fix

→ [[fixes/external-rts-storage-for-csr-tools]]

## Files

| Role | Path / note |
|------|-------------|
| Storage | `includes/rts_storage.php` |
| Consumers | [[generated/modules/waybill_stamper]], [[generated/modules/pdf_to_excel]], `online_orders_generator.php` |
