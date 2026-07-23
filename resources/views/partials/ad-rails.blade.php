@php($__ads = $sideAds ?? collect())
@if($__ads->isNotEmpty())
    @php($__left = $__ads->filter(fn ($a) => in_array($a->position, ['left', 'both']))->take(3))
    @php($__right = $__ads->filter(fn ($a) => in_array($a->position, ['right', 'both']))->reject(fn ($a) => $__left->contains('id', $a->id))->take(3))
    @php($__right = $__right->isEmpty() ? $__ads->filter(fn ($a) => in_array($a->position, ['right', 'both']))->take(3) : $__right)

    @foreach(['left' => $__left, 'right' => $__right] as $__side => $__list)
        @if($__list->isNotEmpty())
            <aside class="ad-rail ad-rail-{{ $__side }}" aria-label="추천 광고">
                <div class="ad-rail-label">AD · 추천상품</div>
                @foreach($__list as $ad)
                    <a class="ad-card"
                       @if($ad->link) href="{{ $ad->link }}" target="_blank" rel="noopener nofollow sponsored" @else href="javascript:void(0)" @endif>
                        @if($ad->badge)<span class="ad-badge">{{ $ad->badge }}</span>@endif
                        @if($ad->image)
                            <span class="ad-thumb"><img src="{{ $ad->image }}" alt="{{ $ad->title }}" loading="lazy"></span>
                        @else
                            <span class="ad-thumb ad-thumb-empty" @if($ad->bg_color) style="background:{{ $ad->bg_color }}" @endif><x-icon name="box" :size="36"/></span>
                        @endif
                        <span class="ad-body">
                            <span class="ad-title">{{ $ad->title }}</span>
                            @if($ad->subtitle)<span class="ad-sub">{{ $ad->subtitle }}</span>@endif
                            @if($ad->price)<span class="ad-price">{{ number_format($ad->price) }}<em>원~</em></span>@endif
                            <span class="ad-cta">자세히 보기 →</span>
                        </span>
                    </a>
                @endforeach
                <div class="ad-rail-foot">광고 · 제휴 상품</div>
            </aside>
        @endif
    @endforeach
@endif
