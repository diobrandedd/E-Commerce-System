---
date: 2026-06-02
type: problem-record
status: fixed
area: pdf-to-excel
tags: [problem, tracking]
---

# Problem: PDF tracking extraction false positives on OCR pages

## Symptom

Noisy `P…` OCR strings chosen over real Lazada numeric tracking → downstream no-match.

## Cause

→ [[causes/pdf-tracking-ocr-false-positive-p-ids]]

## Fix

→ [[fixes/priority-tracking-patterns-and-validation]]

## Files

| Role | Path / note |
|------|-------------|
| Extractor | `pdf-to-excel/extractor.py` |
| Module | [[generated/modules/pdf_to_excel]] |
