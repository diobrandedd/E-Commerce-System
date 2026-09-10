---
name: capstone-builder
description: Generates, revises, and validates Pyx Tracker capstone documentation for Chapters I, II, and III using the approved proposal and institutional format. Use when the user explicitly invokes capstone-builder to draft capstone chapters, objectives, scope, literature synthesis, methodology, diagrams, tables, or related academic documentation.
disable-model-invocation: true
---

# Capstone Builder

Create evidence-based capstone documentation for **Pyx Tracker: Web-based Enterprise Operations Management System**.

## Non-negotiable output rule

> When invoked, any final capstone documentation chapters generated must be automatically written and saved directly to the absolute path: "C:\Users\Admin\Desktop\capstone\".

This rule has priority over default workspace output behavior:

1. Ensure `C:\Users\Admin\Desktop\capstone\` exists before finalizing documents.
2. Write every final generated or revised chapter into that folder.
3. Never save a final chapter only in the project, a temporary folder, Downloads, or the chat response.
4. Never claim completion until the file has been written successfully and its exact absolute path has been verified.
5. If access to the folder is denied, report the error and do not substitute another location without the user's explicit approval.
6. Supporting analysis may remain in the conversation, but the complete final chapter must exist as a file in the required folder.

Use these default filenames unless the user requests another name:

- `Chapter_1_Introduction.md`
- `Chapter_2_Related_Literature_and_Studies.md`
- `Chapter_3_Methodology.md`
- `Pyx_Tracker_Capstone_Chapters_1-3.md` for a combined manuscript

If the user requests DOCX, PDF, or another format, create that format in the same required folder. Keep an editable source copy there when practical.

## Required references

Read both files before drafting:

- [format.md](format.md) — institutional chapter structure, writing rules, and quality gate
- [proposal.md](proposal.md) — approved title, problems, functions, features, technologies, users, and rationale

For claims about implemented behavior, inspect the current Pyx Tracker project and its generated code maps. The proposal describes approved intent; the repository describes current implementation.

## Workflow

1. **Identify the deliverable**
   - Determine the requested chapter, section, revision, or combined manuscript.
   - Determine the requested file format; default to Markdown if none is specified.

2. **Gather evidence**
   - Read `format.md` and `proposal.md`.
   - Inspect relevant project files and current documentation for implementation-specific statements.
   - Ask for missing interview, questionnaire, evaluation, deployment, diagram, or testing evidence when it materially affects the requested final content.

3. **Build traceability**
   - Map each documented problem to an objective, approved feature, scope item, module, methodology build, and expected or measured outcome.
   - Resolve contradictions before finalizing.

4. **Draft**
   - Follow the exact chapter structure in `format.md`.
   - Use the approved names and terminology in `proposal.md`.
   - Distinguish proposed, implemented, tested, and deployed behavior.
   - Use `[Evidence required: ...]` when necessary; never invent missing facts.

5. **Validate**
   - Apply the quality gate in `format.md`.
   - Remove unrelated sample-project names and accidental placeholders.
   - Verify citations and reference metadata before presenting them as factual.
   - Confirm headings, tables, figures, and references are consistent and sequential.

6. **Save automatically**
   - Create `C:\Users\Admin\Desktop\capstone\` if it does not exist.
   - Save the complete final document there using the agreed or default filename.
   - Re-open or otherwise verify the saved file exists and contains the expected chapter.

7. **Report concisely**
   - Provide the exact saved path.
   - Summarize any unresolved evidence placeholders or validation warnings.
   - Do not paste the entire chapter into chat unless the user asks.

## DOCX rendering

To convert an editable Markdown manuscript to an academic DOCX, execute:

`python scripts/render_docx.py INPUT.md OUTPUT.docx`

The renderer applies Times New Roman, academic margins, 1.5 line spacing, headings, lists, page numbers, and tables. Keep both input and output under `C:\Users\Admin\Desktop\capstone\`.

For Pyx Tracker Chapter III visuals, execute:

`python scripts/generate_chapter3_diagrams.py C:\Users\Admin\Desktop\capstone\assets`

This produces the Business Model Canvas, evidence-based Gantt chart, Functional Decomposition Diagram, and Use Case Diagram for embedding through Markdown image syntax.

## Evidence hierarchy

Use evidence in this order:

1. User-approved corrections and supplied research evidence
2. `proposal.md` for approved project scope and rationale
3. Current repository code, schema, and generated maps for implementation facts
4. Verified credible external sources for literature and definitions
5. Clearly labeled placeholders when evidence is unavailable

Do not convert assumptions into facts. If two sources conflict, state the conflict and obtain clarification when it would materially change the manuscript.

## Chapter-specific gates

### Chapter I

- Follow the four-part background flow.
- Use one general objective and 3–6 measurable specific objectives.
- Align scope features with actual modules.
- State honest, non-contradictory limitations.
- Trace every IPO output to an input and process.

### Chapter II

- Use only verifiable sources.
- Synthesize literature by topic rather than listing article summaries.
- Include local and foreign evidence where available.
- End each topic with a research gap.
- Include only cited works in the APA reference list.
- Never fabricate an author, year, DOI, URL, finding, or quotation.

### Chapter III

- Use the approved Agile model.
- Tie each build to approved objectives and modules.
- Describe tests as completed only when records support the claim.
- Derive database fields from the actual schema.
- Keep scope, module names, use cases, figures, tables, and data dictionary aligned.

## Completion checklist

- [ ] Read `format.md`
- [ ] Read `proposal.md`
- [ ] Verified implementation claims against project evidence
- [ ] Preserved approved proposal scope
- [ ] Marked missing evidence instead of inventing content
- [ ] Passed chapter-specific quality checks
- [ ] Saved every final chapter under `C:\Users\Admin\Desktop\capstone\`
- [ ] Verified each saved file
- [ ] Reported exact absolute paths to the user
