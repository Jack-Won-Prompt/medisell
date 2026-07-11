@extends('layouts.admin')
@section('title', $cfg['label'])
@section('heading', $cfg['label'].' '.($editing ? '수정' : '등록'))

@section('content')
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
                                <div class="ahint" style="margin-top:6px">상품명으로 후보를 검색합니다. 맞는 이미지를 클릭하면 서버가 내려받아 썸네일로 지정합니다.</div>
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
})();
</script>
@endsection
