@extends('layouts.app')
@section('title', '배송지 관리 — 메디셀')

@section('content')
<div class="page-head"><div class="container"><h1>배송지 관리</h1></div></div>
<div class="container" style="padding-top:26px">
    <div class="my-layout">
        @include('partials.mynav')
        <div>
            @if(session('ok'))<div class="alert alert-ok" style="margin-bottom:14px">{{ session('ok') }}</div>@endif

            {{-- 저장된 배송지 목록 --}}
            @forelse($addresses as $a)
                <div class="form-card" style="margin-bottom:14px">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px">
                        <div>
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                                <b>{{ $a->label ?: '배송지' }}</b>
                                @if($a->is_default)<span class="badge badge-plan">기본 배송지</span>@endif
                            </div>
                            <div>{{ $a->receiver_name }} · {{ $a->receiver_phone }}</div>
                            <div class="muted" style="font-size:14px">({{ $a->postcode }}) {{ $a->address1 }} {{ $a->address2 }}</div>
                        </div>
                        <div style="display:flex;gap:6px;flex:none">
                            @unless($a->is_default)
                                <form method="POST" action="{{ route('mypage.address.default', $a) }}">@csrf<button class="btn btn-ghost btn-sm">기본설정</button></form>
                            @endunless
                            <button type="button" class="btn btn-ghost btn-sm" onclick="toggleEdit({{ $a->id }})">수정</button>
                            <form method="POST" action="{{ route('mypage.address.delete', $a) }}" onsubmit="return confirm('삭제하시겠습니까?')">
                                @csrf @method('DELETE')<button class="btn btn-red btn-sm">삭제</button>
                            </form>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('mypage.address.update', $a) }}" id="edit-{{ $a->id }}" style="display:none;margin-top:14px;border-top:1px solid var(--line);padding-top:14px">
                        @csrf @method('PUT')
                        @include('partials.address-fields', ['addr' => $a])
                        <div style="margin-top:12px"><button class="btn btn-primary">수정 저장</button></div>
                    </form>
                </div>
            @empty
                <div class="form-card muted" style="margin-bottom:14px">저장된 배송지가 없습니다. 아래에서 추가하세요.</div>
            @endforelse

            {{-- 새 배송지 추가 --}}
            <form method="POST" action="{{ route('mypage.address.store') }}" class="form-card">
                @csrf
                <h3><x-icon name="pin"/> 새 배송지 추가</h3>
                @include('partials.address-fields')
                <div style="margin-top:14px"><button class="btn btn-primary">배송지 추가</button></div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
<script>
function findAddr(el) {
    var form = el.closest('form');
    new daum.Postcode({
        oncomplete: function (d) {
            form.querySelector('.js-postcode').value = d.zonecode;
            form.querySelector('.js-address1').value = d.roadAddress || d.jibunAddress;
            form.querySelector('.js-address2').focus();
        }
    }).open();
}
function toggleEdit(id) {
    var f = document.getElementById('edit-' + id);
    f.style.display = (f.style.display === 'none' || !f.style.display) ? 'block' : 'none';
}
</script>
@endpush
