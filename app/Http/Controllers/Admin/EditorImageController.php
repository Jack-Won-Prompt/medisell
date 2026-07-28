<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 리치 에디터(Quill) 본문 인라인 이미지 업로드.
 *
 * 툴바 이미지 버튼과 클립보드 붙여넣기가 같은 엔드포인트를 쓴다.
 * 파셜(_quill-image-resize)이 FormData 의 image 필드로 보내고 {url} 을 기대한다.
 * 관리자 그룹(auth+admin) 안에 있어 별도 권한 검사는 두지 않는다.
 */
class EditorImageController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:8192'],  // 8MB
        ], [
            'image.required' => '이미지 파일이 없습니다.',
            'image.image'    => '이미지 파일만 올릴 수 있습니다.',
            'image.max'      => '이미지는 8MB 이하만 올릴 수 있습니다.',
            'image.mimes'    => 'jpg·png·gif·webp 형식만 올릴 수 있습니다.',
        ]);

        // 상품 썸네일 업로드와 같은 규칙(public 디스크 + asset)을 따른다
        $path = $request->file('image')->store('uploads/editor/'.date('Ym'), 'public');

        return response()->json(['url' => asset('storage/'.$path)]);
    }
}
