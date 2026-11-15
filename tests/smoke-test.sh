#!/usr/bin/env bash
# End-to-end smoke test for Mendly.
#
# Hits every page as a guest, a customer, an expert and an admin, and fails
# if any page returns the wrong status, redirects somewhere unexpected, or
# prints a PHP error/warning/deprecation. Also checks the main access-control
# rules (guests and the wrong role get bounced).
#
# Usage:  tests/smoke-test.sh [base-url]
# Needs a freshly seeded database (database/mendly-schema.sql + mendly-data.sql)
# and the app served at base-url (default http://127.0.0.1:8000).

set -uo pipefail

BASE="${1:-http://127.0.0.1:8000}"
PASSWORD='ChangeMe!2026'   # demo password for every seed account
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

failures=0
checks=0

fail() {
  echo "  FAIL: $*"
  failures=$((failures + 1))
}

# expect <jar> <path> <status> [redirect-substring]
expect() {
  local jar="$1" path="$2" want_status="$3" want_redirect="${4:-}"
  local body="$WORK/body" result status redirect
  checks=$((checks + 1))

  result=$(curl -s -b "$jar" -c "$jar" -o "$body" -w '%{http_code} %{redirect_url}' "$BASE/$path")
  status="${result%% *}"
  redirect="${result#* }"

  if [[ "$status" != "$want_status" ]]; then
    fail "$path -> HTTP $status (expected $want_status)"
    return
  fi
  if [[ -n "$want_redirect" && "$redirect" != *"$want_redirect"* ]]; then
    fail "$path -> redirected to '$redirect' (expected *$want_redirect*)"
    return
  fi
  # PHP diagnostics, with or without html_errors/Xdebug formatting
  if grep -qE '(Fatal error|Parse error|Warning|Deprecated|Notice)(</b>)?: |Uncaught ' "$body"; then
    fail "$path printed a PHP error: $(grep -oE '(Fatal error|Parse error|Warning|Deprecated|Notice)(</b>)?: [^<]{0,120}' "$body" | head -1)"
  fi
}

login() {
  local jar="$1" email="$2"
  curl -s -c "$jar" -b "$jar" -o /dev/null \
    --data-urlencode "email=$email" --data-urlencode "password=$PASSWORD" \
    "$BASE/process-login.php"
}

echo "== Guest"
G="$WORK/guest"
for page in index.php browse-services.php "browse-services.php?category%5B%5D=4" \
            "browse-services.php?expert=2&sort=price_low" experts.php "experts.php?category=leather" \
            "service-details.php?id=1" "expert-review.php?expert_id=1" about.php contact.php \
            login.php create-account.php register-customer.php register-expert.php \
            forgot-password.php "reset-password.php?token=invalid" admin/login.php; do
  expect "$G" "$page" 200
done
for page in my-requests.php wishlist.php review.php my-services.php my-requests-expert.php \
            add-service.php profile.php "submit-request.php?service=1"; do
  expect "$G" "$page" 302 login.php
done
expect "$G" admin/inquiries.php 302 admin/login.php
expect "$G" "service-details.php?id=99999" 302 browse-services.php

echo "== Customer"
C="$WORK/customer"
login "$C" emily@gmail.com
for page in index.php my-requests.php wishlist.php review.php profile.php \
            "submit-request.php?service=3" "service-details.php?id=1"; do
  expect "$C" "$page" 200
done
expect "$C" my-services.php 302 index.php
expect "$C" my-requests-expert.php 302 index.php
checks=$((checks + 1))
curl -s -b "$C" "$BASE/chat-fetch.php?action=list" | grep -q '"conversation_id"' \
  || fail "chat-fetch.php returned no conversations for a customer"

echo "== Expert"
E="$WORK/expert"
login "$E" alex@mendly.com
for page in index.php my-services.php my-requests-expert.php add-service.php \
            "add-service.php?id=1" profile.php "expert-review.php?expert_id=1"; do
  expect "$E" "$page" 200
done
expect "$E" "add-service.php?id=2" 302 my-services.php   # another expert's service
expect "$E" my-requests.php 302 index.php
expect "$E" "submit-request.php?service=2" 302 service-details.php
checks=$((checks + 1))
curl -s -b "$E" -d request_id=1 -d status=Completed "$BASE/update-request-status.php" | grep -q '"error"' \
  || fail "update-request-status.php allowed skipping straight to Completed"

echo "== Admin"
A="$WORK/admin"
curl -s -c "$A" -b "$A" -o /dev/null -d username=admin --data-urlencode "password=$PASSWORD" "$BASE/admin/login.php"
expect "$A" admin/inquiries.php 200
expect "$A" "admin/inquiries.php?type=Question" 200

echo
if (( failures > 0 )); then
  echo "$failures of $checks checks failed."
  exit 1
fi
echo "All $checks checks passed."
