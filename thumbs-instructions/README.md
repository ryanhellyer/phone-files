# Porting `public/files` thumbnail resizing to another site

You are an LLM assistant. Your job is to add **on-demand image thumbnail
generation** to a target website, replicating the mechanism used by the source
site (a Laravel 12 application). Read this whole file before editing anything.

Everything you need is in this folder:

- `README.md` — this file (the task and theory).
- `SNIPPETS.md` — exact code to paste into files that already exist in the
  target project (routes, composer.json, service provider, view/JS usage).
- `code/` — complete files to copy verbatim into the target project. The folder
  structure mirrors the target path, e.g. `code/config/glide.php` goes to
  `config/glide.php`.

---

## 1. What the feature does

Original (full-size) images live in the web-served directory `public/files/`,
for example `public/files/2026/01/photo.avif`. Pages request **suffixed**
variants whose filename encodes the desired size:

```
/files/2026/01/photo.avif            <- original, served directly by the web server
/files/2026/01/photo-380x300.avif    <- generated on demand, cropped to 380x300
/files/2026/01/photo-96x96.avif      <- generated on demand, cropped to 96x96
```

The naming convention is:

```
<original-name>-<width>x<height>.<extension>
```

- `<width>` is required and must be > 0.
- `<height>` of `0` means "keep aspect ratio" (only width is passed to the
  resizer). The site's deploy smoke test uses `-400x0.avif` for exactly this.
- The extension is the original file's extension (`.avif`, `.jpg`, `.png`,
  `.webp`).

How a request is served:

1. If the requested file physically exists (all originals do), the web server
   serves it directly and PHP is never involved.
2. If it does not exist (all generated variants), the request falls through to
   the front controller (`public/index.php`).
3. A catch-all Laravel route (`files/{path}`) matches the path, parses the
   `-WxH` suffix, and asks **Glide** (`league/glide`) to resize/crop the original
   on the fly. The result is written to a file cache under
   `storage/app/glide/` and streamed back with long-lived cache headers.
4. Subsequent requests for the same variant are served from the Glide file
   cache (the generated file also physically exists in the cache, not in
   `public/files`).

The important design property: **originals are never duplicated**. Only
generated variants are cached, and only the ones actually requested.

---

## 2. Requirements

- PHP 8.2+ (the source site requires `^8.2`).
- Composer.
- The **GD** PHP extension with AVIF support (or Imagick built with an AVIF
  **encoder** — see the gotcha in §8). Verify with `php -m | grep -i gd` and
  `php -r 'var_dump(function_exists("imageavif"));'`.
- A web server configured so that missing files fall through to `index.php`
  (standard Laravel front-controller setup). This is what makes the whole thing
  work without a route for every variant:
  - **nginx** (typical): `try_files $uri $uri/ /index.php?$query_string;`
  - **Apache**: the stock Laravel `public/.htaccess` does this already.

The target must be a Laravel application (or at least a PHP app you can add the
Composer packages and routing to). If the target is not Laravel, see §9.

---

## 3. Dependencies

Add these to `composer.json` `require` and run `composer update`:

```json
"intervention/image": "^3.11",
"league/glide": "^3.2"
```

