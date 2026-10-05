@extends('layouts.admin')
@section('title', '토스 결제 상세')
@section('heading', '토스페이먼츠 결제 상세')

@section('content')
<div style="margin-bottom:12px"><a href="{{ route('admin.toss.index') }}" class="abtn abtn-ghost abtn-sm">← 목록</a></div>
@include('admin.toss._detail')
@endsection
