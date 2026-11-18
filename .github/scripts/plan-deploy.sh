#!/bin/bash
# Decides whether .github/workflows/deploy.yml deploys $GITHUB_SHA, and whether
# a production deploy waits for approval first. Writes `deploy` and
# `needs-approval` to $GITHUB_OUTPUT and explains itself in the job summary.
#
# MODE is one of:
#   dev            Always deploy.
#   prod-on-merge  After a push to main deployed to dev. Deploy only if the
#                  merged PR has the deploy-on-merge label.
#   prod-manual    Run by hand. Wait for main's tip to deploy to dev, then
#                  deploy everything that isn't in production yet.
#
# A production deploy waits for approval when:
#   - sql/migrations changed since the production tag;
#   - the merged PR has the schema-change label (prod-on-merge);
#   - main has other changes that aren't in production yet (prod-on-merge);
#   - there's no production tag yet.

set -euo pipefail

sha=$GITHUB_SHA
repo=$GITHUB_REPOSITORY

out() { echo "$1=$2" >> "$GITHUB_OUTPUT"; }
say() { echo "$*"; echo "$*" >> "$GITHUB_STEP_SUMMARY"; }
skip() { say "$*"; out deploy false; out needs-approval false; exit 0; }

case $MODE in
  dev)
    say "Deploying \`$sha\` to dev."
    out deploy true
    out needs-approval false
    exit 0
    ;;
  prod-on-merge | prod-manual) ;;
  *)
    echo "::error::Unknown MODE: $MODE"
    exit 1
    ;;
esac

reasons=()

if [[ $MODE == prod-on-merge ]]; then
  pr=$(SHA=$sha gh api "repos/$repo/commits/$sha/pulls" \
    --jq '[.[] | select(.merge_commit_sha == env.SHA)] | first // empty | {number, labels: [.labels[].name]}')
  if [[ -z $pr ]]; then
    skip "No merged pull request has \`$sha\` as its merge commit, so this isn't deploying to production."
  fi
  number=$(jq -r .number <<< "$pr")
  if ! jq -e '.labels | index("deploy-on-merge")' <<< "$pr" > /dev/null; then
    skip "PR #$number doesn't have the deploy-on-merge label, so this isn't deploying to production."
  fi
  if jq -e '.labels | index("schema-change")' <<< "$pr" > /dev/null; then
    reasons+=("PR #$number has the schema-change label.")
  fi
else
  # Wait up to 30 minutes for main's tip to deploy to dev.
  check="deploy-dev / deploy"
  run=
  for _ in $(seq 1 180); do
    run=$(gh api -X GET "repos/$repo/commits/$sha/check-runs" -f check_name="$check" \
      --jq '.check_runs | max_by(.started_at) // empty | "\(.status) \(.conclusion)"')
    case $run in
      "completed success") break ;;
      completed*)
        echo "::error::The $check check on $sha didn't pass ($run)."
        exit 1
        ;;
    esac
    echo "Waiting for the $check check on $sha (${run:-not started yet})..."
    sleep 10
  done
  if [[ $run != "completed success" ]]; then
    echo "::error::Timed out waiting for the $check check on $sha."
    exit 1
  fi
fi

say "## Production deploy of \`$sha\`"

if git rev-parse -q --verify refs/tags/production > /dev/null; then
  if git merge-base --is-ancestor "$sha" production; then
    skip "Production already includes \`$sha\`. To redeploy it anyway, run dgf/deploy-prod.sh."
  fi
  say ""
  say "Changes on main since the last production deploy:"
  say ""
  say "$(git log --first-parent --format='- %h %s' "production..$sha")"
  count=$(git rev-list --first-parent --count "production..$sha")
  if [[ $MODE == prod-on-merge && $count -gt 1 ]]; then
    reasons+=("main has $((count - 1)) other change(s) that aren't in production yet.")
  fi
  if ! git diff --quiet production "$sha" -- sql/migrations; then
    reasons+=("sql/migrations changed since the last production deploy. The deploy will run the new migrations.")
  fi
else
  reasons+=("There's no production tag yet, so this can't tell what the deploy includes.")
fi

say ""
if ((${#reasons[@]})); then
  say "This deploy waits for approval because:"
  say ""
  for reason in "${reasons[@]}"; do
    say "- $reason"
  done
  out needs-approval true
else
  say "Deploying without approval."
  out needs-approval false
fi
out deploy true
