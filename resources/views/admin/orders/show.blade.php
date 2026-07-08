@extends('layouts.admin')
@section('title', '주문상세')
@section('heading', '주문상세 · '.$order->order_no)

@section('content')
<div style="display:grid;grid-template-columns:1.5fr 1fr;gap:20px;align-items:start">
    <div>
        <div class="adm-card">
            <div class="h">주문 상품</div>
            <table class="atable">
                <thead><tr><th>상품</th><th>단가</th><th>수량</th><th>합계</th></tr></thead>
                <tbody>
                @foreach($order->items as $it)
                    <tr><td>{{ $it->product_name }}</td><td>{{ number_format($it->price) }}원</td><td>{{ $it->quantity }}</td><td><b>{{ number_format($it->subtotal) }}원</b></td></tr>
                @endforeach
                </tbody>
            </table>
            <div style="padding:16px 20px;border-top:1px solid var(--a-line)">
                <div style="display:flex;justify-content:space-between;padding:4px 0;font-size:13.5px"><span>상품금액</span><span>{{ number_format($order->subtotal) }}원</span></div>
                <div style="display:flex;justify-content:space-between;padding:4px 0;font-size:13.5px"><span>배송비</span><span>{{ number_format($order->shipping_fee) }}원</span></div>
                @if($order->discount)<div style="display:flex;justify-content:space-between;padding:4px 0;font-size:13.5px;color:#e0322d"><span>쿠폰 할인{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</span><span>-{{ number_format($order->discount) }}원</span></div>@endif
                @if($order->point_used)<div style="display:flex;justify-content:space-between;padding:4px 0;font-size:13.5px"><span>적립금 사용</span><span>-{{ number_format($order->point_used) }}원</span></div>@endif
                <div style="display:flex;justify-content:space-between;padding:8px 0 0;font-size:17px;font-weight:800;color:var(--a-navy);border-top:2px solid var(--a-ink);margin-top:6px"><span>결제금액</span><span>{{ number_format($order->total) }}원</span></div>
            </div>
        </div>

        <div class="adm-card">
            <div class="h">배송 / 결제 정보</div>
            <div style="padding:18px 20px;font-size:14px;line-height:2">
                <b>받는분</b> {{ $order->receiver_name }} · {{ $order->receiver_phone }}<br>
                <b>주소</b> ({{ $order->postcode }}) {{ $order->address1 }} {{ $order->address2 }}<br>
                @if($order->memo)<b>메모</b> {{ $order->memo }}<br>@endif
                <b>입금</b> {{ $order->bank }} · 입금자명 {{ $order->depositor }}
                @if($order->paid_at)<br><b>입금확인</b> <span style="color:var(--a-navy)">{{ $order->paid_at->format('Y.m.d H:i') }}</span>@endif
            </div>
        </div>
    </div>

    <div class="adm-card">
        <div class="h">주문 상태 변경</div>
        <div style="padding:20px">
            <div style="margin-bottom:14px"><span class="status-pill st-{{ $order->status }}" style="font-size:14px;padding:7px 16px">{{ $order->statusLabel() }}</span></div>
            <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                @csrf @method('PUT')
                <div class="afield">
                    <label>상태</label>
                    <select name="status" class="aselect">
                        @foreach($statuses as $k => $label)
                            <option value="{{ $k }}" {{ $order->status===$k ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="ahint">'입금확인' → 적립금 자동지급 / '취소' → 재고복구·적립금정산·(토스결제 시) 자동환불</div>
                </div>
                <button class="abtn abtn-pri" style="width:100%;justify-content:center">상태 변경</button>
            </form>
            <a href="{{ route('admin.orders.index') }}" class="abtn abtn-ghost" style="width:100%;justify-content:center;margin-top:10px">목록으로</a>
        </div>
    </div>
</div>

{{-- 배송(송장) 관리 + 취소정보 --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;margin-top:20px">
    <div class="adm-card">
        <div class="h">배송 / 송장</div>
        <div style="padding:20px">
            @if($order->tracking_no)
                <div style="background:#f7f9fc;border-radius:10px;padding:14px;margin-bottom:14px;font-size:13.5px;line-height:1.9">
                    <b>택배사</b> {{ $order->courier }}<br>
                    <b>송장번호</b> {{ $order->tracking_no }}<br>
                    <b>발송일</b> {{ optional($order->shipped_at)->format('Y.m.d H:i') }}
                </div>
            @endif
            <form method="POST" action="{{ route('admin.orders.shipping', $order) }}">
                @csrf @method('PUT')
                <div class="afield">
                    <label>택배사</label>
                    <select name="courier" class="aselect">
                        @foreach(['CJ대한통운','한진택배','롯데택배','우체국택배','로젠택배','쿠팡'] as $c)
                            <option value="{{ $c }}" {{ $order->courier===$c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="afield"><label>송장번호</label><input type="text" name="tracking_no" class="ainput" value="{{ $order->tracking_no }}"></div>
                <button class="abtn abtn-pri" style="width:100%;justify-content:center">{{ $order->tracking_no ? '송장 수정' : '송장 등록(배송중)' }}</button>
            </form>
        </div>
    </div>

    <div class="adm-card">
        <div class="h">전자세금계산서</div>
        <div style="padding:20px">
            @php($business = $order->user && $order->user->member_type==='business' && $order->user->biz_no)
            @php($tis = $order->taxInvoices()->get())
            @if(! $business)
                <p class="muted" style="font-size:13.5px">사업자(병원) 회원 주문만 발행 가능합니다. (사업자등록번호 필요)</p>
            @else
                @forelse($tis as $ti)
                    <div style="border:1px solid var(--a-line);border-radius:10px;padding:12px 14px;margin-bottom:10px;font-size:13px;line-height:1.8">
                        <div style="display:flex;justify-content:space-between;align-items:center">
                            <b>{{ $ti->kindLabel() }}</b>
                            @if($ti->status==='cancelled')<span class="pill pill-n">취소</span>
                            @elseif($ti->status==='simulated')<span class="pill pill-w">시뮬레이트</span>
                            @elseif($ti->status==='issued')<span class="pill pill-y">발행완료</span>
                            @else<span class="pill pill-n">실패</span>@endif
                        </div>
                        공급가 {{ number_format($ti->supply_amount) }} · 세액 {{ number_format($ti->tax_amount) }} · 합계 <b>{{ number_format($ti->total_amount) }}</b>원<br>
                        승인번호 {{ $ti->nts_confirm_num ?? '-' }} · {{ optional($ti->issued_at)->format('Y.m.d H:i') }}
                        @if($ti->error_message)<div style="color:#e0322d;font-size:12px">{{ $ti->error_message }}</div>@endif
                        @if(in_array($ti->status,['issued','simulated']))
                            <div style="display:flex;gap:6px;margin-top:8px">
                                @if($ti->status==='issued')
                                    <a href="{{ route('admin.taxinvoice.popup', $ti) }}" target="_blank" class="abtn abtn-ghost abtn-sm">원본보기</a>
                                @endif
                                <form method="POST" action="{{ route('admin.taxinvoice.cancel', $ti) }}" onsubmit="return confirm('세금계산서를 취소하시겠습니까?')">
                                    @csrf @method('DELETE')
                                    <button class="abtn abtn-red abtn-sm">발행취소</button>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    @php($canIssue = in_array($order->status, ['paid','preparing','shipped','done']))
                    @if($canIssue)
                        <form method="POST" action="{{ route('admin.orders.taxinvoice', $order) }}">
                            @csrf
                            <div style="font-size:13px;color:#6b7794;margin-bottom:10px">
                                공급받는자: {{ $order->user->company_name ?? '-' }} ({{ $order->user->biz_no }})<br>
                                @unless(config('popbill.simulate'))<span style="color:#e0322d">※ 실발행 모드 — 실제 세금계산서가 발행됩니다.</span>
                                @else<span class="pill pill-w">시뮬레이트 모드</span>@endunless
                            </div>
                            <button class="abtn abtn-pri" style="width:100%;justify-content:center">세금계산서 발행</button>
                        </form>
                    @else
                        <p class="muted" style="font-size:13.5px">결제완료(입금확인) 이후 발행할 수 있습니다.</p>
                    @endif
                @endforelse
            @endif
        </div>
    </div>

    <div class="adm-card">
        <div class="h">결제 / 취소</div>
        <div style="padding:20px;font-size:13.5px;line-height:1.9">
            <b>결제수단</b> {{ $order->payment_method==='toss' ? '토스('.($order->pay_method ?? '카드/가상계좌').')' : '무통장입금' }}<br>
            @if($order->payment_key)<b>결제키</b> <span style="font-size:12px;color:#97a0b8">{{ \Illuminate\Support\Str::limit($order->payment_key, 24) }}</span><br>@endif
            @if($order->paid_at)<b>결제완료</b> <span style="color:var(--a-navy)">{{ $order->paid_at->format('Y.m.d H:i') }}</span><br>@endif
            @if($order->status==='cancelled')
                <hr style="border:0;border-top:1px solid var(--a-line);margin:10px 0">
                <b style="color:#e0322d">취소완료</b> {{ optional($order->cancelled_at)->format('Y.m.d H:i') }}<br>
                <b>사유</b> {{ $order->cancel_reason }}<br>
                @if($order->payment_key)<span style="color:#16a34a">※ 토스 결제 자동 환불 처리됨</span>@endif
            @endif
        </div>
    </div>
</div>
@endsection
