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

# Note: with pipefail on, `curl ... | grep -q` can report a failure even when
# grep finds a match (grep exits early and curl gets cut off), so content
# checks save the page into a variable first and then look inside it.

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

# CSRF token for this cookie jar's session, read from a page.
# Pages expose it in <meta name="csrf-token">, admin pages in a hidden field.
token() {
  local jar="$1" page="$2"
  curl -s -b "$jar" -c "$jar" "$BASE/$page" \
    | grep -oE '(name="csrf-token" content|name="csrf_token" value)="[a-f0-9]{64}"' \
    | grep -oE '[a-f0-9]{64}' | head -1
}

login() {
  local jar="$1" email="$2"
  curl -s -c "$jar" -b "$jar" -o /dev/null \
    --data-urlencode "email=$email" --data-urlencode "password=$PASSWORD" \
    --data-urlencode "csrf_token=$(token "$jar" pages/login.php)" \
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
CT=$(token "$C" pages/my-requests.php)
checks=$((checks + 1))
reply=$(curl -s -b "$C" -H "X-CSRF-Token: $CT" -H 'Content-Type: application/json' \
  -d '{"conversation_id":1,"text":"Smoke test message"}' "$BASE/api/chat-send.php")
[[ "$reply" == *'"message_id"'* ]] \
  || fail "api/chat-send.php rejected a JSON message with a valid CSRF token"
checks=$((checks + 1))
reply=$(curl -s -b "$C" -H "X-CSRF-Token: $CT" -d service_id=3 "$BASE/api/toggle-favorite.php")
[[ "$reply" == *'"favorited"'* ]] \
  || fail "api/toggle-favorite.php rejected a request with a valid CSRF token"
checks=$((checks + 1))
page=$(curl -s -b "$C" "$BASE/api/chat-fetch.php?action=list")
[[ "$page" == *'"conversation_id"'* ]] || fail "api/chat-fetch.php returned no conversations for a customer"

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
ET=$(token "$E" pages/my-requests-expert.php)
reply=$(curl -s -b "$E" -H "X-CSRF-Token: $ET" -d request_id=1 -d status=Completed "$BASE/api/update-request-status.php")
[[ "$reply" == *"can't be moved"* ]] \
  || fail "api/update-request-status.php allowed skipping straight to Completed"

echo "== Admin"
A="$WORK/admin"
curl -s -c "$A" -b "$A" -o /dev/null -d username=admin --data-urlencode "password=$PASSWORD" \
  --data-urlencode "csrf_token=$(token "$A" admin/login.php)" "$BASE/admin/login.php"
expect "$A" admin/inquiries.php 200
expect "$A" "admin/inquiries.php?type=Question" 200

echo "== CSRF"
# Requests without this session's token must be refused, even when logged in
checks=$((checks + 1))
code=$(curl -s -o /dev/null -w '%{http_code}' -b "$C" -d service_id=1 "$BASE/api/toggle-favorite.php")
[[ "$code" == "403" ]] || fail "api/toggle-favorite.php accepted a POST without a CSRF token (HTTP $code)"
checks=$((checks + 1))
code=$(curl -s -o /dev/null -w '%{http_code}' -b "$C" -H "X-CSRF-Token: wrong" -d service_id=1 "$BASE/api/toggle-favorite.php")
[[ "$code" == "403" ]] || fail "api/toggle-favorite.php accepted a wrong CSRF token (HTTP $code)"
checks=$((checks + 1))
code=$(curl -s -o /dev/null -w '%{http_code}' -b "$E" -d service_id=10 -d action=delete "$BASE/actions/service-action.php")
[[ "$code" == "403" ]] || fail "actions/service-action.php accepted a form without a CSRF token (HTTP $code)"
checks=$((checks + 1))
code=$(curl -s -o /dev/null -w '%{http_code}' -d email=emily@gmail.com --data-urlencode "password=$PASSWORD" "$BASE/actions/process-login.php")
[[ "$code" == "403" ]] || fail "actions/process-login.php accepted a login without a CSRF token (HTTP $code)"

echo "== Help assistant (no AI key: built-in search + FAQ)"
GT=$(token "$G" index.php)
checks=$((checks + 1))
reply=$(curl -s -b "$G" -c "$G" -H "X-CSRF-Token: $GT" -H 'Content-Type: application/json' \
  -d '{"messages":[{"role":"user","text":"my teddy bear arm is torn"}]}' "$BASE/api/assistant.php")
[[ "$reply" == *'"source":"faq"'* && "$reply" == *'(service:4)'* ]] \
  || fail "api/assistant.php didn't suggest the plushie service: $reply"
checks=$((checks + 1))
code=$(curl -s -o /dev/null -w '%{http_code}' -b "$G" -H 'Content-Type: application/json' \
  -d '{"messages":[{"role":"user","text":"hi"}]}' "$BASE/api/assistant.php")
[[ "$code" == "403" ]] || fail "api/assistant.php accepted a request without a CSRF token (HTTP $code)"

echo "== Expert approval"
# Farah Aziz signed up but hasn't been approved: customers must not see her
F="$WORK/farah"
login "$F" farah@mendly.com
farah_service=$(curl -s -b "$F" "$BASE/pages/my-services.php" | grep -oE 'add-service\.php\?id=[0-9]+' | head -1 | grep -oE '[0-9]+$')
checks=$((checks + 1))
page=$(curl -s -b "$F" "$BASE/pages/my-services.php")
[[ "$page" == *'being reviewed'* ]] || fail "a pending expert doesn't see the 'being reviewed' banner"
expect "$F" "pages/service-details.php?id=$farah_service" 200        # she can preview her own listing
expect "$G" "pages/service-details.php?id=$farah_service" 302 browse-services.php
checks=$((checks + 1))
page=$(curl -s "$BASE/pages/experts.php" "$BASE/pages/browse-services.php")
[[ "$page" == *Farah* || "$page" == *Songket* ]] && fail "a pending expert is visible on the Experts or Browse pages"

# The admin approves her through the review page...
expect "$A" admin/experts.php 200
farah_id=$(curl -s -b "$A" "$BASE/admin/experts.php" | grep -oE 'name="expert_id" value="[0-9]+"' | head -1 | grep -oE '[0-9]+')
curl -s -b "$A" -c "$A" -o /dev/null -d expert_id="$farah_id" -d decision=approve \
  --data-urlencode "csrf_token=$(token "$A" admin/experts.php)" "$BASE/admin/experts.php"
# ...and now customers can see her
checks=$((checks + 1))
page=$(curl -s "$BASE/pages/experts.php")
[[ "$page" == *Farah* ]] || fail "an approved expert doesn't appear on the Experts page"
expect "$G" "pages/service-details.php?id=$farah_service" 200

echo "== Login protection"
L="$WORK/lockout"
for i in 1 2 3 4 5; do
  curl -s -c "$L" -b "$L" -o /dev/null --data-urlencode "email=michael@gmail.com" --data-urlencode "password=wrong$i" \
    --data-urlencode "csrf_token=$(token "$L" pages/login.php)" "$BASE/actions/process-login.php"
done
checks=$((checks + 1))
where=$(curl -s -c "$L" -b "$L" -o /dev/null -w '%{redirect_url}' --data-urlencode "email=michael@gmail.com" \
  --data-urlencode "password=$PASSWORD" --data-urlencode "csrf_token=$(token "$L" pages/login.php)" "$BASE/actions/process-login.php")
[[ "$where" == *"error=locked"* ]] || fail "6th login after 5 wrong passwords wasn't locked (went to $where)"

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
