#!/usr/bin/env sh
set -eu
cd "$(dirname "$0")"

if ! command -v git >/dev/null 2>&1; then
  echo "Git is required to update this project." >&2
  exit 1
fi

branch=$(git branch --show-current)
if [ -z "$branch" ]; then
  echo "Could not detect the current Git branch." >&2
  exit 1
fi

if [ -n "$(git status --porcelain)" ]; then
  echo "Working tree has local changes. Commit or stash them before updating." >&2
  exit 1
fi

git fetch origin "$branch"
git merge --ff-only "origin/$branch"

if [ -f package.json ] && command -v npm >/dev/null 2>&1; then
  npm run build
fi

echo "Updated branch: $branch"
