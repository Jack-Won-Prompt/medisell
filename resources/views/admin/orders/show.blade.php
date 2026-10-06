@extends('layouts.admin')
@section('title', '주문상세')
@section('heading', '주문상세 · '.$order->order_no)

@section('content')

{{-- 요약 --}}
<div class="adm-card" style="padding:16px 20px;margin-bottom:16px;display:flex;flex-wrap:wrap;gap:10px 18px;align-items:center;font-size:13.5px">
    <b style="font-size:16px">{{ $order->order_no }}</b>
    <span class="status-pill st-{{ $order->status }}">{{ $order->statusLabel() }}</span>
    <span>{{ $order->buyer_hospital ?: ($order->user?->company_name ?: ($order->user?->name ?? $order->receiver_name)) }}</span>
    <b style="color:var(--a-navy)">{{ number_format($order->total) }}원</b>
    <span class="muted">{{ \App\Support\OrderPdf::payLabel($order) }}</span>
    <span class="muted" style="margin-left:auto">주문 {{ $order->created_at->format('Y.m.d H:i') }}@if($order->paid_at) · 결제 {{ $order->paid_at->format('Y.m.d H:i') }}@endif</span>
    <a href="{{ route('admin.orders.index') }}" class="abtn abtn-ghost abtn-sm">목록으로</a>
</div>

<div class="utabs" data-tabs="order" data-default="info">
    <button data-tab="info">주문 정보</button>
    <button data-tab="status">상태 · 배송</button>
    <button data-tab="evidence">증빙 (세금계산서·현금영수증)</button>
    <button data-tab="sms">안내 문자 <span class="muted" style="font-weight:400">{{ $order->smsLogs()->count() }}</span></button>
    <button data-tab="payment">결제 · 취소</button>
</div>

<div class="upanel" data-panel="info">
    <div class="ugrid">
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
                    @if($order->agent_id)
                        <div style="background:#eef4ff;border-radius:8px;padding:8px 12px;margin-bottom:8px;line-height:1.7">
                            <b>🛒 대행 구매</b> — 대행자 {{ $order->agent?->name }}<br>
                            <b>구매자</b> {{ $order->buyer_hospital }} · {{ $order->buyer_name }} · {{ $order->buyer_phone }}<br>
                            <b>캐쉬백</b> {{ number_format($order->cashback_amount) }}원
                            @if($order->cashback)<span class="status-pill st-{{ $order->cashback->status==='paid'?'done':($order->cashback->status==='cancelled'?'cancelled':'pending') }}" style="margin-left:4px">{{ $order->cashback->statusLabel() }}</span>@endif
                        </div>
                    @endif
                    <b>받는분</b> {{ $order->receiver_name }} · {{ $order->receiver_phone }}<br>
                    <b>주소</b> ({{ $order->postcode }}) {{ $order->address1 }} {{ $order->address2 }}<br>
                    @if($order->memo)<b>메모</b> {{ $order->memo }}<br>@endif
                    <b>입금</b> {{ $order->bank }} · 입금자명 {{ $order->depositor }}
                    @if($order->paid_at)<br><b>입금확인</b> <span style="color:var(--a-navy)">{{ $order->paid_at->format('Y.m.d H:i') }}</span>@endif
                </div>
            </div>
    </div>
</div>

<div class="upanel" data-panel="status">
    <div class="ugrid">
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
                </div>
            </div>

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
    </div>
</div>

