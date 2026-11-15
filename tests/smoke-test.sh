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
    "$BASE/actions/process-login.php"
}

echo "== Guest"
G="$WORK/guest"
for page in index.php pages/browse-services.php "pages/browse-services.php?category%5B%5D=4" \
            "pages/browse-services.php?expert=2&sort=price_low" pages/experts.php "pages/experts.php?category=leather" \
            "pages/service-details.php?id=1" "pages/expert-review.php?expert_id=1" pages/about.php pages/contact.php \
            pages/login.php pages/create-account.php pages/register-customer.php pages/register-expert.php \
            pages/forgot-password.php "pages/reset-password.php?token=invalid" admin/login.php; do
  expect "$G" "$page" 200
done
for page in pages/my-requests.php pages/wishlist.php pages/review.php pages/my-services.php pages/my-requests-expert.php \
            pages/add-service.php pages/profile.php "pages/submit-request.php?service=1"; do
  expect "$G" "$page" 302 login.php
done
expect "$G" admin/inquiries.php 302 admin/login.php
expect "$G" "pages/service-details.php?id=99999" 302 browse-services.php

echo "== Customer"
C="$WORK/customer"
login "$C" emily@gmail.com
for page in index.php pages/my-requests.php pages/wishlist.php pages/review.php pages/profile.php \
            "pages/submit-request.php?service=3" "pages/service-details.php?id=1"; do
  expect "$C" "$page" 200
done
expect "$C" pages/my-services.php 302 index.php
expect "$C" pages/my-requests-expert.php 302 index.php
checks=$((checks + 1))
curl -s -b "$C" "$BASE/api/chat-fetch.php?action=list" | grep -q '"conversation_id"' \
  || fail "api/chat-fetch.php returned no conversations for a customer"

echo "== Expert"
E="$WORK/expert"
login "$E" alex@mendly.com
for page in index.php pages/my-services.php pages/my-requests-expert.php pages/add-service.php \
            "pages/add-service.php?id=1" pages/profile.php "pages/expert-review.php?expert_id=1"; do
  expect "$E" "$page" 200
done
expect "$E" "pages/add-service.php?id=2" 302 my-services.php   # another expert's service
expect "$E" pages/my-requests.php 302 index.php
expect "$E" "pages/submit-request.php?service=2" 302 service-details.php
checks=$((checks + 1))
curl -s -b "$E" -d request_id=1 -d status=Completed "$BASE/api/update-request-status.php" | grep -q '"error"' \
  || fail "api/update-request-status.php allowed skipping straight to Completed"

echo "== Admin"
A="$WORK/admin"
curl -s -c "$A" -b "$A" -o /dev/null -d username=admin --data-urlencode "password=$PASSWORD" "$BASE/admin/login.php"
expect "$A" admin/inquiries.php 200
expect "$A" "admin/inquiries.php?type=Question" 200

echo "== Links"
# Every page sets <base href> to the project root, so relative links are
# resolved from there. Collect the links, form actions, scripts, styles and
# images on the main pages (as guest, customer and expert) and make sure none
# of them 404.
collect_links() {
  local jar="$1"; shift
  for page in "$@"; do
    curl -s -b "$jar" "$BASE/$page" \
      | grep -oE '(href|action|src)="[^"#?:]+\.(php|css|js|jpg|jpeg|png)' \
      | sed -E 's/^[a-z]+="//'
  done
}
{
  collect_links "$G" index.php pages/browse-services.php "pages/service-details.php?id=1" pages/experts.php \
    pages/login.php pages/contact.php pages/about.php pages/register-expert.php pages/register-customer.php
  collect_links "$C" index.php pages/my-requests.php pages/wishlist.php pages/profile.php pages/review.php
  collect_links "$E" pages/my-services.php pages/my-requests-expert.php pages/add-service.php
} | sed 's#^/##' | sort -u > "$WORK/links"

while read -r link; do
  checks=$((checks + 1))
  code=$(curl -s -o /dev/null -w '%{http_code}' "$BASE/$link")
  [[ "$code" == "404" ]] && fail "broken link: $link"
done < "$WORK/links"
echo "  checked $(wc -l < "$WORK/links" | tr -d ' ') unique links"

echo
if (( failures > 0 )); then
  echo "$failures of $checks checks failed."
  exit 1
fi
echo "All $checks checks passed."
