#!/usr/bin/env sh
set -eu
cd "$(dirname "$0")"
if ! command -v node >/dev/null 2>&1; then
  echo "Install Node.js 20 or newer, then run this script again."
  exit 1
fi
if [ ! -d node_modules/gulp ]; then npm install; fi
npm run dev