`league/glide` v3 is built on `intervention/image` v3. Both must be present.
(The source site's `composer.json` also uses these exact constraints.)

Run:

```bash
composer require league/glide:^3.2 intervention/image:^3.11
```

---

## 4. Files to add

Copy each file from `code/` into the target project at the **same relative
path**. The files are self-contained; the only class-name/namespace coupling is
that all three PHP classes live under the target app's `App\` namespace
(PSR-4), which the source assumes.

| Source file in this folder | Target path |
| --- | --- |
| `code/config/glide.php` | `config/glide.php` |
| `code/app/helpers.php` | `app/helpers.php` (or merge the function into the target's existing helpers file) |
| `code/app/Http/Controllers/ImageController.php` | `app/Http/Controllers/ImageController.php` |
| `code/app/Services/GlideService.php` | `app/Services/GlideService.php` |
| `code/app/Services/GlideLaravelResponseFactory.php` | `app/Services/GlideLaravelResponseFactory.php` |
| `code/public/files/delete-thumbs.sh` | `public/files/delete-thumbs.sh` *(optional maintenance script)* |
| `code/public/files/delete.sh` | `public/files/delete.sh` *(optional maintenance script)* |

If the target's `app/AppServiceProvider.php` differs from the source's, **do not
overwrite it** — instead apply the registration snippet from `SNIPPETS.md`.
`code/app/Providers/AppServiceProvider.php` is included only as a reference for
how the singleton is wired.

### 4.1 `thumbnail_url()` helper and autoloading

`app/helpers.php` defines a single global function:

```php
function thumbnail_url(string $url, int $width, int $height): string
{
    return preg_replace('/\.(avif|jpg|jpeg|png|webp)$/i', '-' . $width . 'x' . $height . '.$1', $url);
}
```

For the function to be autoloaded it must be registered in `composer.json`:

```json
"autoload": {
    "files": [
        "app/helpers.php"
    ],
    ...
}
```

If the target already registers a helpers file, either add the function to that
file or add `app/helpers.php` to the `files` array alongside it. Then run
`composer dump-autoload`.

---

## 5. Wiring

Apply the snippets in `SNIPPETS.md`:

1. **Route** — register the catch-all image route. It must be placed **before**
   the site's own catch-all `{any}` route (Laravel matches routes in order).
2. **Service provider** — register `GlideService` as a singleton (optional but
   recommended; it avoids rebuilding the Glide server on every request).
3. **Storage** — ensure `storage/app/glide` exists and is writable. The service
   creates it on first use, but the web/PHP user must be able to write there.
4. **Ignore generated cache** — ensure the Glide cache directory is gitignored,
   and (if the target serves `public/` from git) make sure generated variants
   are never committed. In the source repo `storage/` is already ignored.
5. **Views** — use `thumbnail_url($path, $w, $h)` in templates instead of
   hardcoding variants (see §6).

---

## 6. Using it in views / templates

Given a full-size featured image path, call the helper with the display size:

```blade
<img
    src="{{ thumbnail_url($post['featuredImage'], 380, 300) }}"
    srcset="{{ thumbnail_url($post['featuredImage'], 380, 300) }} 380w,
            {{ thumbnail_url($post['featuredImage'], 480, 379) }} 480w,
            {{ thumbnail_url($post['featuredImage'], 686, 541) }} 686w">
```

The helper only rewrites the URL; the image is produced lazily on first request.

### Client-side variant construction

The source also builds variants in JavaScript (`resources/js/search.js`) for
search results, using the same convention:

```js
const thumbnail = item.thumbnail.match(/^(.*)\.([a-z0-9]+)$/i);
image.src = thumbnail
    ? `${thumbnail[1]}-380x300.${thumbnail[2]}`
    : `${item.thumbnail}-380x300.avif`;
```

Any front-end code that knows the original path can construct variants the same
way — no server round trip is needed to learn the URL.

---

## 7. Verifying the port

1. Place a real image at `public/files/test/photo.avif` (or reuse an existing
   original).
2. Request a generated variant directly and check it is a real, non-empty image:

   ```bash
   curl -fsS -o /tmp/thumb.avif -w '%{http_code} %{size_download}\n' \
     'http://localhost/files/test/photo-400x0.avif'
   file /tmp/thumb.avif   # should report AVIF, and size > 0
   ```

3. Request the same URL again; the response should come from cache (same body,
   `Cache-Control: max-age=31536000, public`).
4. Request a nonsense size/variant for a missing original
   (`/files/test/missing-10x10.avif`); it must return the site's 404, not a
   PHP error or a 0-byte image.
5. Confirm originals are still served directly (request the original URL; PHP
   should not be hit — check the access log if unsure).

The source project automates step 2 in `deploy.sh` (the "image smoke test"),
which fails the deploy if a generated variant returns 0 bytes.

---

## 8. Gotchas (read before debugging)

- **Always use `gd` unless you have verified Imagick can WRITE AVIF.** An
  Imagick build without an AVIF encoder (shown as `AVIF r--` in
  `convert -list format`) silently produces **0-byte** images. `config/glide.php`
  deliberately defaults to GD for this reason. The `GLIDE_DRIVER` env var can
  override it.
- **Empty-image detection.** `ImageController::serveImage()` checks that the
  cached file exists and is larger than 0 bytes, deletes and logs empty
  results, and returns 404. Keep this guard — it converts the silent Imagick
  failure into a visible error instead of serving a broken image.
- **`max_image_size`.** `config/glide.php` caps generated images at
  `2000 * 2000` pixels to prevent resource-exhaustion requests. Raise it only if
  the target genuinely needs larger output.
- **`fit=crop`.** The controller requests cropped output so 380x300 etc. are
  exact display crops. Height `0` omits the `h` parameter, so Glide scales by
  width and preserves aspect ratio.
- **Cache extension.** `GlideService` calls
  `setCacheWithFileExtensions(true)`, so cached files keep their extension. This
  keeps `Content-Type` correct.
- **Route ordering.** `files/{path}` (`where('path', '.*')`) must be registered
  before any other `{any}` catch-all route, otherwise the wrong handler wins.
- **Trailing-slash middleware.** Do **not** apply trailing-slash redirects to
  the image route; generated filenames end in `.avif`, not `/`.
- **Quality.** The default Glide quality is `q = 50` (`defaults` in
  `GlideService`), matching the source. Adjust if the target wants higher
  fidelity (at the cost of bytes).
- **Deletion / regeneration.** Generated variants may be committed by accident.
  The two maintenance scripts under `public/files/` find and delete files
  matching `-<digits>x<digits>.<ext>`:
  - `delete-thumbs.sh` — removes generated `.avif` files (the common case).
  - `delete.sh` — removes generated `.webp/.jpg/.jpeg` variants.
  Run them from inside `public/files` only after confirming they will not match
  any legitimate original filename containing a size-like suffix.

---

## 9. If the target is not Laravel

The idea ports to any PHP stack; you only have to provide the four moving
parts yourself:

1. Serve `public/files/` statically, and route missing paths to a single PHP
   entry point.
2. Parse the trailing `-<width>x<height>.<ext>` from the requested path.
3. Use `league/glide` (the framework-agnostic `ServerFactory`) to produce the
   resized image and write it to a file cache — `GlideService.php` is a thin,
   portable wrapper around this.
4. Emit the cached bytes with an image `Content-Type`,
   `Cache-Control: max-age=31536000, public`, and a one-year `Expires` header —
   `GlideLaravelResponseFactory.php` shows exactly the headers to send.

If the target is a different PHP framework but still uses Composer, the classes
in `code/app/Services/` can be adapted by replacing only the two Laravel-specific
calls: `config(...)` (use the framework's config accessor) and the Laravel
`Response` object (use a plain header + `readfile()` response).

---

## 10. Definition of done

- [ ] `league/glide` and `intervention/image` installed.
- [ ] `config/glide.php`, `GlideService`, `GlideLaravelResponseFactory`, and
      `ImageController` added under the correct paths.
- [ ] `thumbnail_url()` helper autoloaded.
- [ ] `files/{path}` route registered before any catch-all.
- [ ] `GlideService` registered as a singleton.
- [ ] `storage/app/glide` writable and gitignored.
- [ ] A generated variant returns a valid, non-empty image; a missing original
      returns 404; a repeat request is served from cache.
- [ ] The deploy/smoke test (or an equivalent) catches 0-byte output.