<div class="upanel" data-panel="evidence">
    <div class="ugrid">
        <div class="adm-card">
                <div class="h">전자세금계산서</div>
                <div style="padding:20px">
                    @php($business = $order->user && $order->user->member_type==='business' && $order->user->biz_no)
                    @php($tis = $order->taxInvoices()->get())
                    @if(! $business)
                        <p class="muted" style="font-size:13.5px">사업자(병원) 회원 주문만 발행 가능합니다. (사업자등록번호 필요)</p>
                    @else
                        @foreach($tis as $ti)
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
                        @endforeach
                        {{-- 유효한(발행/시뮬레이트) 계산서가 없으면 발행 버튼 — 취소·실패 뒤 재발행 포함 --}}
                        @unless($tis->whereIn('status', ['issued','simulated'])->count())
                            @php($canIssue = in_array($order->status, ['paid','preparing','shipped','done']))
                            @if($order->isCardPayment())
                                <p class="muted" style="font-size:13.5px">카드 결제 주문은 카드매출전표가 증빙이라 세금계산서를 발행하지 않습니다.</p>
                            @elseif($canIssue)
                                <form method="POST" action="{{ route('admin.orders.taxinvoice', $order) }}">
                                    @csrf
                                    <div style="font-size:13px;color:#6b7794;margin-bottom:10px">
                                        공급받는자: {{ $order->user->company_name ?? '-' }} ({{ $order->user->biz_no }})<br>
                                        @unless(config('popbill.simulate'))<span style="color:#e0322d">※ 실발행 모드 — 실제 세금계산서가 발행됩니다.</span>
                                        @else<span class="pill pill-w">시뮬레이트 모드</span>@endunless
                                    </div>
                                    <button class="abtn abtn-pri" style="width:100%;justify-content:center">세금계산서 {{ $tis->count() ? '재발행' : '발행' }}</button>
                                </form>
                            @else
                                <p class="muted" style="font-size:13.5px">결제완료(입금확인) 이후 발행할 수 있습니다.</p>
                            @endif
                        @endunless
                    @endif
                </div>
            </div>

        <div class="adm-card">
                <div class="h">현금영수증</div>
                <div style="padding:20px">
                    @php($crs = $order->cashReceipts()->get())
                    @foreach($crs as $cr)
                        <div style="border:1px solid var(--a-line);border-radius:10px;padding:12px 14px;margin-bottom:10px;font-size:13px;line-height:1.8">
                            <div style="display:flex;justify-content:space-between;align-items:center">
                                <b>{{ $cr->trade_usage }} · {{ $cr->maskedIdentity() }}</b>
                                @if($cr->status==='cancelled')<span class="pill pill-n">취소</span>
                                @elseif($cr->status==='simulated')<span class="pill pill-w">시뮬레이트</span>
                                @elseif($cr->status==='issued')<span class="pill pill-y">발행완료</span>
                                @else<span class="pill pill-n">실패</span>@endif
                            </div>
                            공급가 {{ number_format($cr->supply_amount) }} · 세액 {{ number_format($cr->tax_amount) }} · 합계 <b>{{ number_format($cr->total_amount) }}</b>원<br>
                            승인번호 {{ $cr->confirm_num ?? '-' }} · {{ optional($cr->issued_at)->format('Y.m.d H:i') }}
                            @if($cr->error_message)<div style="color:#e0322d;font-size:12px">{{ $cr->error_message }}</div>@endif
                            @if(in_array($cr->status,['issued','simulated']))
                                <div style="display:flex;gap:6px;margin-top:8px">
                                    @if($cr->status==='issued')
                                        <a href="{{ route('admin.cashreceipt.popup', $cr) }}" target="_blank" class="abtn abtn-ghost abtn-sm">원본보기</a>
                                    @endif
                                    <form method="POST" action="{{ route('admin.cashreceipt.cancel', $cr) }}" onsubmit="return confirm('현금영수증을 취소하시겠습니까? (취소거래 현금영수증이 발행됩니다)')">
                                        @csrf @method('DELETE')
                                        <button class="abtn abtn-red abtn-sm">발행취소</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @endforeach

                    @unless($crs->whereIn('status', ['issued','simulated'])->count())
                        @if($order->isCardPayment())
                            <p class="muted" style="font-size:13.5px">카드 결제 주문은 현금영수증을 발행하지 않습니다.</p>
                        @elseif($order->taxInvoices()->whereIn('status', ['issued','simulated'])->exists())
                            <p class="muted" style="font-size:13.5px">세금계산서가 발행된 주문입니다.</p>
                        @elseif(! in_array($order->status, ['paid','preparing','shipped','done']))
                            <p class="muted" style="font-size:13.5px">
                                결제완료(입금확인) 이후 발행할 수 있습니다.
                                @if($order->cash_receipt_type)<br>고객 신청: {{ $order->cash_receipt_type==='income' ? '소득공제용' : '지출증빙용' }} · 입금 확인 시 자동 발행@endif
                            </p>
                        @else
                            <form method="POST" action="{{ route('admin.orders.cashreceipt', $order) }}">
                                @csrf
                                <div style="display:flex;gap:8px;margin-bottom:8px">
                                    <select name="type" class="aselect" style="width:140px">
                                        <option value="income" {{ $order->cash_receipt_type!=='expense' ? 'selected' : '' }}>소득공제용</option>
                                        <option value="expense" {{ $order->cash_receipt_type==='expense' ? 'selected' : '' }}>지출증빙용</option>
                                    </select>
                                    <input type="text" name="identity" class="ainput" style="flex:1" value="{{ $order->cash_receipt_identity }}" placeholder="휴대폰번호 / 사업자번호" required>
                                </div>
                                <div style="font-size:12.5px;color:#6b7794;margin-bottom:8px">
                                    @if(config('popbill.cashbill.simulate', true))<span class="pill pill-w">시뮬레이트 모드</span>
                                    @else<span style="color:#e0322d">※ 실발행 모드 — 국세청에 신고되는 현금영수증이 발행됩니다.</span>@endif
                                </div>
                                <button class="abtn abtn-pri" style="width:100%;justify-content:center">현금영수증 {{ $crs->count() ? '재발행' : '발행' }}</button>
                            </form>
                        @endif
                    @endunless
                </div>
            </div>
    </div>
</div>

<div class="upanel" data-panel="sms">
    <div style="max-width:900px">
        <div class="adm-card">
                <div class="h">안내 문자 <span style="font-weight:400;font-size:12px;color:#97a0b8">· {{ ['simulate'=>'시뮬레이트','redirect'=>'테스트번호 발송','live'=>'실발송'][config('popbill.sms.mode','simulate')] ?? config('popbill.sms.mode') }} 모드</span></div>
                <div style="padding:14px 20px;font-size:13px">
                    @forelse($order->smsLogs()->get() as $log)
                        <div style="border-bottom:1px solid var(--a-line);padding:8px 0">
                            <b>{{ $log->kindLabel() }}</b> · {{ $log->receiver }} · {{ $log->msg_type }}
                            <span class="pill {{ in_array($log->status,['sent','redirected']) ? 'pill-y' : ($log->status==='simulated' ? 'pill-w' : 'pill-n') }}">{{ $log->statusLabel() }}</span>
                            <span style="color:#97a0b8;font-size:12px">{{ $log->created_at->format('m.d H:i') }}</span>
                            <div style="white-space:pre-line;color:#4b5570;font-size:12.5px;margin-top:4px">{{ $log->content }}</div>
                            @if($log->error_message)<div style="color:#e0322d;font-size:12px">{{ $log->error_message }}</div>@endif
                        </div>
                    @empty
                        <p class="muted" style="margin:0">보낸 문자가 없습니다.</p>
                    @endforelse
                </div>
            </div>
    </div>
</div>

<div class="upanel" data-panel="payment">
    <div style="max-width:900px">
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
</div>
@endsection
