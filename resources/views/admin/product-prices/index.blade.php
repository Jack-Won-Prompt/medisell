@extends('layouts.admin')
@section('title', '가격 일괄 관리')
@section('heading', '가격 일괄 관리 (정가 · 병원 회원 할인가)')

@section('content')
@php
    // 정렬 링크: 같은 열을 다시 누르면 반대 방향
    $sortLink = function (string $asc, string $desc, string $label) use ($f) {
        $next = $f['sort'] === $desc ? $asc : $desc;
        $mark = $f['sort'] === $desc ? ' ▼' : ($f['sort'] === $asc ? ' ▲' : '');
        return '<a href="'.e(request()->fullUrlWithQuery(['sort' => $next, 'page' => null])).'" style="color:inherit">'.e($label).$mark.'</a>';
    };
@endphp

<div class="adm-stats" style="margin-bottom:16px">
    <div class="adm-stat"><span class="ic"><x-icon name="box"/></span><div><div class="v">{{ number_format($stats['total']) }}</div><div class="l">전체 상품</div></div></div>
    <div class="adm-stat"><span class="ic"><x-icon name="coin"/></span><div><div class="v">{{ number_format($stats['withMem']) }}</div><div class="l">병원 회원 할인가 설정</div></div></div>
    <div class="adm-stat"><span class="ic"><x-icon name="close"/></span><div><div class="v">{{ number_format($stats['noPrice']) }}</div><div class="l">정가 0원(가격문의)</div></div></div>
</div>

<div class="adm-card">
    <div class="h">
        <span>상품 검색</span>
        <span style="display:flex;gap:6px">
            <a href="{{ route('admin.product-prices.export', request()->query()) }}" class="abtn abtn-ghost abtn-sm"><x-icon name="doc" :size="14"/> CSV 내려받기(현재 조건)</a>
            <button type="button" class="abtn abtn-ghost abtn-sm" onclick="document.getElementById('impBox').style.display = document.getElementById('impBox').style.display === 'none' ? '' : 'none'">CSV 올리기</button>
        </span>
    </div>
    <div style="padding:14px 20px">
        <div id="impBox" style="display:none;background:#f7f9fc;border:1px dashed #c7cedd;border-radius:10px;padding:12px 14px;margin-bottom:12px">
            <form method="POST" action="{{ route('admin.product-prices.import') }}" enctype="multipart/form-data" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                @csrf
                <input type="file" name="file" accept=".csv,text/csv" class="ainput" style="flex:1;min-width:220px;padding:7px" required>
                <button class="abtn abtn-pri">업로드 반영</button>
            </form>
            <div class="ahint" style="margin-top:6px">「CSV 내려받기」 파일에서 <b>정가 · 병원 회원 할인가 · 재고 · 판매(1/0)</b> 열만 고쳐 올리세요. 상품ID로 찾아 수정하며 새 상품은 만들지 않습니다. 할인가를 비우면 해제됩니다. 엑셀에서 저장한 파일도 그대로 됩니다.</div>
        </div>

        <form method="GET" action="{{ route('admin.product-prices.index') }}" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap">
            <div class="afield" style="margin:0;flex:1;min-width:200px"><label>검색</label>
                <input type="text" name="q" class="ainput" value="{{ $f['q'] }}" placeholder="상품명 · 상품코드 · 제조사"></div>
            <div class="afield" style="margin:0;width:200px"><label>카테고리</label>
                <select name="cat" class="aselect">
                    <option value="">전체</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" {{ (int) $f['cat'] === $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @foreach($c->children as $cc)
                            <option value="{{ $cc->id }}" {{ (int) $f['cat'] === $cc->id ? 'selected' : '' }}>&nbsp;&nbsp;└ {{ $cc->name }}</option>
                        @endforeach
                    @endforeach
                </select></div>
            <div class="afield" style="margin:0;width:120px"><label>판매상태</label>
                <select name="state" class="aselect">
                    <option value="">전체</option>
                    <option value="on" {{ $f['state']==='on' ? 'selected' : '' }}>판매중</option>
                    <option value="off" {{ $f['state']==='off' ? 'selected' : '' }}>판매안함</option>
                </select></div>
            <div class="afield" style="margin:0;width:150px"><label>병원 회원 할인가</label>
                <select name="member" class="aselect">
                    <option value="">전체</option>
                    <option value="set" {{ $f['member']==='set' ? 'selected' : '' }}>설정됨</option>
                    <option value="unset" {{ $f['member']==='unset' ? 'selected' : '' }}>미설정</option>
                </select></div>
            <div class="afield" style="margin:0;width:100px"><label>표시</label>
                <select name="per_page" class="aselect">
                    @foreach([20,50,100,200] as $n)<option value="{{ $n }}" {{ $perPage===$n ? 'selected' : '' }}>{{ $n }}개</option>@endforeach
                </select></div>
            <input type="hidden" name="sort" value="{{ $f['sort'] }}">
            <button class="abtn abtn-ghost">조회</button>
        </form>
    </div>
