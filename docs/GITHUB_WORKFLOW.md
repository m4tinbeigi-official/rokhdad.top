# Rokhdad GitHub Workflow

## Repository

- URL: `https://github.com/m4tinbeigi-official/rokhdad.top`
- Default branch: `main`
- Visibility: public

## Commit Rules

- Keep commits task-scoped.
- Use the task ID in commit messages once implementation starts.
- Do not commit `.env`, production secrets, dumps, logs, or generated dependency folders.
- Do not bundle unrelated tasks in one commit.

## Recommended Branch Flow

```text
main
└── task/PX-000-short-title
```

Implementation flow:

```bash
git checkout main
git pull --ff-only origin main
git checkout -b task/PX-000-short-title
# edit files for only that task
git add <files>
git commit -m "PX-000: short task summary"
git push -u origin task/PX-000-short-title
```

## Branch Protection Status

Branch protection is enabled on `main`.

## Future Branch Protection Settings

Enabled settings:

- Require pull request before merge.
- Require one approving review.
- Dismiss stale approvals.
- Require linear history.
- Block force pushes.
- Block branch deletion.
