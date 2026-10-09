<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>phone.hellyer.kiwi — trash</title>
<style>
  :root {
    --bg: #f6f7f4;
    --surface: #ffffff;
    --ink: #1c2419;
    --ink-2: #5d6b58;
    --ink-3: #97a593;
    --accent-soft: #eaf3e2;
    --accent-ink: #3d7218;
    --keep: #3a6fd8;
    --line: #e5eae1;
  }
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Inter, "Helvetica Neue", Arial, sans-serif;
    background: var(--bg);
    color: var(--ink);
    min-height: 100dvh;
  }
  .page { max-width: 760px; margin: 0 auto; padding: 26px 20px 80px; }
  .topbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding-bottom: 6px; }
  .brand { display: flex; align-items: baseline; gap: 10px; }
  .brand h1 { font-size: 1.05rem; font-weight: 750; letter-spacing: -.01em; }
  .brand .sub { font-size: .78rem; font-weight: 600; color: var(--ink-3); }
  .back {
    display: inline-flex; align-items: center; gap: 7px;
    border: 1px solid var(--line); background: var(--surface); color: var(--ink-2);
    text-decoration: none; font-size: .85rem; font-weight: 650; padding: 8px 14px; border-radius: 10px;
  }
  .back:hover { border-color: #ccd6c7; color: var(--ink); }
  .section-label {
    font-size: .72rem; font-weight: 750; letter-spacing: .1em; text-transform: uppercase;
    color: var(--ink-3); padding: 22px 4px 8px;
  }
  .list { border: 1px solid var(--line); border-radius: 16px; background: var(--surface); overflow: hidden; }
  .row {
    display: flex; align-items: center; gap: 13px;
    padding: 12px 14px 12px 16px; border-bottom: 1px solid var(--line);
    transition: opacity .2s ease, transform .2s ease;
  }
  .row:last-child { border-bottom: none; }
  .row.dying { opacity: 0; transform: translateX(20px); }
  .glyph {
    flex: 0 0 auto; width: 38px; height: 38px; border-radius: 10px;
    display: grid; place-items: center; background: #eef0ec; color: var(--ink-2);
  }
  .glyph svg { width: 19px; height: 19px; }
  .info { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; gap: 2px; }
  .name { font-size: .95rem; font-weight: 550; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .path { font-size: .74rem; font-weight: 600; color: var(--ink-3); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .meta { flex: 0 0 auto; font-size: .78rem; font-weight: 600; color: var(--ink-3); }
  .restore {
    flex: 0 0 auto; display: inline-flex; align-items: center; gap: 6px;
    border: none; border-radius: 10px; background: var(--keep); color: #fff;
    font: inherit; font-size: .8rem; font-weight: 700; padding: 8px 12px; cursor: pointer;
    transition: background .15s, transform .1s;
  }
  .restore:hover { background: #335fbb; }
  .restore:active { transform: scale(.95); }
  .restore svg { width: 14px; height: 14px; }
  .empty { padding: 60px 20px; text-align: center; color: var(--ink-3); }
  .empty h2 { font-size: 1rem; font-weight: 650; color: var(--ink-2); margin-bottom: 6px; }
  .pathfoot { margin-top: 18px; color: var(--ink-3); font-size: .78rem; }
  .toast {
    position: fixed; left: 50%; bottom: 40px; transform: translate(-50%, 14px);
    background: rgba(23, 32, 15, .92); color: #f2f6ee; font-size: .84rem; font-weight: 600;
    padding: 10px 18px; border-radius: 100px; opacity: 0; pointer-events: none;
    transition: opacity .22s ease, transform .22s ease; white-space: nowrap; z-index: 60;
  }
  .toast.show { opacity: 1; transform: translate(-50%, 0); }
</style>
</head>
<body>
@php
    $iconFile = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V9z"/><path d="M13 3v6h6"/></svg>';
    $iconRestore = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12a8 8 0 1 0 2.3-5.7"/><path d="M4 4v4h4"/></svg>';
@endphp

<div class="page">
  <div class="topbar">
    <div class="brand">
      <h1>phone.hellyer.kiwi</h1>
      <span class="sub">trash</span>
    </div>
    <a class="back" href="{{ route('files.index') }}">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
      Back to files
    </a>
  </div>

  <div class="section-label" id="countLabel">{{ count($items) }} {{ count($items) === 1 ? 'item' : 'items' }}</div>
  <div class="list" id="list" @if (!count($items)) style="display:none" @endif>
    @foreach ($items as $item)
      <div class="row" data-path="{{ $item['path'] }}">
        <span class="glyph">{!! $iconFile !!}</span>
        <span class="info">
          <span class="name">{{ $item['name'] }}</span>
          <span class="path">{{ $item['path'] }}</span>
        </span>
        <span class="meta">{{ $item['size_label'] }}</span>
        <button class="restore" data-path="{{ $item['path'] }}">
          {!! $iconRestore !!}
          <span>Restore</span>
        </button>
      </div>
    @endforeach
  </div>

  <div class="empty" id="empty" @if (count($items)) style="display:none" @endif>
    <h2>Trash is empty</h2>
    <p>Deleted files will appear here.</p>
  </div>

  <p class="pathfoot">{{ $trashPath }}</p>
</div>

<div class="toast" id="toast"></div>

<script>
(function () {
  "use strict";

  const restoreUrl = @json(route('files.restore'));
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  const listEl = document.getElementById("list");
  const emptyEl = document.getElementById("empty");
  const countLabel = document.getElementById("countLabel");
  const toastEl = document.getElementById("toast");

  let toastTimer = null;
  function toast(msg) {
    toastEl.textContent = msg;
    toastEl.classList.add("show");
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toastEl.classList.remove("show"), 2200);
  }

  function updateCount() {
    const n = listEl.querySelectorAll(".row").length;
    countLabel.textContent = n + (n === 1 ? " item" : " items");
    listEl.style.display = n ? "" : "none";
    emptyEl.style.display = n ? "none" : "";
    if (!n) countLabel.style.display = "none";
  }

  listEl.querySelectorAll(".restore").forEach(btn => {
    btn.addEventListener("click", async () => {
      const path = btn.dataset.path;
      const row = btn.closest(".row");
      btn.disabled = true;
      try {
        const res = await fetch(restoreUrl, {
          method: "POST",
          cache: "no-store",
          headers: {
            "Content-Type": "application/json",
            "Accept": "application/json",
            "X-CSRF-TOKEN": csrf,
          },
          body: JSON.stringify({ path }),
        });
        let data = null;
        try { data = await res.json(); } catch (e) {}
        if (!res.ok) throw new Error((data && data.error) || "Restore failed (" + res.status + ")");
        row.classList.add("dying");
        setTimeout(() => { row.remove(); updateCount(); }, 200);
        toast("Restored to " + (data && data.restored_to ? data.restored_to : path));
      } catch (e) {
        toast(e.message || "Restore failed");
        btn.disabled = false;
      }
    });
  });
})();
</script>
</body>
</html>
