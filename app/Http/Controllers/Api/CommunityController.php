<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Inquiry;
use App\Models\Notice;
use App\Models\Review;
use App\Support\ApiSerializer as S;
use Illuminate\Http\Request;

class CommunityController extends Controller
{
    public function notices(Request $request)
    {
        $notices = Notice::orderByDesc('is_pinned')->latest('published_at')->paginate(15);

        return response()->json([
            'notices' => collect($notices->items())->map(fn ($n) => S::noticeBrief($n)),
            'meta' => ['current_page' => $notices->currentPage(), 'last_page' => $notices->lastPage(), 'has_more' => $notices->hasMorePages()],
        ]);
    }

    public function notice(Request $request, Notice $notice)
    {
        $notice->increment('views');

        return response()->json([
            'notice' => array_merge(S::noticeBrief($notice), ['body' => $notice->body]),
        ]);
    }

    public function reviews(Request $request)
    {
        $reviews = Review::visible()->with('product')->latest()->paginate(15);

        return response()->json([
            'reviews' => collect($reviews->items())->map(fn ($r) => array_merge(S::review($r), [
                'product' => $r->product ? [
                    'id' => $r->product->id, 'name' => $r->product->name, 'slug' => $r->product->slug,
                    'thumbnail' => S::image($r->product->thumbnail, $request),
                ] : null,
            ])),
            'meta' => ['current_page' => $reviews->currentPage(), 'last_page' => $reviews->lastPage(), 'has_more' => $reviews->hasMorePages()],
        ]);
    }

    public function faq(Request $request)
    {
        $groups = Faq::orderBy('sort_order')->get()->groupBy('category');

        return response()->json([
            'groups' => $groups->map(fn ($items, $cat) => [
                'category' => $cat,
                'items' => $items->map(fn ($f) => [
                    'id' => $f->id, 'question' => $f->question, 'answer' => $f->answer,
                ])->values(),
            ])->values(),
        ]);
    }

    public function qna(Request $request)
    {
        $items = Inquiry::latest()->paginate(15);
        $uid = $request->user()?->id;

        return response()->json([
            'inquiries' => collect($items->items())->map(function ($q) use ($uid) {
                $mine = $uid && $q->user_id === $uid;
                $locked = $q->is_secret && ! $mine;

                return [
                    'id'      => $q->id,
                    'type'    => $q->type,
                    'name'    => $locked ? '비공개' : $q->name,
                    'subject' => $locked ? '🔒 비밀글입니다.' : $q->subject,
                    'body'    => $locked ? null : $q->body,
                    'status'  => $q->status,
                    'answer'  => $locked ? null : $q->answer,
                    'is_secret' => (bool) $q->is_secret,
                    'is_mine' => $mine,
                    'date'    => $q->created_at?->format('Y-m-d'),
                ];
            }),
            'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'has_more' => $items->hasMorePages()],
        ]);
    }

    public function inquiryStore(Request $request)
    {
        $data = $request->validate([
            'type'    => ['required', 'in:quote,qna,request'],
            'name'    => ['required', 'string', 'max:50'],
            'phone'   => ['nullable', 'string', 'max:30'],
            'email'   => ['nullable', 'email', 'max:100'],
            'subject' => ['required', 'string', 'max:150'],
            'body'    => ['required', 'string', 'max:3000'],
            'is_secret' => ['nullable', 'boolean'],
        ]);

        $inquiry = Inquiry::create($data + [
            'user_id'   => $request->user()?->id,
            'is_secret' => $request->boolean('is_secret'),
            'status'    => 'pending',
        ]);

        return response()->json([
            'message' => '문의가 접수되었습니다. 빠르게 답변드리겠습니다.',
            'id'      => $inquiry->id,
        ], 201);
    }
}
