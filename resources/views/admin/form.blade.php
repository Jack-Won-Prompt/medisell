@extends('layouts.admin')
@section('title', $cfg['label'])
@section('heading', $cfg['label'].' '.($editing ? '수정' : '등록'))

@section('content')
<div class="adm-card" style="max-width:760px">
    <div style="padding:24px">
        <form method="POST" action="{{ $editing ? route('admin.update', [$cfg['key'], $item->id]) : route('admin.store', $cfg['key']) }}" enctype="multipart/form-data">
            @csrf
            @if($editing) @method('PUT') @endif

            @foreach($cfg['fields'] as $f)
                @php($name = $f['name'])
                @php($type = $f['type'] ?? 'text')
                @php($value = old($name, $item->{$name} ?? ''))
                <div class="afield">
                    @if($type !== 'checkbox')
                        <label>{{ $f['label'] }} @if($f['required'] ?? false)<span style="color:#e0322d">*</span>@endif</label>
                    @endif

                    @if($type === 'textarea')
                        <textarea name="{{ $name }}" class="atextarea" rows="{{ $f['rows'] ?? 4 }}">{{ $value }}</textarea>
                    @elseif($type === 'checkbox')
                        <label class="acheck"><input type="checkbox" name="{{ $name }}" value="1" {{ $value ? 'checked' : '' }}> {{ $f['label'] }}</label>
                    @elseif($type === 'select')
                        <select name="{{ $name }}" class="aselect">
                            <option value="">— 선택 —</option>
                            @foreach($options[$name] ?? [] as $optVal => $optLabel)
                                <option value="{{ $optVal }}" {{ (string)$value === (string)$optVal ? 'selected' : '' }}>{{ $optLabel }}</option>
                            @endforeach
                        </select>
                    @elseif($type === 'number')
                        <input type="number" name="{{ $name }}" class="ainput" value="{{ $value }}" step="any">
                    @elseif($type === 'date')
                        <input type="date" name="{{ $name }}" class="ainput" value="{{ $value ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d') : '' }}">
                    @elseif($type === 'datetime')
                        <input type="datetime-local" name="{{ $name }}" class="ainput" value="{{ $value ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d\TH:i') : '' }}">
                    @elseif($type === 'image')
                        @if($value)
                            <div style="margin-bottom:8px"><img src="{{ $value }}" alt="" style="max-height:120px;max-width:200px;border:1px solid var(--a-line);border-radius:8px;object-fit:contain;background:#fff"></div>
                        @endif
                        <input type="file" name="{{ $name }}" accept="image/*" class="ainput" style="padding:8px">
                        @if($value)
                            <label class="acheck" style="margin-top:6px"><input type="checkbox" name="{{ $name }}_clear" value="1"> 기존 이미지 삭제</label>
                        @endif
                    @else
                        <input type="text" name="{{ $name }}" class="ainput" value="{{ $value }}">
                    @endif

                    @if(!empty($f['hint']))<div class="ahint">{{ $f['hint'] }}</div>@endif
                    @error($name)<div class="aerr">{{ $message }}</div>@enderror
                </div>
            @endforeach

            <div style="display:flex;gap:10px;margin-top:8px">
                <a href="{{ route('admin.index', $cfg['key']) }}" class="abtn abtn-ghost">취소</a>
                <button class="abtn abtn-pri">{{ $editing ? '수정 저장' : '등록' }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
