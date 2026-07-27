{{--
    인터넷 최저가 비교 — 쿠팡 · 네이버 · 일반 의료소모품몰 채널별 최저가 Top 3.
    $compare : MarketPriceService::compare() 결과 / $sell : 현재 회원에게 적용되는 판매가

    is_sample(실연동 키 미설정 → 모의값)일 때는 실제 시세가 아니므로
    관리자·로컬 환경에서만 "예시" 배지와 함께 미리보기로 노출한다.
--}}
@php
    $__rows = $compare['rows'] ?? [];
    $__sample = $compare['is_sample'] ?? false;
    $__preview = $__sample && (app()->environment('local') || (auth()->user()?->is_admin ?? false));
@endphp

@if($__rows && (! $__sample || $__preview))
    <div class="mkt-compare">
        <div class="mkt-head">
            <span class="mkt-title"><x-icon name="search" :size="15"/> 인터넷 최저가 비교</span>
            @if($__preview)
                <span class="mkt-sample" title="COUPANG_SERP_API_KEY 미설정 — 실제 시세가 아닌 예시 데이터입니다">예시 데이터</span>
            @elseif(! empty($compare['fetched_at']))
                <span class="mkt-date">{{ \Illuminate\Support\Carbon::parse($compare['fetched_at'])->format('Y.m.d') }} 기준</span>
            @endif
        </div>

        <ul class="mkt-list">
            @foreach($__rows as $r)
                <li>
                    <span class="mkt-ch mkt-ch-{{ $r['channel'] }}">{{ $r['channel_label'] }}</span>
                    <span class="mkt-seller">{{ \Illuminate\Support\Str::limit($r['seller'] ?: $r['channel_label'], 14) }}</span>
                    <span class="mkt-price">{{ number_format($r['price']) }}원</span>
                    @if($r['diff'] > 0)
                        <span class="mkt-diff up">+{{ number_format($r['diff']) }}</span>
                    @elseif($r['diff'] < 0)
                        <span class="mkt-diff down">{{ number_format($r['diff']) }}</span>
                    @else
                        <span class="mkt-diff">동일</span>
                    @endif
                    @if($r['url'])
                        <a href="{{ $r['url'] }}" target="_blank" rel="noopener nofollow">보기 <x-icon name="chevron-right" :size="13"/></a>
                    @endif
                </li>
            @endforeach
        </ul>

        @if($compare['is_lowest'] ?? false)
            <div class="mkt-foot best">
                <x-icon name="check" :size="14"/>
                메디셀이 최저가입니다
                @if(($compare['saving'] ?? 0) > 0)
                    — 타사 최저가보다 <b>{{ number_format($compare['saving']) }}원</b> 저렴
                @endif
            </div>
        @endif

        <div class="mkt-note">
            ※ 외부 검색 결과 기준이며 판매처 사정에 따라 실제 결제금액(배송비·옵션 포함)은 다를 수 있습니다.
            병원 전용가는 로그인 후 확인하실 수 있습니다.
        </div>
    </div>
@endif
