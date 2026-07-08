@extends('layouts.app')
@section('title', '마이페이지 — 메디셀')

@section('content')
<div class="page-head"><div class="container"><h1>마이페이지</h1></div></div>
<div class="container" style="padding-top:26px">
    <div class="my-layout">
        @include('partials.mynav')
        <div>
            <div style="background:linear-gradient(120deg,var(--navy-800),var(--navy-600));color:#fff;border-radius:var(--radius);padding:24px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between">
                <div>
                    <div style="font-size:20px;font-weight:700">{{ $user->name }}님</div>
                    <div style="opacity:.85;font-size:13.5px;margin-top:4px">
                        @if($user->member_type==='business')
                            병원회원 · {{ $user->company_name }}
                            @if($user->biz_status==='approved')<span class="badge badge-new" style="margin-left:6px">승인완료</span>
                            @elseif($user->biz_status==='pending')<span class="badge badge-hot" style="margin-left:6px">승인대기</span>
                            @else<span class="badge badge-soldout" style="margin-left:6px">미승인</span>@endif
                        @else
                            일반회원
                        @endif
                    </div>
                </div>
                <div style="text-align:right">
                    <div style="font-size:12.5px;opacity:.85">보유 적립금</div>
                    <div style="font-size:26px;font-weight:800">{{ number_format($user->point) }}<span style="font-size:15px">원</span></div>
                </div>
            </div>

            <div class="stat-cards">
                <div class="stat-card"><span class="ic"><x-icon name="package"/></span><div><div class="v">{{ $orderCount }}</div><div class="l">총 주문</div></div></div>
                <div class="stat-card"><span class="ic"><x-icon name="cart"/></span><div><div class="v">{{ auth()->user()->cartItems()->count() }}</div><div class="l">장바구니</div></div></div>
                <div class="stat-card"><span class="ic"><x-icon name="coin"/></span><div><div class="v">{{ number_format($user->point) }}</div><div class="l">적립금(원)</div></div></div>
            </div>

            <div class="form-card">
                <h3><x-icon name="package"/> 최근 주문</h3>
                @if($recentOrders->count())
                    <table class="dtable" style="border:0">
                        <thead><tr><th>주문번호</th><th>주문일</th><th>금액</th><th>상태</th></tr></thead>
                        <tbody>
                        @foreach($recentOrders as $o)
                            <tr>
                                <td><a href="{{ route('mypage.order', $o) }}" style="font-weight:600;color:var(--navy-800)">{{ $o->order_no }}</a></td>
                                <td>{{ $o->created_at->format('Y.m.d') }}</td>
                                <td>{{ number_format($o->total) }}원</td>
                                <td><span class="status-pill st-{{ $o->status }}">{{ $o->statusLabel() }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="muted">주문 내역이 없습니다.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
