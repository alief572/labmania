"""Pemeriksaan HTTP UAT lokal tanpa browser; password opsional lewat environment privat."""

import http.cookiejar
import json
import os
import re
import urllib.error
import urllib.parse
import urllib.request


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


base = "http://localhost:8080"
cookies = http.cookiejar.CookieJar()
client = urllib.request.build_opener(NoRedirect, urllib.request.HTTPCookieProcessor(cookies))


def request(path, data=None, headers=None):
    req = urllib.request.Request(base + path, data=data, headers=headers or {})
    try:
        response = client.open(req, timeout=15)
    except urllib.error.HTTPError as error:
        response = error
    with response:
        return response.status, response.headers, response.read().decode("utf-8", errors="replace")


def page(body):
    match = re.search(r'<script data-page="app" type="application/json">(.*?)</script>', body, re.S)
    assert match, "Inertia data-page tidak ditemukan"
    return json.loads(match.group(1))


assert request("/up")[0] == 200, "/up gagal"
status, headers, body = request("/login")
assert status == 200 and page(body)["component"] == "Auth/Login", "Login bukan Laravel Inertia"
assert headers.get("X-Content-Type-Options") == "nosniff"
assert any(c.name == "labmania_local_uat_session" for c in cookies), "Cookie UAT tidak tersedia"
assets = set(re.findall(r'(?:src|href)="(http://localhost:8080)?(/build/assets/[^\"]+)"', body))
assert assets, "Asset hasil build tidak ditemukan"
for _, asset in assets:
    assert request(asset)[0] == 200, asset
for path in ["/", "/program", "/permissions", "/manage-peserta"]:
    status, headers, _ = request(path)
    assert status == 302 and headers.get("Location", "").endswith("/login"), path
for path in ["/.env", "/.git/config", "/composer.json", "/composer.lock", "/artisan",
             "/app/Support/LocalUat.php", "/storage/logs/laravel.log", "/local/.state.json",
             "/database/schema/rehearsal.mysql.sql", "/vendor/autoload.php"]:
    assert request(path)[0] in (403, 404), path
assert request("/login", b"username=x&password=x")[0] == 419, "POST tanpa CSRF diterima"
print("LULUS: health, Inertia login, aset, cookie, autentikasi, CSRF, dan proteksi source.")

password = os.environ.get("UAT_HTTP_PASSWORD")
if password:
    request("/login")
    csrf = next(urllib.parse.unquote(c.value) for c in cookies if c.name == "XSRF-TOKEN")
    form = urllib.parse.urlencode({"username": os.environ.get("UAT_HTTP_USERNAME", "admin"), "password": password}).encode()
    status, headers, _ = request("/login", form, {"X-XSRF-TOKEN": csrf, "Referer": base + "/login"})
    destination = urllib.parse.urlparse(headers.get("Location", ""))
    assert status == 302 and destination.netloc == urllib.parse.urlparse(base).netloc and destination.path not in ("/login", "/two-factor"), "Login UAT tidak langsung masuk"
    status, _, body = request("/")
    assert status == 200 and page(body)["component"] == "Dashboard/Index", "Dashboard setelah login gagal"
    csrf = next(urllib.parse.unquote(c.value) for c in cookies if c.name == "XSRF-TOKEN")
    assert request("/logout", b"", {"X-XSRF-TOKEN": csrf, "Referer": base + "/"})[0] == 302
    assert request("/")[0] == 302, "Logout tidak menutup akses"
    print("LULUS: password akun UAT langsung membuka Dashboard, logout menutup akses.")
