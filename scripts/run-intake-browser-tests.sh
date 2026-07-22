#!/usr/bin/env bash

set -euo pipefail

repo_root=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
playwright_dir=${RMT_PLAYWRIGHT_DIR:-/tmp/rmt-playwright-core}
playwright_version=${RMT_PLAYWRIGHT_VERSION:-1.61.1}

if [[ ! -d "$playwright_dir/node_modules/playwright-core" ]]; then
    mkdir -p "$playwright_dir"
    npm install --prefix "$playwright_dir" --no-save --no-package-lock "playwright-core@$playwright_version"
fi

if [[ -z "${RMT_CHROME_PATH:-}" ]]; then
    if [[ -x "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" ]]; then
        export RMT_CHROME_PATH="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
    elif command -v google-chrome >/dev/null 2>&1; then
        export RMT_CHROME_PATH=$(command -v google-chrome)
    elif command -v chromium >/dev/null 2>&1; then
        export RMT_CHROME_PATH=$(command -v chromium)
    else
        printf '%s\n' 'Set RMT_CHROME_PATH to an installed Chrome or Chromium executable.' >&2
        exit 1
    fi
fi

export NODE_PATH="$playwright_dir/node_modules${NODE_PATH:+:$NODE_PATH}"
node "$repo_root/tests/Browser/intake-no-js.js"