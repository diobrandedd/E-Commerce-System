	# AI-Directed Engineering Workflow

> How to use Obsidian + Cursor so **you define theory**, **AI plans**, **AI implements** — without skipping context.

## The three layers

```mermaid
flowchart LR
  theory [Theory - YOU write]
  plan [Plan - AI drafts YOU approve]
  build [Build - AI implements]
  record [Record - AI logs + regenerate]

  theory --> plan
  plan --> build
  build --> record
  record -.-> theory
```

| Layer | Where in Obsidian | Who owns it | Purpose |
|-------|-------------------|-------------|---------|
| **Theory** | [[010_Product_Theory]], `specs/*.md` | **You** | What the system *should* do — outcomes, rules, users, not file paths |
| **Plan** | `plans/active/*.md` | **AI drafts**, you approve | How to build it — files, API actions, DB, risks, test steps |
| **Build** | Repo code + `generated/` | **AI** (after approval) | Implementation grounded in [[000_System_Architecture]] and indexes |
| **Record** | [[002_Memory_Bank]], [[problems/000_Problem_Index]], [[001_System_State]] | **AI** after merge | What shipped, why, blast radius |

**Theory is not code.** Specs describe behavior and constraints. Plans translate specs into repo-specific steps. Generated maps show what *exists today*.

---

## Folder layout

```text
_System_Core/
├── 000_System_Architecture.md   # Technical invariants (existing)
├── 010_Product_Theory.md        # Product/domain theory (you maintain)
├── 006_AI_Directed_Engineering.md  # This file
├── 001_System_State.md          # Pointer to active spec + plan + todos
├── specs/                       # Feature theories (one note per initiative)
│   └── TEMPLATE_Feature_Spec.md
├── plans/
│   ├── active/                  # Current approved or in-progress plan
│   └── archive/                 # Done plans (keep for history)
│       └── TEMPLATE_Implementation_Plan.md
└── generated/                   # Auto maps (do not edit)
```

---

## Step-by-step (your ritual)

### 1. Write theory (you, in Obsidian)

1. Update [[010_Product_Theory]] if the change affects product-wide rules.
2. Duplicate `specs/TEMPLATE_Feature_Spec.md` → `specs/YYYY-MM-DD_short-name.md`.
3. Fill in: problem, users, success criteria, business rules, out of scope.
4. Link the spec from [[001_System_State]] under **Active Spec**.

**Do not** put file paths or SQL in the spec — that belongs in the plan.

### 2. Ask AI to plan (Cursor, Plan mode)

Paste a prompt like:

```text
Read _System_Core/specs/<your-spec>.md, 010_Product_Theory.md, 000_System_Architecture.md,
003_Fragile_Edges.md, and relevant generated/ indexes.

Produce an implementation plan in _System_Core/plans/active/<same-name>_plan.md
using TEMPLATE_Implementation_Plan.md. Do NOT write code yet.
List affected files, API actions, tables, UI routes, and test plan.
```

Review the plan in Obsidian. Edit until correct. Check links to [[003_Fragile_Edges]] and `generated/` impact areas.

### 3. Approve and implement (Cursor, Agent mode)

Update [[001_System_State]]:

- **Status:** `Plan approved — ready to implement`
- Link **Active Plan**

Prompt:

```text
Implement exactly _System_Core/plans/active/<plan>.md.
Follow 004/005 for UI. Run impact.php before editing shared includes/ functions.
Regenerate memory bank when done and append 002_Memory_Bank.
```

### 4. Close the loop

1. Move plan `plans/active/` → `plans/archive/`.
2. Update [[001_System_State]] (clear active spec/plan or set next).
3. Append [[002_Memory_Bank]] with one-line link → [[problems/000_Problem_Index|problem record]].
4. Run `php tools/memory_bank/generate_all.php`.

---

## Quick chat fixes (no spec/plan)

For **small bugs and optimizations** you solve in chat:

1. Describe the issue → AI implements.
2. Say **"save to memory bank"** (or combine: *"fix this and save to memory bank"*).
3. AI creates:
   - `problems/records/YYYY-MM-DD_short-slug.md` — **Problem** hub
   - `problems/causes/...md` — **Cause** node (reusable if same root cause)
   - `problems/fixes/...md` — **Fix** node
   - **Files** table in the record (links to `generated/modules/` where applicable)
   - One-line entry in [[002_Memory_Bank]]
   - [[003_Fragile_Edges]] update if it is a trap future agents will miss

Templates: [[problems/TEMPLATE_Problem_Record]] · [[problems/TEMPLATE_Cause]] · [[problems/TEMPLATE_Fix]]

**Graph view:** open [[problems/000_Problem_Index]] → local graph → see Problem → Cause → Fix → Files chain.

Large features still use spec → plan → implement above.

---

## What to put in Cursor vs Obsidian

| Content | Obsidian | Cursor |
|---------|----------|--------|
| Product vision, domain rules | `010`, `specs/` | Read before plan |
| Active sprint pointer | `001` | Read every session |
| Implementation plan | `plans/active/` | Execute in Agent mode |
| API/DB/caller maps | `generated/` | Read + regenerate |
| Source code | — | Edit in repo |

---

## Obsidian tips

- **Graph view:** link `[[010_Product_Theory]]` → specs → plans → `[[002_Memory_Bank]]` for traceability.
- **Templates:** core Templates plugin → point folder to `_System_Core/specs` and `plans`.
- **Daily note (optional):** quick ideas → promote to `specs/` when ready to build.
- **Status property (optional):** add YAML `status: theory | planning | approved | done` on spec notes for Dataview.

---

## Anti-patterns

- Skipping spec and jumping to code → regressions and UI inconsistency
- Putting implementation detail only in chat → lost when session ends; use `plans/active/`
- Editing `generated/` by hand → run generator instead
- One giant “whole system” doc → use layered notes (010 + per-feature specs)

---

## Quick prompts cheat sheet

| Goal | Prompt |
|------|--------|
| New feature | “Read specs/X.md and draft plan in plans/active/ — no code” |
| Implement | “Execute plans/active/X_plan.md; regenerate memory bank” |
| Change existing | “Run impact.php on fn; read spec; plan delta only” |
| UI feature | “Also read 004 and 005; check generated/spa/151” |

See also: [[README]] · [[000_System_Architecture]]
