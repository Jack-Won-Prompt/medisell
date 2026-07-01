@php($r = request()->route()->getName())
<nav class="my-nav">
    <a href="{{ route('mypage.index') }}" class="{{ $r==='mypage.index' ? 'on' : '' }}"><x-icon name="user"/> 마이페이지 홈</a>
    <a href="{{ route('mypage.orders') }}" class="{{ str_starts_with($r,'mypage.order') ? 'on' : '' }}"><x-icon name="package"/> 주문내역</a>
    <a href="{{ route('mypage.wishlist') }}" class="{{ $r==='mypage.wishlist' ? 'on' : '' }}"><x-icon name="heart"/> 관심상품</a>
    <a href="{{ route('mypage.points') }}" class="{{ $r==='mypage.points' ? 'on' : '' }}"><x-icon name="coin"/> 적립금</a>
    <a href="{{ route('mypage.coupons') }}" class="{{ $r==='mypage.coupons' ? 'on' : '' }}"><x-icon name="tag"/> 쿠폰함</a>
    <a href="{{ route('mypage.profile') }}" class="{{ $r==='mypage.profile' ? 'on' : '' }}"><x-icon name="tools"/> 회원정보수정</a>
    <a href="{{ route('cart.index') }}"><x-icon name="cart"/> 장바구니</a>
</nav>
