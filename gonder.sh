#!/usr/bin/env bash
set -euo pipefail

# Her zaman scriptin bulundugu klasorde calis.
cd "$(dirname "$0")"

BRANCH="${BRANCH:-main}"
COMMIT_MSG="${1:-Guncelleme: $(date '+%Y-%m-%d %H:%M:%S')}"

if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  echo "Hata: Bu klasor bir git deposu degil."
  exit 1
fi

if [[ -n "$(git status --porcelain)" ]]; then
  git add -A
  git commit -m "$COMMIT_MSG"
else
  echo "Degisiklik yok, commit atlanadi."
fi

git pull --rebase origin "$BRANCH"
git push origin "$BRANCH"

echo "Tamam: GitHub gonderimi bitti. Branch: $BRANCH"