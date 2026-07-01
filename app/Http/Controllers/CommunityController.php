<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Inquiry;
use App\Models\Notice;
use App\Models\Review;
use Illuminate\Http\Request;

class CommunityController extends Controller
{
    public function notices()
    {
        $notices = Notice::orderByDesc('is_pinned')->latest('published_at')->paginate(15);

        return view('community.notices', compact('notices'));
    }

    public function notice(Notice $notice)
    {
        $notice->increment('views');

        return view('community.notice', compact('notice'));
    }

    public function reviews()
    {
        $reviews = Review::visible()->with('product')->latest()->paginate(15);

        return view('community.reviews', compact('reviews'));
    }

    public function faq(Request $request)
    {
        $faqs = Faq::orderBy('sort_order')->get()->groupBy('category');

        return view('community.faq', compact('faqs'));
    }

    public function qna()
    {
        $inquiries = Inquiry::latest()->paginate(15);

        return view('community.qna', compact('inquiries'));
    }

    public function inquiryForm(Request $request)
    {
        $type = $request->get('type', 'qna');

        return view('community.inquiry', ['type' => $type]);
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

        Inquiry::create($data + [
            'user_id'   => $request->user()?->id,
            'is_secret' => $request->boolean('is_secret'),
            'status'    => 'pending',
        ]);

        return redirect()->route('community.qna')->with('ok', '문의가 접수되었습니다. 빠르게 답변드리겠습니다.');
    }
}
