{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <title>{{ $site['name'] ?? '메디셀' }} — {{ $site['tagline'] ?? '의료소모품 전문 쇼핑몰' }}</title>
    <link>{{ $home }}</link>
    <description>의료소모품 전문 쇼핑몰 메디셀 — 거즈·주사기·수액·소독·글러브 등 병의원 의료소모품</description>
    <language>ko</language>
    <lastBuildDate>{{ $built->toRfc2822String() }}</lastBuildDate>
    <atom:link href="{{ $self }}" rel="self" type="application/rss+xml"/>
@foreach($items as $i)
    <item>
        <title>{{ $i['title'] }}</title>
        <link>{{ $i['link'] }}</link>
        <guid isPermaLink="true">{{ $i['link'] }}</guid>
        <category>{{ $i['category'] }}</category>
        <description>{{ $i['desc'] }}</description>
@if($i['date'])
        <pubDate>{{ $i['date']->toRfc2822String() }}</pubDate>
@endif
@if($i['image'])
        <enclosure url="{{ $i['image'] }}" type="image/jpeg" length="0"/>
@endif
    </item>
@endforeach
</channel>
</rss>
