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

if [ -f package.json ]; then
  if ! command -v npm >/dev/null 2>&1; then
    echo "Node.js and npm are required to rebuild the site assets." >&2
    exit 1
  fi
  npm ci
  npm run build
fi

if [ -f artisan ]; then
  command -v php >/dev/null 2>&1 || { echo "PHP is required." >&2; exit 1; }
  command -v composer >/dev/null 2>&1 || { echo "Composer is required." >&2; exit 1; }
  composer install --no-interaction --prefer-dist
  php artisan optimize:clear
  php artisan migrate --force
  php artisan storage:link --force
  php artisan view:cache
fi

echo "Updated branch: $branch"
