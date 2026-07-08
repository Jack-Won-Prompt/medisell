@php($activeCat = $category ?? null)
<aside class="side">
    <div class="side-card">
        <div class="sc-head"><x-icon name="grid" :size="18"/> 카테고리</div>
        <ul class="side-cats">
            @foreach($navCategories as $cat)
                @php($isOn = $activeCat && ($activeCat->id === $cat->id || $activeCat->parent_id === $cat->id))
                <li class="{{ $isOn ? 'on' : '' }}">
                    <a href="{{ route('catalog.category', $cat->slug) }}"><x-icon :name="$cat->icon ?? 'box'"/>{{ $cat->name }}</a>
                    @if($isOn && $cat->children->count())
                        <div class="subs">
                            @foreach($cat->children as $sub)
                                <a href="{{ route('catalog.category', $sub->slug) }}" style="{{ $activeCat->id === $sub->id ? 'color:var(--navy-800);font-weight:600' : '' }}">{{ $sub->name }}</a>
                            @endforeach
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>

    <a href="{{ route('community.inquiry', ['type' => 'quote']) }}" class="side-banner" style="background:linear-gradient(135deg,var(--navy-800),var(--navy-600))">
        <small>대량구매 병의원이라면</small>
        <strong>견적문의 바로가기 →</strong>
    </a>
</aside>
