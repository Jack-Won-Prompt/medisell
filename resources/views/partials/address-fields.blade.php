@php($a = $addr ?? null)
<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
    <div class="field"><label>배송지명</label><input type="text" name="label" class="input" value="{{ old('label', $a->label ?? '') }}" placeholder="예) 병원, 집"></div>
    <div class="field"><label>받는분 <span class="req">*</span></label><input type="text" name="receiver_name" class="input" value="{{ old('receiver_name', $a->receiver_name ?? auth()->user()->name) }}" required></div>
</div>
<div class="field"><label>연락처 <span class="req">*</span></label><input type="text" name="receiver_phone" class="input" value="{{ old('receiver_phone', $a->receiver_phone ?? auth()->user()->phone) }}" required></div>
<div class="field" style="max-width:360px"><label>우편번호</label>
    <div style="display:flex;gap:8px">
        <input type="text" name="postcode" class="input js-postcode" value="{{ old('postcode', $a->postcode ?? '') }}" readonly placeholder="주소 찾기 클릭">
        <button type="button" class="btn btn-ghost" onclick="findAddr(this)" style="flex:none;white-space:nowrap">주소 찾기</button>
    </div>
</div>
<div class="field"><label>주소 <span class="req">*</span></label><input type="text" name="address1" class="input js-address1" value="{{ old('address1', $a->address1 ?? '') }}" required readonly placeholder="주소 찾기로 입력"></div>
<div class="field"><label>상세주소</label><input type="text" name="address2" class="input js-address2" value="{{ old('address2', $a->address2 ?? '') }}" placeholder="상세 주소 (동/호수 등)"></div>
<label class="acheck" style="display:flex;align-items:center;gap:6px;margin-top:6px"><input type="checkbox" name="is_default" value="1" {{ ($a->is_default ?? false) ? 'checked' : '' }}> 기본 배송지로 설정</label>
