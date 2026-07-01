@extends('layouts.app')
@section('title', 'FAQ — 메디셀')

@section('content')
<div class="page-head"><div class="container"><h1>자주 묻는 질문</h1></div></div>
<div class="container" style="padding:26px 20px;max-width:860px">
    @forelse($faqs as $category => $items)
        <h3 style="font-size:17px;font-weight:800;color:var(--navy-800);margin:24px 0 12px">{{ $category }}</h3>
        @foreach($items as $faq)
            <details class="form-card" style="margin-bottom:10px;padding:0">
                <summary style="padding:16px 20px;cursor:pointer;font-weight:700;list-style:none;display:flex;align-items:center;gap:10px">
                    <span style="color:var(--red);font-weight:900">Q</span> {{ $faq->question }}
                </summary>
                <div style="padding:0 20px 18px 44px;color:var(--slate-600);font-size:14px;line-height:1.8">{{ $faq->answer }}</div>
            </details>
        @endforeach
    @empty
        <p class="muted">등록된 FAQ가 없습니다.</p>
    @endforelse
</div>
@endsection
