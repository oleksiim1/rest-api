---
name: pr-description
description: Write a pull request description (What / Why / Changes) for the current branch. Use whenever the user is opening or creating a PR, asks for a PR description, title or summary, wants to "summarize this branch" or "describe what changed for review", or invokes /pr-description, even if they don't say "description" explicitly.
allowed-tools: Bash(git status:*), Bash(git diff:*), Bash(git log:*), Bash(git merge-base:*), Bash(git rev-parse:*), Bash(git branch:*), Bash(git symbolic-ref:*), PowerShell(git status:*), PowerShell(git diff:*), PowerShell(git log:*), PowerShell(git merge-base:*), PowerShell(git rev-parse:*), PowerShell(git branch:*), PowerShell(git symbolic-ref:*), Read, Grep, Glob
---

# PR description

Write a description of everything this branch adds compared to its base branch, so a reviewer understands the change before reading the diff.

## Steps

1. **Find the base branch.** This repo branches from `master`, not `main`. Use the branch the user names if they name one; otherwise use `master`, falling back to `main` or the remote's default (`git symbolic-ref refs/remotes/origin/HEAD`) if `master` doesn't exist. Use the three-dot form (`<base>...HEAD`) so only this branch's own changes are compared, not what landed on the base since branching.
2. **Gather the state** (in parallel):
   - `git log --format="%h %s%n%b" <base>..HEAD`: the commits tell you the intent and story of the branch
   - `git diff --stat <base>...HEAD` and `git diff --name-status <base>...HEAD`: the shape of the change, including deletions (`D`) and renames (`R`)
   - `git diff <base>...HEAD`: the actual change. For a large diff, read the stat first and then the diff of the files that matter rather than skimming everything equally
   - `git status --short`: uncommitted work isn't part of the PR
3. **Understand before writing.** Work out what the branch achieves and why it was needed. The "why" usually comes from commit messages, the branch name (`<type>/<ticket-id>-<description>`, so include the ticket id if there is one other than `000`), or what the code is clearly enabling. If the reason truly can't be inferred, write your best reading and flag it below the description rather than inventing a motivation.

## Format

```markdown
## What
One sentence explaining what this PR does.

## Why
Brief context on why this change is needed: the problem, need or ticket behind it. Two or three sentences at most.

## Changes
- Specific changes, grouped by area or idea rather than listed file by file
- Mention any files deleted or renamed explicitly
- Call out anything a reviewer should look at closely: migrations, config or env changes, new dependencies, breaking API changes
```

Guidance:
- Describe behavior and intent; name files only when it helps (deleted/renamed files, config, migrations).
- Keep it proportional: a three-line fix gets two or three bullets, not a dozen.
- If the user asks for a title too, make it imperative and under 72 characters, like a commit subject.

## Output

- If the user only asked for a description, return it in one fenced `markdown` block so it can be pasted as is, followed by at most one short line noting anything uncertain or excluded (such as uncommitted changes).
- If the user asked to create the PR, use the description as the body of `gh pr create --base <base>` (pass it via a file or heredoc so the markdown survives the shell). Pushing and creating a PR are outward-facing, so do it only when the user asked for it.