</div>

<form method="POST" action="{{ route('admin.product-prices.save') }}" id="ppGrid" class="adm-card" style="margin-top:16px">
    @csrf @method('PUT')
    <div class="h">
        <span>상품 {{ number_format($products->total()) }}개 <span class="muted" style="font-weight:400;font-size:12.5px">· 칸을 고친 뒤 [저장]</span></span>
        <span style="display:flex;gap:8px;align-items:center">
            <span class="muted" style="font-size:12.5px" id="ppDirty">바뀐 항목 없음</span>
            <button class="abtn abtn-pri abtn-sm">가격·재고 저장</button>
        </span>
    </div>
    <div style="padding:10px 20px 0">
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;background:#f7f9fc;border-radius:10px;padding:10px 12px;font-size:13px">
            <b>병원 회원 할인가 일괄 채우기</b>
            <label class="acheck" style="display:flex;align-items:center;gap:4px"><input type="checkbox" id="ppOnlyChecked"> 체크한 상품만</label>
            <span>정가 대비</span>
            <input type="number" id="ppRate" class="ainput" style="width:80px;height:32px" min="0" max="100" step="0.1" placeholder="%">
            <span>% 할인 (</span>
            <select id="ppRound" class="aselect" style="width:auto;height:32px">
                <option value="1">원 단위</option><option value="10" selected>10원 단위</option><option value="100">100원 단위</option>
            </select>
            <span>올림)</span>
            <button type="button" class="abtn abtn-ghost abtn-sm" id="ppApply">채우기</button>
            <button type="button" class="abtn abtn-ghost abtn-sm" id="ppClear">할인가 비우기</button>
        </div>
    </div>
    <div style="padding:0 20px 10px;overflow-x:auto">
        <table class="atable" style="font-size:13px">
            <thead><tr>
                <th style="width:30px"><input type="checkbox" id="ppAll"></th>
                <th style="width:90px">상품코드</th>
                <th>{!! $sortLink('name', 'name', '상품명 / 규격') !!}</th>
                <th style="width:60px">단위</th>
                <th style="text-align:right;width:120px">{!! $sortLink('price_asc', 'price_desc', '정가') !!}</th>
                <th style="text-align:right;width:140px">{!! $sortLink('member_asc', 'member_desc', '병원 회원 할인가') !!}</th>
                <th style="text-align:right;width:60px">할인율</th>
                <th style="text-align:right;width:90px">{!! $sortLink('stock_asc', 'stock_asc', '재고') !!}</th>
                <th style="width:50px;text-align:center">판매</th>
            </tr></thead>
            <tbody>
            @forelse($products as $p)
                <tr class="pp-row" style="{{ $p->is_active ? '' : 'opacity:.6' }}">
                    <td><input type="checkbox" class="pp-chk"></td>
                    <td style="font-size:12px">{{ $p->code }}</td>
                    <td><a href="{{ route('admin.edit', ['products', $p->id]) }}" style="color:inherit">{{ $p->name }}</a>
                        @if($p->spec)<br><span class="muted" style="font-size:11.5px">{{ \Illuminate\Support\Str::limit($p->spec, 50) }}</span>@endif</td>
                    <td>{{ $p->unit }}</td>
                    <td style="text-align:right"><input type="number" name="rows[{{ $p->id }}][price]" class="ainput pp-in pp-price" min="0" value="{{ $p->price }}" data-orig="{{ $p->price }}" style="text-align:right;height:32px;width:110px" required></td>
                    <td style="text-align:right"><input type="number" name="rows[{{ $p->id }}][member_price]" class="ainput pp-in pp-mem" min="0" value="{{ $p->member_price }}" data-orig="{{ $p->member_price }}" placeholder="-" style="text-align:right;height:32px;width:120px"></td>
                    <td style="text-align:right" class="pp-rate">-</td>
                    <td style="text-align:right"><input type="number" name="rows[{{ $p->id }}][stock]" class="ainput pp-in" min="0" value="{{ $p->stock }}" data-orig="{{ $p->stock }}" style="text-align:right;height:32px;width:80px"></td>
                    <td style="text-align:center">
                        <input type="hidden" name="rows[{{ $p->id }}][is_active]" value="0">
                        <input type="checkbox" name="rows[{{ $p->id }}][is_active]" value="1" class="pp-in" data-orig="{{ $p->is_active ? 1 : 0 }}" {{ $p->is_active ? 'checked' : '' }}>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" style="text-align:center;color:#97a0b8;padding:30px">조건에 맞는 상품이 없습니다.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;gap:10px;flex-wrap:wrap">
            <div>{{ $products->links() }}</div>
            <button class="abtn abtn-pri">가격·재고 저장</button>
        </div>
        <div class="ahint" style="margin-top:6px">
            <b>병원 회원 할인가</b>는 승인된 병원 회원 모두에게 적용되는 상품별 가격입니다. 비워 두면 정가로 판매합니다.
            회원 개별 전용가(회원 상세)·거래처 단가가 있으면 그쪽이 우선합니다. 다른 페이지로 넘어가기 전에 저장하세요.
        </div>
    </div>
