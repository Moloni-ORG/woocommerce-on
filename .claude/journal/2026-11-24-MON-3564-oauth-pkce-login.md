# OAuth public-client login: redirect URI and callback guard

**Symptom:** the first try at the OAuth (PKCE) connect redirected with `Context::getAdminUrl()`; the
authorization server rejected it as an unregistered `redirect_uri` on later logins.

**Cause:** `getAdminUrl()` appends a `_wpnonce` that rotates per user and every 12–24h, while a
public client's `redirect_uri` must match the registered one byte-for-byte.

**Rule / what changed:**
- The OAuth `redirect_uri` is `admin_url('admin.php?page=' . Context::getPageName())`, no nonce
  (`Start::getRedirectUri()`), used identically at register, authorize and grant.
- A row with an empty `client_secret` is a public client; a row with a secret is a merchant from
  the old Developer ID / Client secret login and keeps the old grant/refresh request unchanged.
- The client id lives only in the auth table. Every connect calls `/auth/register` again: the
  server returns the existing client for an identical (client_name, redirect_uris) pair, but only
  scans the 50 newest clients with that name. So the name carries the store host
  (`Moloni ON WooCommerce (<host>)`); a name shared by every store would stop matching and pile up
  clients.
- `?code=` / `?error=` are only handled while the row has no `refresh_token`. Without that guard, a
  stray link shows a connected store the login form, and clicking Connect truncates the old row.
- `.mo` is rebuilt with Python `babel.messages.mofile` (`read_mo` → edit → `write_mo`); `msgfmt`
  isn't installed and the file's `Generated-By: Babel` header shows that's how it was built.

**Not verified end to end:** a fresh connect with a real Moloni ON login + consent, and the HTTPS
message on a plain-http store (only via stubbed harness).
