@extends('layouts.admin')
@section('title', $cfg['label'])
@section('heading', $cfg['label'].' '.($editing ? '수정' : '등록'))

@section('content')
@php($editorFields = collect($cfg['fields'])->where('type', 'editor')->pluck('name')->all())

<div class="adm-card" style="max-width:760px">
    <div style="padding:24px">
        <form method="POST" action="{{ $editing ? route('admin.update', [$cfg['key'], $item->id]) : route('admin.store', $cfg['key']) }}" enctype="multipart/form-data">
            @csrf
            @if($editing) @method('PUT') @endif

            @foreach($cfg['fields'] as $f)
                @php($name = $f['name'])
                @php($type = $f['type'] ?? 'text')
                @php($value = old($name, $item->{$name} ?? ''))
                <div class="afield">
                    @if($type !== 'checkbox')
                        <label>{{ $f['label'] }} @if($f['required'] ?? false)<span style="color:#e0322d">*</span>@endif</label>
                    @endif

                    @if($type === 'textarea')
                        <textarea name="{{ $name }}" class="atextarea" rows="{{ $f['rows'] ?? 4 }}">{{ $value }}</textarea>
                    @elseif($type === 'editor')
                        {{-- 리치 에디터 — 실제 값은 hidden 으로 넘기고 Quill 은 표시만 담당 --}}
                        <div class="aeditor-wrap" id="ed_wrap_{{ $name }}">
                            <div class="aeditor" id="ed_{{ $name }}">{!! $value !!}</div>
                        </div>
                        <textarea name="{{ $name }}" id="ed_val_{{ $name }}" hidden>{{ $value }}</textarea>
                        <noscript><div class="ahint">자바스크립트가 꺼져 있어 편집기를 쓸 수 없습니다.</div></noscript>
                    @elseif($type === 'checkbox')
                        <label class="acheck"><input type="checkbox" name="{{ $name }}" value="1" {{ $value ? 'checked' : '' }}> {{ $f['label'] }}</label>
                    @elseif($type === 'select')
                        <select name="{{ $name }}" class="aselect">
                            <option value="">— 선택 —</option>
                            @foreach($options[$name] ?? [] as $optVal => $optLabel)
                                <option value="{{ $optVal }}" {{ (string)$value === (string)$optVal ? 'selected' : '' }}>{{ $optLabel }}</option>
                            @endforeach
                        </select>
                    @elseif($type === 'number')
                        <input type="number" name="{{ $name }}" class="ainput" value="{{ $value }}" step="any">
                    @elseif($type === 'date')
                        <input type="date" name="{{ $name }}" class="ainput" value="{{ $value ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d') : '' }}">
                    @elseif($type === 'datetime')
                        <input type="datetime-local" name="{{ $name }}" class="ainput" value="{{ $value ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d\TH:i') : '' }}">
                    @elseif($type === 'image')
                        <div style="margin-bottom:8px"><img src="{{ $value }}" alt="" id="thumbPreview_{{ $name }}" style="max-height:120px;max-width:200px;border:1px solid var(--a-line);border-radius:8px;object-fit:contain;background:#fff;{{ $value ? '' : 'display:none' }}"></div>
                        <input type="file" name="{{ $name }}" accept="image/*" class="ainput" style="padding:8px">
                        @if($value)
                            <label class="acheck" style="margin-top:6px"><input type="checkbox" name="{{ $name }}_clear" value="1"> 기존 이미지 삭제</label>
                        @endif
                        @if($cfg['key'] === 'products' && $editing && $name === 'thumbnail')
                            <div style="margin-top:10px">
                                <button type="button" class="abtn abtn-ghost abtn-sm" id="imgAutoBtn"
                                        data-search="{{ route('admin.products.imagesearch', $item->id) }}"
                                        data-fetch="{{ route('admin.products.imagefetch', $item->id) }}">🔍 이미지 자동검색 (의료몰·네이버)</button>
                                <span id="imgAutoStatus" class="ahint" style="margin-left:8px"></span>
                                <div id="imgCandidates" style="margin-top:10px;grid-template-columns:repeat(6,1fr);gap:8px;display:none"></div>
                                <div style="margin-top:10px;display:flex;gap:6px">
                                    <input type="url" id="imgUrlInput" class="ainput" placeholder="또는 이미지 URL·상품페이지 URL 붙여넣기 (쿠팡/의료몰 등)"
                                           data-url="{{ route('admin.products.imageurl', $item->id) }}" style="flex:1;padding:8px">
                                    <button type="button" class="abtn abtn-ghost abtn-sm" id="imgUrlBtn">가져오기</button>
                                </div>
                                <div class="ahint" style="margin-top:6px">상품명으로 후보를 검색하거나, 정확한 이미지/상품페이지 URL을 붙여넣어 가져올 수 있습니다.</div>
                            </div>
                        @endif
                    @else
                        <input type="text" name="{{ $name }}" class="ainput" value="{{ $value }}">
                    @endif

                    @if(!empty($f['hint']))<div class="ahint">{{ $f['hint'] }}</div>@endif
                    @error($name)<div class="aerr">{{ $message }}</div>@enderror
                </div>
            @endforeach

            <div style="display:flex;gap:10px;margin-top:8px">
                <a href="{{ route('admin.index', $cfg['key']) }}" class="abtn abtn-ghost">취소</a>
                <button class="abtn abtn-pri">{{ $editing ? '수정 저장' : '등록' }}</button>
            </div>
        </form>
    </div>
