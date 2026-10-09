<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>phone.hellyer.kiwi — files</title>
<style>
  :root {
    --bg: #f6f7f4;
    --surface: #ffffff;
    --ink: #1c2419;
    --ink-2: #5d6b58;
    --ink-3: #97a593;
    --accent: #6faf3e;
    --accent-ink: #3d7218;
    --accent-soft: #eaf3e2;
    --line: #e5eae1;
    --danger: #d84a3a;
    --keep: #3a6fd8;
  }

  * { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }

  body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Inter, "Helvetica Neue", Arial, sans-serif;
    background: var(--bg);
    color: var(--ink);
    min-height: 100dvh;
  }

  .page { max-width: 760px; margin: 0 auto; padding: 0 20px 150px; }

  /* ---------- top bar ---------- */
  .topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 26px 0 6px;
  }

  .brand { display: flex; align-items: baseline; gap: 10px; min-width: 0; }
  .brand h1 { font-size: 1.05rem; font-weight: 750; letter-spacing: -.01em; white-space: nowrap; }
  .brand .sub { font-size: .78rem; font-weight: 600; color: var(--ink-3); }

  .ibtn {
    flex: 0 0 auto;
    width: 38px;
    height: 38px;
    border: 1px solid var(--line);
    border-radius: 10px;
    background: var(--surface);
    color: var(--ink-2);
    display: grid;
    place-items: center;
    cursor: pointer;
    transition: background .15s ease, border-color .15s ease, color .15s ease, transform .1s ease;
  }
  .ibtn:hover { border-color: #ccd6c7; color: var(--ink); }
  .ibtn:active { transform: scale(.94); }
  .ibtn svg { width: 18px; height: 18px; }
  .ibtn.trash:hover { border-color: #bcd4a4; color: var(--accent-ink); background: var(--accent-soft); }
  .ibtn.invert:hover { border-color: #b9cfee; color: var(--keep); background: #eaf1fb; }
  .ibtn.select.active { background: var(--accent-soft); border-color: #b7d49c; color: var(--accent-ink); }
  .ibtn.labeled {
    width: auto;
    padding: 0 13px;
    gap: 7px;
    display: inline-flex;
    align-items: center;
    font-size: .85rem;
    font-weight: 650;
  }
  .ibtn.labeled svg { width: 16px; height: 16px; }

  /* ---------- path row ---------- */
  .pathrow { display: flex; align-items: center; gap: 8px; padding: 10px 0 14px; }

  .up-btn {
    flex: 0 0 auto;
    width: 36px;
    height: 36px;
    border: 1px solid var(--line);
    border-radius: 10px;
    background: var(--surface);
    color: var(--ink-2);
    display: grid;
    place-items: center;
    cursor: pointer;
    transition: background .15s, color .15s, opacity .15s, transform .1s;
  }
  .up-btn:hover { color: var(--ink); border-color: #ccd6c7; }
  .up-btn:active { transform: scale(.94); }
  .up-btn:disabled { opacity: .3; cursor: default; }
  .up-btn:disabled:hover { color: var(--ink-2); border-color: var(--line); }
  .up-btn svg { width: 16px; height: 16px; }

  .crumbs {
    flex: 1 1 auto;
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 4px;
    overflow-x: auto;
    scrollbar-width: none;
    padding: 4px 0;
  }
  .crumbs::-webkit-scrollbar { display: none; }

  .sort {
    flex: 0 0 auto;
    display: inline-flex;
    border: 1px solid var(--line);
    border-radius: 9px;
    overflow: hidden;
    background: var(--surface);
  }
  .sort button {
    border: none;
    background: none;
    font: inherit;
    font-size: .74rem;
    font-weight: 650;
    color: var(--ink-2);
    padding: 6px 10px;
    cursor: pointer;
    transition: background .15s, color .15s;
  }
  .sort button + button { border-left: 1px solid var(--line); }
  .sort button:hover { color: var(--ink); }
  .sort button.active { background: var(--accent-soft); color: var(--accent-ink); }

  .crumb {
    border: none;
    background: none;
    font: inherit;
    font-size: .95rem;
    font-weight: 650;
    color: var(--ink-2);
    padding: 5px 9px;
    border-radius: 8px;
    white-space: nowrap;
    cursor: pointer;
    transition: color .15s, background .15s;
  }
  .crumb:hover { color: var(--ink); background: rgba(28,36,25,.05); }
  .crumb:last-child { color: var(--ink); pointer-events: none; }
  .crumb-sep { color: var(--ink-3); flex: 0 0 auto; user-select: none; font-size: .9rem; }

  /* ---------- sections ---------- */
  .section-label {
    font-size: .72rem;
    font-weight: 750;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--ink-3);
    padding: 18px 4px 8px;
  }

  .list {
    border: 1px solid var(--line);
    border-radius: 16px;
    background: var(--surface);
    overflow: hidden;
  }

  /* ---------- folder / file rows ---------- */
  .row {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 12px 16px;
    border-bottom: 1px solid var(--line);
    cursor: pointer;
    user-select: none;
    transition: background .13s ease, opacity .2s ease, transform .2s ease;
  }
  .row:last-child { border-bottom: none; }
  .row:hover { background: #fafbf8; }

  .row .glyph {
    flex: 0 0 auto;
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: grid;
    place-items: center;
    background: var(--accent-soft);
    color: var(--accent-ink);
  }
  .row.file .glyph { background: #eef0ec; color: var(--ink-2); }
  .row.file.video .glyph { background: var(--accent-soft); color: var(--accent-ink); }
  .glyph svg { width: 19px; height: 19px; }

  .row .name {
    flex: 1 1 auto;
    min-width: 0;
    font-size: .95rem;
    font-weight: 550;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .row .meta { flex: 0 0 auto; font-size: .78rem; font-weight: 600; color: var(--ink-3); white-space: nowrap; }

  .row .chev, .row .dl-hint { flex: 0 0 auto; width: 15px; height: 15px; color: var(--ink-3); }
  .row .dl-hint { opacity: 0; transition: opacity .15s; }
  .row:hover .dl-hint { opacity: 1; }
  .row:hover .chev { display: none; }
  body.selecting .row .dl-hint { display: none; }
  body.selecting .row:hover .chev { display: block; }
  .row .chev svg, .row .dl-hint svg { width: 15px; height: 15px; }

  /* ---------- image grid ---------- */
  .grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(128px, 1fr));
    gap: 10px;
  }

  .tile {
    position: relative;
    aspect-ratio: 1;
    border-radius: 14px;
    overflow: hidden;
    cursor: pointer;
    user-select: none;
    border: 2px solid transparent;
    transition: transform .12s ease, border-color .14s ease, opacity .2s ease, box-shadow .15s ease;
  }
  .tile:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(28,36,25,.12); }
  .tile:active { transform: scale(.97); }

  .tile img { width: 100%; height: 100%; object-fit: cover; display: block; }

  .tile .tname {
    position: absolute;
    left: 0; right: 0; bottom: 0;
    padding: 22px 9px 7px;
    font-size: .68rem;
    font-weight: 650;
    color: #fff;
    background: linear-gradient(to top, rgba(15,22,12,.72), transparent);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    pointer-events: none;
  }

  .tile .tcheck {
    position: absolute;
    top: 8px; left: 8px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: rgba(255,255,255,.92);
    display: grid;
    place-items: center;
    opacity: 0;
    transform: scale(.5);
    transition: opacity .15s, transform .18s cubic-bezier(.34,1.56,.64,1), background .15s;
    pointer-events: none;
  }
  .tile .tcheck svg { width: 12px; height: 12px; color: #fff; opacity: 0; }
  body.selecting .tile .tcheck { opacity: 1; transform: scale(1); }
  .tile.selected .tcheck { background: var(--accent); }
  .tile.selected .tcheck svg { opacity: 1; }
  .tile.selected { border-color: var(--accent); }
  .tile.dying { opacity: 0; transform: scale(.9); }

  /* ---------- selection checks (rows) ---------- */
  .check {
    flex: 0 0 auto;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: 2px solid #c6cfc2;
    background: var(--surface);
    display: grid;
    place-items: center;
    transition: background .15s, border-color .15s;
  }
  .check svg { width: 12px; height: 12px; color: #fff; opacity: 0; transform: scale(.4); transition: opacity .15s, transform .18s cubic-bezier(.34,1.56,.64,1); }
  .row.selected .check { background: var(--accent); border-color: var(--accent); }
  .row.selected .check svg { opacity: 1; transform: scale(1); }
  .row.selected { background: var(--accent-soft); }
  .row.selected:hover { background: #e2eed6; }

  .check { display: none; }
  body.selecting .check { display: grid; }
  body.selecting .chev { display: none; }
  body.selecting .row:hover { background: #fafbf8; }
  body.selecting .row.selected:hover { background: #e2eed6; }

  .row.dying { opacity: 0; transform: translateX(24px) scale(.98); }

  /* ---------- empty ---------- */
  .empty {
    display: none;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 70px 20px;
    color: var(--ink-3);
    text-align: center;
  }
  .empty.show { display: flex; }
  .empty svg { width: 40px; height: 40px; opacity: .5; }
  .empty h2 { font-size: 1rem; font-weight: 650; color: var(--ink-2); }
  .empty p { font-size: .85rem; max-width: 300px; line-height: 1.5; }

  /* ---------- action bar ---------- */
  .actionbar {
    position: fixed;
    left: 0; right: 0; bottom: 0;
    display: flex;
    justify-content: center;
    padding: 14px 20px calc(16px + env(safe-area-inset-bottom, 0px));
    background: linear-gradient(to top, var(--bg) 70%, rgba(246,247,244,0));
    transform: translateY(130%);
    transition: transform .28s cubic-bezier(.32,.72,.34,1);
    pointer-events: none;
  }
  body.selecting .actionbar { transform: translateY(0); pointer-events: auto; }

  .actionbar-inner { display: flex; gap: 12px; width: 100%; max-width: 500px; }

  .action {
    flex: 1 1 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    padding: 13px 10px;
    border: none;
    border-radius: 13px;
    font: inherit;
    font-size: .88rem;
    font-weight: 700;
    cursor: pointer;
    color: #fff;
    transition: transform .1s ease, opacity .15s ease;
  }
  .action svg { width: 17px; height: 17px; }
  .action:active { transform: scale(.96); }
  .action:disabled { opacity: .4; cursor: default; }
  .action:disabled:active { transform: none; }
  .action.del { background: var(--danger); box-shadow: 0 6px 18px rgba(216,74,58,.3); }
  .action.keep { background: var(--keep); box-shadow: 0 6px 18px rgba(58,111,216,.3); }

  /* ---------- lightbox ---------- */
  .lightbox {
    position: fixed;
    inset: 0;
    background: rgba(14, 19, 12, .92);
    display: none;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 50;
    padding: 64px 20px 20px;
  }
  .lightbox.open { display: flex; }

  .lb-bar {
    position: absolute;
    top: 0; left: 0; right: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 14px 18px;
  }

  .lb-name {
    color: #f2f6ee;
    font-size: .92rem;
    font-weight: 650;
    min-width: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .lb-actions { display: flex; gap: 8px; flex: 0 0 auto; }

  .lb-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    border: none;
    border-radius: 10px;
    font: inherit;
    font-size: .84rem;
    font-weight: 700;
    padding: 9px 14px;
    cursor: pointer;
    color: #fff;
    background: rgba(255,255,255,.14);
    transition: background .15s, transform .1s;
    text-decoration: none;
  }
  .lb-btn:hover { background: rgba(255,255,255,.26); }
  .lb-btn:active { transform: scale(.95); }
  .lb-btn svg { width: 15px; height: 15px; }
  .lb-btn.dl { background: var(--accent); }
  .lb-btn.dl:hover { background: #7fc04d; }
  .lb-btn.del { background: var(--danger); }
  .lb-btn.del:hover { background: #c53f30; }

  .lightbox img {
    max-width: min(92vw, 880px);
    max-height: 76vh;
    border-radius: 10px;
    box-shadow: 0 20px 60px rgba(0,0,0,.5);
  }

  .lightbox video {
    max-width: min(92vw, 880px);
    max-height: 76vh;
    border-radius: 10px;
    background: #000;
    box-shadow: 0 20px 60px rgba(0,0,0,.5);
  }

  /* ---------- toast ---------- */
  .toast {
    position: fixed;
    left: 50%;
    bottom: 100px;
    transform: translate(-50%, 14px);
    background: rgba(23, 32, 15, .92);
    color: #f2f6ee;
    font-size: .84rem;
    font-weight: 600;
    padding: 10px 18px;
    border-radius: 100px;
    opacity: 0;
    pointer-events: none;
    transition: opacity .22s ease, transform .22s ease;
    white-space: nowrap;
    z-index: 60;
  }
  .toast.show { opacity: 1; transform: translate(-50%, 0); }

  @media (max-width: 480px) {
    .page { padding: 0 14px 150px; }
    .topbar { padding-top: 18px; }
    .row { padding: 11px 14px; gap: 11px; }
    .row .meta { display: none; }
    .grid { grid-template-columns: repeat(auto-fill, minmax(104px, 1fr)); gap: 8px; }
    .action span.label { display: none; }
    .ibtn.labeled span { display: none; }
    .ibtn.labeled { width: 38px; padding: 0; }
  }
</style>
</head>
<body>

<div class="page">
  <div class="topbar">
    <div class="brand">
      <h1>phone.hellyer.kiwi</h1>
      <span class="sub">files</span>
    </div>
    <div style="display:flex; gap:8px;">
      <button class="ibtn select" id="selectBtn" title="Select items" aria-label="Select items">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8.5"/><path d="M8.5 12.2l2.4 2.4 4.6-5"/></svg>
      </button>
      <button class="ibtn invert" id="invertBtn" title="Reverse selection" aria-label="Reverse selection">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="8.5"/><path d="M12 3.5a8.5 8.5 0 0 1 0 17z" fill="currentColor" stroke="none"/></svg>
      </button>
      <button class="ibtn trash labeled" id="trashBtn" title="View trash" aria-label="View trash">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9.5 7V4.8A.8.8 0 0 1 10.3 4h3.4a.8.8 0 0 1 .8.8V7M6.2 7l.8 12.2a1.8 1.8 0 0 0 1.8 1.7h6.4a1.8 1.8 0 0 0 1.8-1.7L17.8 7"/><path d="M10 11v6M14 11v6"/></svg>
        <span>Trash</span>
      </button>
    </div>
  </div>

  <div class="pathrow">
    <button class="up-btn" id="upBtn" title="Go up" aria-label="Go up one directory">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
    </button>
    <nav class="crumbs" id="crumbs" aria-label="Directory path"></nav>
    <div class="sort" id="sort" role="group" aria-label="Sort by">
      <button type="button" data-sort="name" class="active">Name</button>
      <button type="button" data-sort="newest">Newest</button>
      <button type="button" data-sort="oldest">Oldest</button>
    </div>
  </div>

  <main>
    <div id="sections"></div>
    <div class="empty" id="empty">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8a2 2 0 0 1 2-2h4l2 2.5h8a2 2 0 0 1 2 2V17a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
      <h2>This folder is empty</h2>
      <p>Everything here has been deleted.</p>
    </div>
  </main>
</div>

<div class="actionbar" id="actionbar">
  <div class="actionbar-inner">
    <button class="action del" id="delBtn" disabled>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9.5 7V4.8A.8.8 0 0 1 10.3 4h3.4a.8.8 0 0 1 .8.8V7M6.2 7l.8 12.2a1.8 1.8 0 0 0 1.8 1.7h6.4a1.8 1.8 0 0 0 1.8-1.7L17.8 7"/><path d="M10 11v6M14 11v6"/></svg>
      <span class="label" id="delLabel">Delete</span>
    </button>
    <button class="action keep" id="keepBtn" disabled>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 4v5c0 4.6-3 8.2-7 9-4-.8-7-4.4-7-9V7z"/><path d="M9 12l2.2 2.2L15.5 10"/></svg>
      <span class="label">Delete all but these</span>
    </button>
  </div>
</div>

<div class="lightbox" id="lightbox">
  <div class="lb-bar">
    <span class="lb-name" id="lbName"></span>
    <div class="lb-actions">
      <button class="lb-btn del" id="lbDel" title="Delete">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9.5 7V4.8A.8.8 0 0 1 10.3 4h3.4a.8.8 0 0 1 .8.8V7M6.2 7l.8 12.2a1.8 1.8 0 0 0 1.8 1.7h6.4a1.8 1.8 0 0 0 1.8-1.7L17.8 7"/><path d="M10 11v6M14 11v6"/></svg>
        <span>Delete</span>
      </button>
      <a class="lb-btn dl" id="lbDl" download>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v11M7 11l5 5 5-5M4.5 20h15"/></svg>
        <span>Download</span>
      </a>
      <button class="lb-btn" id="lbClose" title="Close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
      </button>
    </div>
  </div>
  <img id="lbImg" alt="">
  <video id="lbVideo" controls playsinline preload="metadata" style="display:none"></video>
</div>

<div class="toast" id="toast"></div>

<script>
(function () {
  "use strict";

  const initialPath = @json($path);
  const listUrl = @json(route('files.list'));
  const destroyUrl = @json(route('files.destroy'));
  const keepUrl = @json(route('files.keep'));
  const trashUrl = @json(route('files.trash'));
  const csrf = document.querySelector('meta[name="csrf-token"]').content;

  /* ---------- dom ---------- */
  const $ = (id) => document.getElementById(id);
  const crumbsEl = $("crumbs"), sectionsEl = $("sections"), emptyEl = $("empty"),
        upBtn = $("upBtn"), selectBtn = $("selectBtn"), invertBtn = $("invertBtn"),
        delBtn = $("delBtn"), keepBtn = $("keepBtn"), delLabel = $("delLabel"),
        toastEl = $("toast"), lightbox = $("lightbox"), lbImg = $("lbImg"),
        lbVideo = $("lbVideo"),
        lbName = $("lbName"), lbDl = $("lbDl"), sortEl = $("sort");

  const ICONS = {
    dir:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8a2 2 0 0 1 2-2h4l2 2.5h8a2 2 0 0 1 2 2V17a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>',
    file:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V9z"/><path d="M13 3v6h6"/></svg>',
    video: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="13" height="14" rx="2.2"/><path d="M16 10.5l5-2.8v8.6l-5-2.8z"/></svg>',
    play:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8.5"/><path d="M10 8.6l6 3.4-6 3.4z" fill="currentColor"/></svg>',
    check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>',
    chev:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>',
    dl:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v11M7 11l5 5 5-5M4.5 20h15"/></svg>',
  };

  /* ---------- state ---------- */
  let dir = { path: "", parent: null, breadcrumbs: [], entries: [] };
  let selecting = false;
  const selected = new Set();   // stores entry.path values
  let pressTimer = null;
  let longPressed = false;
  let currentSort = "name";

  let toastTimer = null;
  function toast(msg) {
    toastEl.textContent = msg;
    toastEl.classList.add("show");
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toastEl.classList.remove("show"), 1900);
  }

  function sortCompare(a, b) {
    if (currentSort === "name") {
      return (a.name || "").localeCompare(b.name || "", undefined, { numeric: true, sensitivity: "base" });
    }
    const da = a.modified_ts || 0;
    const db = b.modified_ts || 0;
    return currentSort === "oldest" ? da - db : db - da;
  }

  function itemsAt() {
    const c = dir.entries || [];
    return {
      dirs: c.filter(i => i.type === "dir").sort(sortCompare),
      images: c.filter(i => i.type === "file" && i.is_image).sort(sortCompare),
      files: c.filter(i => i.type === "file" && !i.is_image).sort(sortCompare),
      all: c.slice().sort(sortCompare),
    };
  }

  function elFor(p) {
    return sectionsEl.querySelector('[data-path="' + (window.CSS && CSS.escape ? CSS.escape(p) : p) + '"]');
  }

  /* ---------- data ---------- */
  async function navigate(p, push) {
    let res;
    try {
      res = await fetch(listUrl + "?path=" + encodeURIComponent(p || "") + "&_=" + Date.now(), {
        headers: { "Accept": "application/json" },
        cache: "no-store",
      });
    } catch (e) {
      toast("Could not load folder");
      return;
    }
    if (!res.ok) { toast("Not found"); return; }
    dir = await res.json();
    selected.clear();
    setSelecting(false);
    render();
    if (push !== false) {
      const url = dir.path ? location.pathname + "?path=" + encodeURIComponent(dir.path) : location.pathname;
      history.pushState({ path: dir.path }, "", url);
    }
  }

  /* ---------- render ---------- */
  function renderCrumbs() {
    crumbsEl.innerHTML = "";
    (dir.breadcrumbs || []).forEach((p, i) => {
      if (i > 0) {
        const s = document.createElement("span");
        s.className = "crumb-sep";
        s.textContent = "\u203A";
        crumbsEl.appendChild(s);
      }
      const b = document.createElement("button");
      b.className = "crumb";
      b.textContent = p.name;
      b.addEventListener("click", () => navigate(p.path));
      crumbsEl.appendChild(b);
    });
    upBtn.disabled = dir.parent === null;
  }

  function bindSelectPress(el, item) {
    el.addEventListener("pointerdown", () => {
      longPressed = false;
      if (selecting) return;
      clearTimeout(pressTimer);
      pressTimer = setTimeout(() => {
        if (!selecting) {
          longPressed = true;
          setSelecting(true, item.path);
        }
      }, 480);
    });
    ["pointerup", "pointerleave", "pointercancel"].forEach(ev =>
      el.addEventListener(ev, () => clearTimeout(pressTimer)));
  }

  // Returns true if the click was the tail of a long-press and should be ignored.
  function consumedByLongPress(e) {
    if (!longPressed) return false;
    longPressed = false;
    e.preventDefault();
    return true;
  }

  function toggleSelected(item, el) {
    el.classList.toggle("selected");
    el.classList.contains("selected") ? selected.add(item.path) : selected.delete(item.path);
    updateActions();
  }

  function download(item) {
    const a = document.createElement("a");
    a.href = item.url;
    a.download = item.name;
    document.body.appendChild(a);
    a.click();
    a.remove();
    toast("Downloading " + item.name);
  }

  function makeRow(item) {
    const row = document.createElement("div");
    row.className = item.type === "dir" ? "row folder" : (item.is_video ? "row file video" : "row file");
    row.dataset.name = item.name;
    row.dataset.path = item.path;

    const chk = document.createElement("span");
    chk.className = "check";
    chk.innerHTML = ICONS.check;

    const glyph = document.createElement("span");
    glyph.className = "glyph";
    glyph.innerHTML = item.type === "dir" ? ICONS.dir : (item.is_video ? ICONS.video : ICONS.file);

    const name = document.createElement("span");
    name.className = "name";
    name.textContent = item.name;

    const meta = document.createElement("span");
    meta.className = "meta";
    meta.textContent = item.type === "dir"
      ? item.count + " items"
      : item.size_label;

    row.append(chk, glyph, name, meta);

    if (item.type === "dir") {
      const chev = document.createElement("span");
      chev.className = "chev";
      chev.innerHTML = ICONS.chev;
      row.appendChild(chev);
    } else {
      const hint = document.createElement("span");
      hint.className = "dl-hint";
      hint.innerHTML = item.is_video ? ICONS.play : ICONS.dl;
      row.appendChild(hint);
    }

    row.addEventListener("click", (e) => {
      if (e.detail > 1) return;             // ignore the 2nd click of a double-click
      if (consumedByLongPress(e)) return;
      if (selecting) return toggleSelected(item, row);
      if (item.type === "dir") navigate(item.path);
      else if (item.is_video) openLightbox(item);
      else download(item);
    });

    bindSelectPress(row, item);
    if (selected.has(item.path)) row.classList.add("selected");
    return row;
  }

  function makeTile(item) {
    const tile = document.createElement("div");
    tile.className = "tile";
    tile.dataset.name = item.name;
    tile.dataset.path = item.path;

    const img = document.createElement("img");
    img.src = item.thumbnail;
    img.alt = item.name;
    img.loading = "lazy";
    img.draggable = false;
    if (item.srcset) {
      img.srcset = item.srcset;
      img.sizes = "(max-width: 480px) 104px, 136px";
    }

    const tname = document.createElement("span");
    tname.className = "tname";
    tname.textContent = item.name;

    const tcheck = document.createElement("span");
    tcheck.className = "tcheck";
    tcheck.innerHTML = ICONS.check;

    tile.append(img, tname, tcheck);

    tile.addEventListener("click", (e) => {
      if (e.detail > 1) return;             // ignore the 2nd click of a double-click
      if (consumedByLongPress(e)) return;
      if (selecting) return toggleSelected(item, tile);
      openLightbox(item);
    });

    bindSelectPress(tile, item);
    if (selected.has(item.path)) tile.classList.add("selected");
    return tile;
  }

  function render() {
    const { dirs, images, files, all } = itemsAt();
    sectionsEl.innerHTML = "";
    emptyEl.classList.toggle("show", all.length === 0);

    const addLabel = (t) => {
      const l = document.createElement("div");
      l.className = "section-label";
      l.textContent = t;
      sectionsEl.appendChild(l);
    };

    if (dirs.length) {
      addLabel("Folders");
      const list = document.createElement("div");
      list.className = "list";
      dirs.forEach(d => list.appendChild(makeRow(d)));
      sectionsEl.appendChild(list);
    }
    if (images.length) {
      addLabel("Images");
      const grid = document.createElement("div");
      grid.className = "grid";
      images.forEach(i => grid.appendChild(makeTile(i)));
      sectionsEl.appendChild(grid);
    }
    if (files.length) {
      addLabel("Files");
      const list = document.createElement("div");
      list.className = "list";
      files.forEach(f => list.appendChild(makeRow(f)));
      sectionsEl.appendChild(list);
    }
    renderCrumbs();
    updateActions();
  }

  /* ---------- selection ---------- */
  function setSelecting(on, firstPath) {
    selecting = on;
    document.body.classList.toggle("selecting", on);
    selectBtn.classList.toggle("active", on);
    selected.clear();
    sectionsEl.querySelectorAll(".selected").forEach(el => el.classList.remove("selected"));
    if (on && firstPath) {
      selected.add(firstPath);
      const el = elFor(firstPath);
      if (el) el.classList.add("selected");
    }
    updateActions();
    if (on && !firstPath) toast("Click or long-press items to select");
  }

  function updateActions() {
    const n = selected.size;
    delBtn.disabled = n === 0;
    keepBtn.disabled = n === 0;
    delLabel.textContent = n === 0 ? "Delete" : "Delete " + n + (n === 1 ? " item" : " items");
  }

  invertBtn.addEventListener("click", () => {
    if (!selecting) setSelecting(true);
    const { all } = itemsAt();
    all.forEach(item => {
      const el = elFor(item.path);
      const isSel = selected.has(item.path);
      if (isSel) { selected.delete(item.path); if (el) el.classList.remove("selected"); }
      else { selected.add(item.path); if (el) el.classList.add("selected"); }
    });
    updateActions();
  });

  /* ---------- deletions ---------- */
  async function act(url, method, paths) {
    const res = await fetch(url, {
      method,
      cache: "no-store",
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "X-CSRF-TOKEN": csrf,
      },
      body: JSON.stringify({ path: dir.path, paths: paths || Array.from(selected) }),
    });
    let data = null;
    try { data = await res.json(); } catch (e) {}
    if (!res.ok) throw new Error((data && data.error) || "Request failed (" + res.status + ")");
    return data || {};
  }

  delBtn.addEventListener("click", async () => {
    if (!selected.size) return;
    const paths = new Set(selected);
    sectionsEl.querySelectorAll("[data-path]").forEach(el => {
      if (paths.has(el.dataset.path)) el.classList.add("dying");
    });
    try {
      const r = await act(destroyUrl, "DELETE", Array.from(selected));
      const n = r.moved || 0;
      toast(n + (n === 1 ? " item" : " items") + " moved to trash");
      setTimeout(() => navigate(dir.path, false), 240);
    } catch (e) {
      toast(e.message || "Delete failed");
      sectionsEl.querySelectorAll(".dying").forEach(el => el.classList.remove("dying"));
    }
  });

  keepBtn.addEventListener("click", async () => {
    if (!selected.size) return;
    try {
      const r = await act(keepUrl, "POST", Array.from(selected));
      const n = r.moved || 0;
      toast(n + (n === 1 ? " item" : " items") + " moved to trash");
      setTimeout(() => navigate(dir.path, false), 240);
    } catch (e) {
      toast(e.message || "Delete failed");
    }
  });

  /* ---------- lightbox ---------- */
  let lightboxItem = null;

  function openLightbox(item) {
    lightboxItem = item;
    lbName.textContent = item.name;
    lbDl.href = item.url;
    lbDl.download = item.name;

    if (item.is_video) {
      // Play videos inline (with native controls) instead of downloading.
      lbImg.style.display = "none";
      lbImg.removeAttribute("src");
      lbVideo.style.display = "block";
      lbVideo.src = item.url;
    } else {
      lbVideo.pause();
      lbVideo.style.display = "none";
      lbVideo.removeAttribute("src");
      lbVideo.load();
      lbImg.style.display = "block";
      lbImg.src = item.url;
      lbImg.alt = item.name;
    }

    lightbox.classList.add("open");
  }

  function closeLightbox() {
    lightbox.classList.remove("open");
    lbVideo.pause();
  }

  $("lbClose").addEventListener("click", closeLightbox);
  lightbox.addEventListener("click", (e) => { if (e.target === lightbox) closeLightbox(); });

  $("lbDel").addEventListener("click", async () => {
    if (!lightboxItem) return;
    const item = lightboxItem;
    try {
      await act(destroyUrl, "DELETE", [item.path]);
      closeLightbox();
      toast("1 item moved to trash");
      setTimeout(() => navigate(dir.path, false), 240);
    } catch (e) {
      toast(e.message || "Delete failed");
    }
  });

  /* ---------- controls ---------- */
  upBtn.addEventListener("click", () => {
    if (selecting) { setSelecting(false); return; }
    if (dir.parent !== null) navigate(dir.parent);
  });

  selectBtn.addEventListener("click", () => setSelecting(!selecting));
  $("trashBtn").addEventListener("click", () => { location.href = trashUrl; });

  sortEl.querySelectorAll("button").forEach(btn => {
    btn.addEventListener("click", () => {
      currentSort = btn.dataset.sort;
      sortEl.querySelectorAll("button").forEach(b => b.classList.toggle("active", b === btn));
      render();
    });
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      if (lightbox.classList.contains("open")) closeLightbox();
      else if (selecting) setSelecting(false);
    }
  });

  window.addEventListener("popstate", () => {
    const p = new URLSearchParams(location.search).get("path") || "";
    navigate(p, false);
  });

  navigate(initialPath, false);
})();
</script>
</body>
</html>
