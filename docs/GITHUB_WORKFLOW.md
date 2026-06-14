# Rokhdad GitHub Workflow

## Repository

- URL: `https://github.com/m4tinbeigi-official/rokhdad.top`
- Default branch: `main`
- Visibility: private

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

Automatic branch protection could not be enabled while the repository is private on the current GitHub plan. GitHub returned HTTP 403 with a plan limitation message.

Until branch protection is available:

- Treat `main` as protected by convention.
- Prefer task branches and pull requests.
- Do not force-push `main`.
- Do not commit directly to `main` except planning/bootstrap commits approved by the owner.

## Future Branch Protection Settings

When available, enable:

- Require pull request before merge.
- Require one approving review.
- Dismiss stale approvals.
- Require linear history.
- Block force pushes.
- Block branch deletion.