</div>

@if($editorFields)
    {{-- Quill 1.3.7 자체 호스팅 — CDN 차단·폐쇄망에서도 편집기가 뜨도록 --}}
    <link rel="stylesheet" href="{{ asset('vendor/quill/quill.snow.css') }}">
    <style>
        .aeditor-wrap{border:1px solid var(--a-line);border-radius:8px;overflow:hidden;background:#fff}
        .aeditor-wrap:focus-within{border-color:var(--a-navy);box-shadow:0 0 0 3px rgba(11,61,145,.10)}
        .aeditor-wrap .ql-toolbar{border:0;border-bottom:1px solid var(--a-line);background:#fbfcfe}
        .aeditor-wrap .ql-container{border:0;font-size:14px;font-family:inherit}
        .aeditor-wrap .ql-editor{min-height:320px;line-height:1.7}
        .aeditor-wrap .ql-editor img{max-width:100%;height:auto}
        .aeditor-wrap .ql-editor.ql-blank::before{color:#9aa5bd;font-style:normal}
    </style>
    @include('partials._quill-image-resize')
    <script src="{{ asset('vendor/quill/quill.min.js') }}"></script>
    <script>
    (function () {
        var names = @json($editorFields);
        var csrf  = document.querySelector('meta[name="csrf-token"]').content;
        var url   = @json(route('admin.editor.upload'));
        var editors = [];

        names.forEach(function (n) {
            var host = document.getElementById('ed_' + n);
            var val  = document.getElementById('ed_val_' + n);
            if (!host || !val || !window.Quill) return;

            var q = new Quill(host, {
                theme: 'snow',
                placeholder: '상세 설명을 입력하세요. 이미지는 붙여넣거나 툴바에서 올릴 수 있습니다.',
                modules: {
                    toolbar: [
                        [{ header: [2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ color: [] }, { background: [] }],
                        [{ list: 'ordered' }, { list: 'bullet' }],
                        [{ align: [] }],
                        ['link', 'image'],
                        ['clean'],
                    ],
                },
            });

            if (window.installQuillImageResize) {
                window.installQuillImageResize(q, { uploadUrl: url, csrfToken: csrf });
            }
            editors.push({ q: q, val: val });
        });

        // 제출 직전 에디터 내용을 hidden 으로 옮긴다 (빈 문서는 빈 문자열로)
        var form = document.querySelector('.adm-card form');
        if (form) {
            form.addEventListener('submit', function () {
                editors.forEach(function (e) {
                    e.val.value = e.q.getText().trim() === '' && !e.q.root.querySelector('img')
                        ? '' : e.q.root.innerHTML;
                });
            });
        }
    })();
    </script>
@endif

<script>
(function () {
    var btn = document.getElementById('imgAutoBtn');
    if (!btn) return;
    var box = document.getElementById('imgCandidates');
    var status = document.getElementById('imgAutoStatus');
    var csrf = document.querySelector('meta[name="csrf-token"]').content;

    btn.addEventListener('click', function () {
        btn.disabled = true; status.textContent = '검색 중… (수 초 걸릴 수 있습니다)'; box.style.display = 'none'; box.innerHTML = '';
        fetch(btn.dataset.search, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                btn.disabled = false;
                var list = d.candidates || [];
                if (!list.length) { status.textContent = '후보 이미지를 찾지 못했습니다. (특수 제품일 수 있음)'; return; }
                status.textContent = list.length + '개 후보 — 맞는 이미지를 클릭하세요';
                box.style.display = 'grid';
                list.forEach(function (c) {
                    var fig = document.createElement('div');
                    fig.style.cssText = 'cursor:pointer;border:2px solid transparent;border-radius:8px;overflow:hidden;background:#fff';
                    fig.title = c.source + ' · ' + (c.alt || '');
                    fig.innerHTML = '<img src="' + c.thumb + '" style="width:100%;aspect-ratio:1;object-fit:contain;background:#fff"><div style="font-size:10px;color:#888;text-align:center;padding:2px">' + c.source + '</div>';
                    fig.addEventListener('click', function () { pick(c.url, fig); });
                    box.appendChild(fig);
                });
            })
            .catch(function () { btn.disabled = false; status.textContent = '검색 실패'; });
    });

    function pick(url, fig) {
        status.textContent = '다운로드 중…';
        [].forEach.call(box.children, function (n) { n.style.borderColor = 'transparent'; });
        fig.style.borderColor = '#2f6bff';
        fetch(btn.dataset.fetch, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ url: url })
        }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
          .then(function (res) {
              if (res.ok && res.d.thumbnail) {
                  var prev = document.getElementById('thumbPreview_thumbnail');
                  prev.src = res.d.thumbnail + '?t=' + Date.now(); prev.style.display = '';
                  status.textContent = '✓ 썸네일 지정됨' + (res.d.propagated ? ' · 유사 상품 ' + res.d.propagated + '개에도 자동 적용됨' : '') + ' (즉시 반영)';
              } else { status.textContent = '실패: ' + (res.d.error || ''); }
          }).catch(function () { status.textContent = '다운로드 실패'; });
    }

    var urlBtn = document.getElementById('imgUrlBtn');
    if (urlBtn) {
        urlBtn.addEventListener('click', function () {
            var input = document.getElementById('imgUrlInput');
            var u = input.value.trim();
            if (!u) { input.focus(); return; }
            status.textContent = '가져오는 중…';
            fetch(input.dataset.url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ url: u })
            }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
              .then(function (res) {
                  if (res.ok && res.d.thumbnail) {
                      var prev = document.getElementById('thumbPreview_thumbnail');
                      prev.src = res.d.thumbnail + '?t=' + Date.now(); prev.style.display = '';
                      status.textContent = '✓ 썸네일 지정됨' + (res.d.propagated ? ' · 유사 상품 ' + res.d.propagated + '개에도 자동 적용됨' : '') + ' (즉시 반영)';
                      input.value = '';
                  } else { status.textContent = '실패: ' + (res.d.error || ''); }
              }).catch(function () { status.textContent = '가져오기 실패'; });
        });
    }
})();
</script>
@endsection
