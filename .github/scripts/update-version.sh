#!/bin/bash
set -e

git fetch --tags

latest_tag=$(git describe --tags --abbrev=0 2>/dev/null || echo "v0.0.0")
echo "Laatste tag: $latest_tag"

last_merge_commit=$(git log origin/development --merges --pretty=format:"%H" | while read commit; do
  author=$(git show -s --format='%an' $commit)
  if [[ "$author" != *"[bot]"* ]]; then
    parents=$(git show -s --pretty=format:"%P" $commit)
    feature_commit=$(echo $parents | awk '{print $2}')
    branch_name=$(git name-rev --name-only $feature_commit 2>/dev/null)
    if [[ "$branch_name" == feature/* ]]; then
      echo $commit
      break
    fi
  fi
done)

merged_commit=$(git log -1 --pretty=format:"%P" $last_merge_commit | awk '{print $2}')
commits=$(git log -1 --pretty=format:"%B" $merged_commit)

echo "🎯 Merge commit: $last_merge_commit"
echo "📌 Feature commit: $merged_commit"
echo "📝 Commit message: $commits"

commit_type=$(echo "$commits" | head -n 1 | cut -d':' -f1 | tr '[:upper:]' '[:lower:]' | xargs)
echo "🔍 Commit type: $commit_type"

major=0; minor=0; patch=0

case "$commit_type" in
  feat\!*|breaking\ change*|breaking*|💥*) major=1 ;;
  feature*|feat*) minor=1 ;;
  bugfix*|update*|refactor*|test*|revert*|ci-cd*|ci/cd*|ci_cd*) patch=1 ;;
  style*) minor=1 ;;
  doc*) minor=0 ;;
  *) echo "⚠️ Geen bekende type match voor: \"$commit_type\"" ;;
esac

IFS='.' read -r v_major v_minor v_patch <<< "${latest_tag#v}"

if [[ $major -eq 1 ]]; then
  v_major=$((v_major + 1)); v_minor=0; v_patch=0
elif [[ $minor -eq 1 ]]; then
  v_minor=$((v_minor + 1)); v_patch=0
elif [[ $patch -eq 1 ]]; then
  v_patch=$((v_patch + 1))
else
  echo "No version bump needed"
  echo "bump=no" >> $GITHUB_OUTPUT
  exit 0
fi

new_tag="v${v_major}.${v_minor}.${v_patch}"
echo "new_tag=$new_tag" >> $GITHUB_ENV
echo "bump=yes" >> $GITHUB_ENV