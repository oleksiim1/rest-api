---
name: commit-message
description: Draft a git commit message for the current changes without committing. Use when the user asks for a commit message, wants to describe their changes, or invokes /commit-message.
allowed-tools: Bash(git status:*), Bash(git diff:*), Bash(git log:*), PowerShell(git status:*), PowerShell(git diff:*), PowerShell(git log:*), Read, Grep, Glob
---

# Commit message

Draft a commit message for the current changes and return it for review. **Never** run `git commit`, `git add`, `git stash`, or anything else that changes the repository or index.

## Steps

1. Gather the state (run these in parallel):
   - `git status --short`
   - `git diff --staged --stat` and `git diff --staged`
   - `git diff --stat` and `git diff`
   - `git log -n 10 --format=%s` to match the existing subject style
2. Decide the scope:
   - If anything is staged, describe **only the staged changes**, since that is what a commit would include. Mention in one line below the message that unstaged/untracked changes were left out.
   - If nothing is staged, describe all modified, deleted and untracked files. Read untracked files (or skim them if large) so the message reflects what they actually contain.
3. Understand the change before writing: what it does and why. Group related files into one idea instead of listing every file.

## Message format

- **Subject:** imperative mood ("Add", "Fix", "Configure"), capitalized, no trailing period, at most 72 characters. Match the style of recent commits.
- **Blank line**, then a **body** when the change is more than trivial: short `-` bullets explaining what changed and why, wrapped at 72 characters. Skip the body for a one-line change.
- Don't mention file names unless they help; describe behavior and intent.
- End the message with a blank line and a `Co-Authored-By` trailer naming the Claude model you are running as, e.g. `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.

## Output

Return the message in a single fenced code block so it can be copied as is, followed by at most one short line noting anything excluded or uncertain. Nothing else.
