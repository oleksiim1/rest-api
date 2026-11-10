---
name: implement-issue
description: Implement a GitHub issue that was refined with /refine-issue, test-first, on its own branch, ending with a pull request that closes it. Use when the user invokes /implement-issue <number> or asks to "implement", "build" or "work on" a specific issue number.
argument-hint: <issue-number>
---

# Implement issue

Implement the plan written into a GitHub issue by `/refine-issue`. The issue number comes from the arguments: `$ARGUMENTS`. If it is missing, ask for it.

## 1. Check the issue is ready

Run `gh issue view <N> --json number,title,body,labels,state,url`.

Stop and tell the user to run `/refine-issue <N>` first if any of these hold:
- the issue is closed,
- it does not have the `ready` label,
- the body has no `## Test plan` section, or `## Open questions` contains anything other than "None.".

From the body, read **Type**, **Slug**, **Goal**, **Scope**, **Context**, **Test plan** and **Acceptance criteria**. Test-plan items that are already checked were finished in an earlier session; continue from the first unchecked one.

## 2. Prepare the branch

1. Run `git status --short`. If the working tree is dirty, stop and ask the user what to do with the changes. Never stash or discard them yourself.
2. The branch name is `<Type>/<N>-<Slug>`, e.g. `feat/12-product-price-validation`.
   - If that branch already exists (locally or on `origin`), check it out and continue on it.
   - Otherwise run `git checkout master`, `git pull --ff-only`, then `git checkout -b <branch>`.
3. Never commit to `master`.

## 3. Check the plan still fits

Read the files listed under **Context** and the area around them. If `master` has changed so that the plan no longer fits (files moved, the behaviour already exists, a conflicting design landed), stop and explain the mismatch. Suggest re-running `/refine-issue <N>`. Do not silently improvise a different plan.

## 4. Work through the test plan (TDD)

For each unchecked test-plan item, in order:

1. **Red.** Write the test in the matching place under `tests/` (mirroring `src/`). Run it with `vendor/bin/phpunit <test file>` (or `--filter`) and confirm it fails for the expected reason, not because of a typo or missing import.
2. **Green.** Write the minimum code to make it pass, in the right layer. Follow `CLAUDE.md`: Domain has no Symfony or Doctrine imports, controllers stay thin and delegate to Application, repository interfaces live in Domain and are bound in `config/services.yaml`, Doctrine mappings are XML.
3. **Refactor** while the tests stay green.
4. **Commit** the item: imperative subject under 72 characters, matching recent `git log` style, with a short body when useful.
5. **Tick it off in the issue.** Fetch the current body, change that item's `- [ ]` to `- [x]`, write it to a temporary file in the scratchpad directory and run `gh issue edit <N> --body-file <file>`. To limit noise, it is fine to tick several items at once after a layer is finished, but always before stopping.

For non-code items (CI, Docker, docs), make the change and run the check the item describes instead of a PHPUnit test.

**If the plan turns out to be wrong** (a test shows the design should differ, an item is missing, an item is unnecessary): make the smallest sensible adjustment, update the test plan in the issue body to match what you actually did, and note the deviation for the PR description. If the change would alter scope or behaviour the user cares about, stop and ask first.

## 5. Verify

Run all of these and fix any failure before going on:
- `vendor/bin/phpunit` (full suite)
- `php bin/console lint:container`
- `php bin/console lint:yaml config`

If a command cannot run locally (for example the database is needed), run it in the container with `docker compose exec php …`. If it still cannot run, say so plainly rather than claiming it passed.

Then go through **Acceptance criteria** and check each one is met. Tick those in the issue the same way.

## 6. Hand over

Summarize for the user: the branch, the commits, the test results, any deviations from the plan, and anything left undone.

Pushing and opening a PR are outward-facing, so ask before doing them unless the user already asked for a PR. When approved:
1. `git push -u origin <branch>`
2. Write the description with the `pr-description` skill's format (What / Why / Changes), add a line `Closes #<N>`, mention any plan deviations, and create the PR with `gh pr create --base master --title "<imperative title>" --body-file <file>`.
3. Return the PR link.
