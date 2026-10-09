<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    padding: 13px 16px; border-bottom: 1px solid var(--line);
  }
  .row:last-child { border-bottom: none; }
  .glyph {
    flex: 0 0 auto; width: 38px; height: 38px; border-radius: 10px;
    display: grid; place-items: center; background: var(--accent-soft); color: var(--accent-ink);
  }
  .glyph.file { background: #eef0ec; color: var(--ink-2); }
  .glyph svg { width: 19px; height: 19px; }
  .name { flex: 1 1 auto; min-width: 0; font-size: .95rem; font-weight: 550; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .meta { flex: 0 0 auto; font-size: .78rem; font-weight: 600; color: var(--ink-3); }
  .empty { padding: 60px 20px; text-align: center; color: var(--ink-3); }
  .empty h2 { font-size: 1rem; font-weight: 650; color: var(--ink-2); margin-bottom: 6px; }
  .path { margin-top: 18px; color: var(--ink-3); font-size: .78rem; }
</style>
</head>
<body>
@php
    $iconDir = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8a2 2 0 0 1 2-2h4l2 2.5h8a2 2 0 0 1 2 2V17a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>';
    $iconFile = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V9z"/><path d="M13 3v6h6"/></svg>';
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

  @if (count($items))
    <div class="section-label">{{ count($items) }} {{ count($items) === 1 ? 'item' : 'items' }}</div>
    <div class="list">
      @foreach ($items as $item)
        <div class="row">
          <span class="glyph {{ $item['type'] === 'file' ? 'file' : '' }}">{!! $item['type'] === 'dir' ? $iconDir : $iconFile !!}</span>
          <span class="name">{{ $item['name'] }}</span>
          <span class="meta">{{ $item['size_label'] }}</span>
        </div>
      @endforeach
    </div>
  @else
    <div class="empty">
      <h2>Trash is empty</h2>
      <p>Deleted files will appear here.</p>
    </div>
  @endif

  <p class="path">{{ $trashPath }}</p>
</div>
</body>
</html>
