@if(($recentProducts ?? collect())->isNotEmpty())
<aside class="quick-recent" aria-label="최근 본 상품">
    <div class="qr-head">최근<br>본상품</div>
    <ul>
        @foreach($recentProducts->take(5) as $rp)
            <li>
                <a href="{{ route('catalog.show', $rp->slug) }}" title="{{ $rp->name }}">
                    @if($rp->thumbnail)
                        <img src="{{ $rp->thumbnail }}" alt="{{ $rp->name }}" loading="lazy">
                    @else
                        <span class="qr-ph"><x-icon :name="$rp->category->icon ?? 'box'" :size="22"/></span>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>
    <a href="#" class="qr-top" onclick="window.scrollTo({top:0,behavior:'smooth'});return false" aria-label="맨 위로"><x-icon name="arrow-right" :size="16"/></a>
</aside>
@endif
