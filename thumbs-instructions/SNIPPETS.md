# Snippets for existing files

These are the edits to make in files that already exist in the target project.
Full file copies live in `code/` (see `README.md` §4).

---

## 1. `composer.json`

Add the autoload entry so `thumbnail_url()` is loaded, and the two packages.

```json
{
    "require": {
        "intervention/image": "^3.11",
        "league/glide": "^3.2"
    },
    "autoload": {
        "files": [
            "app/helpers.php"
        ]
    }
}
```

If the target already has an `autoload.files` array, add `"app/helpers.php"` to
it (do not remove existing entries). Then:

```bash
composer update
composer dump-autoload
```

---

## 2. `routes/web.php`

Add the import (top of file, with the other `use` statements):

```php
use App\Http\Controllers\ImageController;
```

Register the route. **It must appear before the site's catch-all `{any}`
route** (often `Route::get('{any}', ...)`), because Laravel matches routes in
registration order and `.*` will otherwise swallow it.

```php
/**
 * Glide dynamic image resizing route.
 * Handles dimensioned filenames like /files/2026/01/photo-412x232.avif
 * by resizing the base image (photo.avif) on the fly.
 */
Route::get('files/{path}', [ImageController::class, 'show'])
    ->where('path', '.*')
    ->name('image.show');
```

Do **not** attach trailing-slash middleware to this route.

---

## 3. `app/Providers/AppServiceProvider.php`

Register the Glide server wrapper as a singleton. Minimal diff — only the
import, the `register()` body, and (optionally) the `boot()` method are new.

```php
<?php

namespace App\Providers;

use App\Services\GlideService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GlideService::class);
    }

    public function boot(): void
    {
        //
    }
}
```

If the target's `AppServiceProvider` already has content, just add the `use`
statement and the `$this->app->singleton(GlideService::class);` line to the
existing `register()` method instead of replacing the file.

---

## 4. `.env` / `.env.example`

Document the driver override. Default is `gd` even without this variable;
include it for clarity.

```dotenv
GLIDE_DRIVER=gd
```

---

## 5. `.gitignore` (project root)

Make sure generated caches are never committed. The source repo ignores the
whole `storage/` tree except `.gitignore` stubs:

```
storage/*
!storage/private/
!storage/public/
!storage/.gitignore
```

Also ensure no generated variant files can be committed from `public/files`.
If that directory is tracked, add a pattern such as:

```
public/files/**/*-*x*.avif
public/files/**/*-*x*.webp
public/files/**/*-*x*.jpg
public/files/**/*-*x*.jpeg
```

(Only add these if no legitimate original filename ends in `-<digits>x<digits>`.)

---

## 6. Blade usage

```blade
<img
    src="{{ thumbnail_url($post['featuredImage'], 380, 300) }}"
    srcset="{{ thumbnail_url($post['featuredImage'], 380, 300) }} 380w,
            {{ thumbnail_url($post['featuredImage'], 480, 379) }} 480w,
            {{ thumbnail_url($post['featuredImage'], 686, 541) }} 686w"
    sizes="(max-width: 686px) 100vw, 686px"
    alt="{{ $post['title'] ?? '' }}"
    loading="lazy">
```

For a cover/hero image wider than tall, keep the source aspect ratio and let the
height follow:

```blade
<img src="{{ thumbnail_url($cover, 96, 96) }}"
     srcset="{{ thumbnail_url($cover, 96, 96) }} 96w,
             {{ thumbnail_url($cover, 192, 192) }} 192w">
```

---

## 7. JavaScript variant construction

Any client-side code that has the original path can build the same URL without
a server call:

```js
const match = originalPath.match(/^(.*)\.([a-z0-9]+)$/i);

const thumbSrc = originalPath.endsWith('.svg')
    ? originalPath
    : match
        ? `${match[1]}-380x300.${match[2]}`
        : `${originalPath}-380x300.avif`;
```

---

## 8. Deploy smoke test (recommended)

This catches a broken driver (e.g. Imagick without an AVIF encoder) that would
otherwise serve 0-byte images silently. Adapt to the target's deploy script.

```bash
SMOKE_SOURCE="$(find public/files -type f -name '*.avif' -size +1k | head -n 1)"
SMOKE_REL="${SMOKE_SOURCE#*public/}"
SMOKE_URL="https://example.com/${SMOKE_REL%.avif}-400x0.avif"
SMOKE_BYTES="$(curl -fsS -o /dev/null -w '%{size_download}' "$SMOKE_URL" || echo 0)"

if [ "${SMOKE_BYTES:-0}" -le 0 ]; then
    echo "Image smoke test FAILED: ${SMOKE_URL} returned ${SMOKE_BYTES} bytes" >&2
    exit 1
fi
```
