# Plan: implement `phone.html` in the Laravel app

Turn the static `phone.html` mock into a working file browser backed by a
directory of real files, with images shown as Glide-generated thumbnails.

## Key facts

- The real files live in `./files` (project root), **not** in `public/`, so the
  web server cannot serve them directly. All access must go through Laravel.
- The production server stores these files at a different path, so the location
  is read from a `.env` variable.
- Image thumbnails are produced by the existing Glide system
  (`config/glide.php`, `GlideService`, `ImageController`).
- `phone.html` already contains all the CSS/JS for browsing, selecting and
  deleting. Only its hard-coded sample `tree` needs to be replaced with real
  data.

## 1. Make the files path configurable

Add to `.env` and `.env.example`:

```dotenv
PHONE_FILES_PATH=/workspace/files
PHONE_TRASH_PATH=/workspace/files/.trash
```

Create `config/phone.php`:

```php
return [
    'files_path' => env('PHONE_FILES_PATH', base_path('files')),
    'trash_path' => env('PHONE_TRASH_PATH', base_path('files/.trash')),
];
```

Point Glide at the same directory — change `config/glide.php` (use `env()`
directly here, since config files load in alphabetical order and `phone` is
not guaranteed to be loaded before `glide`):

```php
'source' => env('PHONE_FILES_PATH', base_path('files')),
```

Now Glide reads originals from the env-configured directory and caches
generated variants in `storage/app/glide`.

## 2. Backend controller

Create `app/Http/Controllers/FileBrowserController.php`:

| Method | Purpose |
| --- | --- |
| `index()` | Render the browser Blade view (root path). |
| `list()` | Return JSON of the entries in `?path=...` (name, type, size, relative path, `is_image`). |
| `destroy()` | Move the selected entries to the trash directory. |
| `keepOnly()` | Move everything **except** the selected entries to the trash directory. |
| `trash()` | Placeholder page for the Trash button. |

Rules:

- Resolve every requested path against `config('phone.files_path')` with
  `realpath()` and reject anything that escapes the root (no `../`).
- Hide dotfiles/dot-folders (e.g. `.stfolder`, `.trashed-*`).
- Move-to-trash = rename into `config('phone.trash_path')`, creating it if
  needed.
- `is_image` = extension in `jpg, jpeg, png, webp, avif`.

## 3. Thumbnails

- The existing route already serves generated images:
  `GET files/{path}` → `ImageController@show` (Glide).
- Since the files are not under `public/`, every original must also be fetched
  through Laravel. Add a matching route for originals (see step 4) that uses
  `GlideService`/a streamed response from `config('phone.files_path')`.
- In the listing, give every file a relative path. The view builds the
  thumbnail URL with the existing helper:

  ```blade
  <img src="{{ thumbnail_url('/files/'.$file['path'], 96, 96) }}"
       loading="lazy" alt="">
  ```

  `thumbnail_url()` rewrites `photo.jpg` → `photo-96x96.jpg`, which the Glide
  route generates and caches on first request. Non-images render the file
  glyph instead.

## 4. Routes (`routes/web.php`)

Place these **before** any catch-all route (the Glide route already is):

```php
Route::get('/', [FileBrowserController::class, 'index'])->name('files.index');
Route::get('/list', [FileBrowserController::class, 'list'])->name('files.list');
Route::delete('/delete', [FileBrowserController::class, 'destroy'])->name('files.destroy');
Route::post('/keep', [FileBrowserController::class, 'keepOnly'])->name('files.keep');
Route::get('/trash', [FileBrowserController::class, 'trash'])->name('files.trash');

// Originals + Glide variants (both used by the browser)
Route::get('files/{path}', [FileBrowserController::class, 'show'])
    ->where('path', '.*')->name('files.show');
```

## 5. Front-end view

1. Copy `public/phone.html` to `resources/views/files.blade.php`.
2. Delete the sample `tree` object.
3. Replace it with data from the server:
   - simplest: inject the initial listing with `@json($tree)` in the Blade, and
     reload the page on folder navigation (`?path=Photos/Camera`);
   - or keep the SPA feel and `fetch('/list?path=...')` on every navigation.
4. Keep all existing CSS and the selection / delete / "delete all but these" /
   Trash logic; only swap the data source and the delete handlers to POST to
   the routes above.
5. For image rows, render the Glide thumbnail; for folders keep the chevron and
   make the row navigate.

## 6. Verify

- `/` renders `phone.html` (Blade) listing the real `./files` tree.
- An image row loads a non-empty thumbnail from `/files/...-96x96.jpg`
  (`Cache-Control: max-age=31536000, public`), and the variant is cached under
  `storage/app/glide`.
- Selecting items + Delete moves them to `PHONE_TRASH_PATH`; Delete-all-but
  keeps only the selected items.
- Path traversal (`?path=../../etc`) is rejected.
- Changing `PHONE_FILES_PATH` in `.env` and running `php artisan config:clear`
  points the app at a different directory (simulating prod).

## Files touched

- new: `config/phone.php`, `app/Http/Controllers/FileBrowserController.php`,
  `resources/views/files.blade.php`
- edit: `.env`, `.env.example`, `config/glide.php`, `routes/web.php`
- reuse: `app/helpers.php`, `GlideService`, `ImageController`,
  `storage/app/glide`