</form>

<script>
(function () {
    var form = document.getElementById('ppGrid'), dirty = document.getElementById('ppDirty');
    var rows = form.querySelectorAll('.pp-row');
    function val(i) { return i.type === 'checkbox' ? (i.checked ? '1' : '0') : String(i.value); }
    function rate(tr) {
        var pr = Number(tr.querySelector('.pp-price').value), m = tr.querySelector('.pp-mem').value;
        tr.querySelector('.pp-rate').textContent = (m !== '' && pr > 0 && Number(m) < pr) ? Math.round((pr - Number(m)) / pr * 100) + '%' : '-';
    }
    function count() {
        var n = 0;
        form.querySelectorAll('.pp-in').forEach(function (i) {
            var ch = val(i) !== String(i.dataset.orig || (i.type === 'checkbox' ? '0' : ''));
            i.style.background = ch && i.type !== 'checkbox' ? '#fff7e6' : '';
            if (ch) n++;
        });
        dirty.textContent = n ? '저장 안 된 변경 ' + n + '칸' : '바뀐 항목 없음';
        dirty.style.color = n ? '#b45309' : '';
        return n;
    }
    rows.forEach(function (tr) { rate(tr); tr.querySelectorAll('.pp-in').forEach(function (i) { i.addEventListener('input', function () { rate(tr); count(); }); i.addEventListener('change', count); }); });
    document.getElementById('ppAll').addEventListener('change', function () { var c = this.checked; form.querySelectorAll('.pp-chk').forEach(function (x) { x.checked = c; }); });
    function targets() {
        var only = document.getElementById('ppOnlyChecked').checked;
        return Array.prototype.filter.call(rows, function (tr) { return !only || tr.querySelector('.pp-chk').checked; });
    }
    document.getElementById('ppApply').addEventListener('click', function () {
        var r = document.getElementById('ppRate').value, unit = Number(document.getElementById('ppRound').value);
        if (r === '' || Number(r) < 0 || Number(r) > 100) { alert('할인율(%)을 입력해 주세요.'); return; }
        var t = targets(); if (!t.length) { alert('체크한 상품이 없습니다.'); return; }
        t.forEach(function (tr) {
            var pr = Number(tr.querySelector('.pp-price').value);
            if (pr > 0) { tr.querySelector('.pp-mem').value = Math.ceil(pr * (100 - Number(r)) / 100 / unit) * unit; rate(tr); }
        });
        count();
    });
    document.getElementById('ppClear').addEventListener('click', function () {
        var t = targets(); if (!t.length || !confirm(t.length + '개 상품의 병원 회원 할인가를 비웁니다. 저장하면 정가로 판매됩니다. 계속할까요?')) return;
        t.forEach(function (tr) { tr.querySelector('.pp-mem').value = ''; rate(tr); }); count();
    });
    var submitting = false;
    form.addEventListener('submit', function () { submitting = true; });
    window.addEventListener('beforeunload', function (e) { if (!submitting && count()) { e.preventDefault(); e.returnValue = ''; } });
})();
</script>
@endsection
