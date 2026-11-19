#!/bin/bash
# Decides whether .github/workflows/deploy.yml deploys $GITHUB_SHA, and whether
# a production deploy waits for approval first. Writes `deploy`,
# `needs-approval` and `move-dev` to $GITHUB_OUTPUT and explains itself in the
# job summary.
#
# MODE is one of:
#   dev            After a push to main. Deploy unless the dev branch has
#                  non-merge commits that this commit doesn't include, put
#                  there by deploy-dev.yml, and the merged PR doesn't have the
#                  deploy-on-merge label.
#   dev-for-prod   Run by hand (deploy-prod.yml), before prod-manual. Wait
#                  for the push of main's tip to pass its tests, then deploy
#                  unless production already includes it.
#   dev-branches   Always deploy. Run by hand (deploy-dev.yml) to deploy
#                  branches that may not be on main yet; waits for approval
#                  if they aren't.
#   prod-on-merge  After a push to main deployed to dev. Deploy only if the
#                  merged PR has the deploy-on-merge label.
#   prod-manual    Run by hand, after dev-for-prod. Deploy everything that
#                  isn't in production yet.
#
# When dev or dev-for-prod replaces commits that aren't on main, `move-dev`
# tells deploy.yml to move the dev branch to this commit.
#
# A production deploy waits for approval when:
#   - sql/migrations changed since the production tag;
#   - the merged PR has the schema-change label (prod-on-merge);
#   - main has other changes that aren't in production yet (prod-on-merge);
#   - there's no production tag yet.

set -euo pipefail

sha=${SHA:-$GITHUB_SHA}
repo=$GITHUB_REPOSITORY

out() { echo "$1=$2" >> "$GITHUB_OUTPUT"; }
say() { echo "$*"; echo "$*" >> "$GITHUB_STEP_SUMMARY"; }
skip() { say "$*"; out deploy false; out needs-approval false; exit 0; }
merged_pr() {
  SHA=$sha gh api "repos/$repo/commits/$sha/pulls" \
    --jq '[.[] | select(.merge_commit_sha == env.SHA)] | first // empty | {number, labels: [.labels[].name]}'
}
wait_for_tests() {
  local id jobs failed
  for _ in $(seq 1 360); do
    id=$(gh api -X GET "repos/$repo/actions/workflows/test-and-deploy.yml/runs" \
      -f head_sha="$sha" -f event=push -f branch=main \
      --jq '.workflow_runs | max_by(.run_number) // empty | .id')
    jobs=
    if [[ -n $id ]]; then
      jobs=$(gh api "repos/$repo/actions/runs/$id/jobs" \
        --jq '.jobs[] | select(.name == "phpunit" or .name == "hurl") | "\(.name) \(.status) \(.conclusion)"')
    fi
    if [[ $(grep -c ' completed success$' <<< "$jobs") == 2 ]]; then
      return
    fi
    if failed=$(grep ' completed ' <<< "$jobs" | grep -v ' success$'); then
      echo "::error::The tests on $sha didn't pass: $failed"
      exit 1
    fi
    echo "Waiting for the tests on $sha..."
    sleep 10
  done
  echo "::error::Timed out waiting for the tests on $sha."
  exit 1
}

case $MODE in
  dev | dev-for-prod)
    if [[ $MODE == dev-for-prod ]]; then
      if git rev-parse -q --verify refs/tags/production > /dev/null &&
        git merge-base --is-ancestor "$sha" production; then
        skip "Production already includes \`$sha\`, so this isn't deploying it to dev first."
      fi
      wait_for_tests
    fi
    parked=
    if git rev-parse -q --verify refs/remotes/origin/dev > /dev/null; then
      parked=$(git log --no-merges --format='- %h %s' "$sha..refs/remotes/origin/dev")
    fi
    if [[ -z $parked ]]; then
      say "Deploying \`$sha\` to dev."
    else
      if [[ $MODE == dev ]]; then
        pr=$(merged_pr)
        if [[ -z $pr ]] || ! jq -e '.labels | index("deploy-on-merge")' <<< "$pr" > /dev/null; then
          say "The dev branch has commits that aren't on main:"
          say ""
          say "$parked"
          say ""
          skip "So this isn't deploying \`$sha\` to dev. Dev gets main again once they're merged, or when Deploy branches to dev deploys main."
        fi
      fi
      say "Deploying \`$sha\` to dev, replacing these commits that aren't on main:"
      say ""
      say "$parked"
      out move-dev true
    fi
    out deploy true
    out needs-approval false
    exit 0
    ;;
  dev-branches)
    out deploy true
    if git merge-base --is-ancestor "$sha" refs/remotes/origin/main; then
      say "Deploying \`$sha\` to dev."
      out needs-approval false
    else
      say "Deploying \`$sha\` to dev. It isn't on main, so it waits for approval."
      out needs-approval true
    fi
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
  pr=$(merged_pr)
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
