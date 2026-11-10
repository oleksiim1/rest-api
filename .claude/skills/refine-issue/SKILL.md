---
name: refine-issue
description: Turn a raw GitHub issue into an implementation-ready plan (goal, scope, context, TDD test plan, acceptance criteria) by rewriting the issue body and labelling it `ready`. Use when the user invokes /refine-issue <number>, asks to "refine", "plan" or "groom" an issue, or wants an issue prepared for /implement-issue.
argument-hint: <issue-number>
allowed-tools: Bash(gh issue view:*), Bash(gh label list:*), Bash(git log:*), Bash(git status:*), PowerShell(gh issue view:*), PowerShell(gh label list:*), PowerShell(git log:*), PowerShell(git status:*), Read, Grep, Glob
---

# Refine issue

Rewrite a raw GitHub issue into a fixed template so `/implement-issue` can work from it. The issue number comes from the arguments: `$ARGUMENTS`. If it is missing, ask for it.

The output is a plan, not code. Do not create branches, edit files or write code in this skill.

## Steps

1. **Read the issue.** Run `gh issue view <N> --json number,title,body,labels,comments,state`. If the issue is closed, stop and say so.
   - If the body already uses the template below (it has a `## Test plan` section), this is a **re-refine**: keep the existing `<details><summary>Original issue</summary>` block as is, and treat the current body plus any newer comments as the input.
   - Otherwise the whole current body is the raw text to keep.
2. **Investigate the codebase** so the plan matches what exists, not what you imagine. Follow `CLAUDE.md`: find the bounded context under `src/<Context>/` and its layers (`Domain`, `Application`, `Infrastructure`, `Presentation`), the matching tests under `tests/`, routes, `config/services.yaml`, Doctrine XML mappings, `compose.yaml` / Docker files, and `.github/workflows/` when relevant. Note related open issues if the issue mentions them.
3. **Resolve ambiguity with the user, never by guessing.** Collect everything that would change the plan: unclear behaviour, missing validation rules, error responses, naming, data model choices, scope edges. Ask them in one go (prefer AskUserQuestion with concrete options and a recommended one). Only questions whose answers genuinely change what gets built belong here. Pick sensible defaults for the rest and state them under **Scope**.
   - If the user wants to leave something open, put it under **Open questions**. The issue then does not get the `ready` label.
4. **Write the plan** using the template below.
   - **Type** is one of `feat`, `fix`, `chore`, `docs`, `refactor`. It becomes the branch prefix.
   - **Slug** is a short kebab-case description (2 to 5 words) used in the branch name, e.g. `product-price-validation`.
   - **Test plan** items are concrete behaviours, ordered for TDD from the inside out: Domain first (pure unit tests), then Application (in-memory fakes), then Infrastructure (`KernelTestCase`), then Presentation (`WebTestCase` with status codes and response bodies). Each item must be small enough for one red/green/refactor cycle and specific enough to write the test from, e.g. "Presentation: `POST /api/products` with a negative price returns 422 with a `price` violation". Skip layers the change does not touch. For non-code work (CI, Docker, docs), list verifiable checks instead of tests.
   - **Acceptance criteria** describe the outcome from the outside: what someone can observe when the issue is done.
   - Keep it proportional. A one-line fix gets a two-item plan. If the plan clearly needs more than one PR, say so and propose how to split it into separate issues instead of writing one huge plan.
5. **Update the issue.** Write the new body to a temporary file in the scratchpad directory (not the repo), then run `gh issue edit <N> --body-file <file>`. Also update the title with `--title` if the raw title is vague; keep it short and imperative.
6. **Label it.** If **Open questions** is empty, add the `ready` label: `gh issue edit <N> --add-label ready`. If the label does not exist yet, create it first with `gh label create ready --color 0e8a16 --description "Refined and ready for /implement-issue"`. If there are open questions, remove the label instead (`--remove-label ready`) when present.
7. **Report back** in a few lines: the issue link, whether it is `ready`, the number of test-plan items, and any defaults you chose that the user may want to revisit.

## Template

Use exactly these headings, because `/implement-issue` parses them.

```markdown
**Type:** feat
**Slug:** product-price-validation

## Goal
One or two sentences: what changes and why.

## Scope
- In: …
- Out: …
- Assumptions: defaults chosen during refinement

## Context
- Bounded context and layers affected
- Existing files and classes to change or follow as examples (repo-relative paths)
- Related issues or PRs

## Test plan
- [ ] Domain: …
- [ ] Application: …
- [ ] Presentation: …

## Acceptance criteria
- [ ] …

## Open questions
None.

<details><summary>Original issue</summary>

…the raw issue text, unchanged…

</details>
```

## Rules

- Never drop or rewrite the original issue text; it always ends up in the `<details>` block.
- Do not invent requirements. Anything not stated by the issue, the user or the existing code is either asked about or listed as an assumption.
- Respect the project conventions in `CLAUDE.md` and memory (for example: Doctrine mappings stay XML, Docker images use exact tags).
