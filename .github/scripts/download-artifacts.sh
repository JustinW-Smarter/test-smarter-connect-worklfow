#!/bin/bash

echo "🔍 Ophalen artifacts van huidige workflow run..."

# Haal de laatste run-id op
RUN_ID=$(gh run list --limit 1 --json databaseId --jq '.[0].databaseId')

if [ -z "$RUN_ID" ]; then
  echo "❌ Kan de huidige workflow run ID niet ophalen"
  exit 1
fi

echo "📦 Workflow Run ID: $RUN_ID"

# Haal artifact-informatie via de GitHub API
ARTIFACTS=$(gh api -X GET "repos/${GITHUB_REPOSITORY}/actions/runs/$RUN_ID/artifacts" --jq '.artifacts[].name')

if [ -z "$ARTIFACTS" ]; then
  echo "ℹ️ Geen artifacts gevonden."
  exit 0
fi

# Download elke artifact
echo "$ARTIFACTS" | while read -r ARTIFACT_NAME; do
  echo "⬇️ Downloading artifact: $ARTIFACT_NAME"
  gh run download "$RUN_ID" -n "$ARTIFACT_NAME" -D "artifacts/$ARTIFACT_NAME"
done