@extends('layouts.app')
@section('title', '쿠폰함 — 메디셀')

@section('content')
<div class="page-head"><div class="container"><h1>쿠폰함</h1></div></div>
<div class="container" style="padding-top:26px">
    <div class="my-layout">
        @include('partials.mynav')
        <div>
            <h3 style="font-size:18px;font-weight:700;margin-bottom:14px">사용 가능한 쿠폰 <span class="text-red">{{ $available->count() }}</span></h3>
            @if($available->count())
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:30px">
                    @foreach($available as $uc)
                        @php($c = $uc->coupon)
                        <div style="border:1px solid var(--line);border-radius:12px;overflow:hidden;display:flex">
                            <div style="background:var(--navy-800);color:#fff;padding:18px 20px;display:flex;flex-direction:column;justify-content:center;min-width:110px;text-align:center">
                                <div style="font-size:24px;font-weight:800">{{ $c->typeLabel() }}</div>
                                <div style="font-size:11px;opacity:.85">할인</div>
                            </div>
                            <div style="padding:14px 16px;flex:1">
                                <div style="font-weight:700;font-size:15px">{{ $c->name }}</div>
                                <div class="muted" style="font-size:12.5px;margin-top:4px">
                                    {{ number_format($c->min_order_amount) }}원 이상 사용<br>
                                    @if($c->ends_at)~{{ $c->ends_at->format('Y.m.d') }}까지@else기간 제한 없음@endif
                                </div>
                                <div style="margin-top:6px"><span class="chip" style="font-size:11px;padding:3px 8px">코드 {{ $c->code }}</span></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty" style="padding:50px 20px"><x-icon name="tag"/><h3>보유한 쿠폰이 없습니다</h3><p>발행되는 쿠폰은 이곳에 담깁니다.</p></div>
            @endif

            @if($used->count())
                <h3 style="font-size:16px;font-weight:700;margin:24px 0 12px;color:var(--slate-500)">사용 완료</h3>
                <table class="dtable">
                    <thead><tr><th>쿠폰</th><th>할인</th><th>사용일</th></tr></thead>
                    <tbody>
                    @foreach($used as $uc)
                        <tr style="opacity:.6"><td>{{ $uc->coupon->name }}</td><td>{{ $uc->coupon->typeLabel() }}</td><td>{{ $uc->used_at->format('Y.m.d') }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
