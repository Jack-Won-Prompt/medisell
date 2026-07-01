<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    public function index(Request $request)
    {
        $query = Inquiry::latest();
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        $inquiries = $query->paginate(15)->withQueryString();

        return view('admin.inquiries.index', [
            'inquiries' => $inquiries,
            'types'     => Inquiry::TYPES,
        ]);
    }

    public function show(Inquiry $inquiry)
    {
        return view('admin.inquiries.show', compact('inquiry'));
    }

    public function answer(Request $request, Inquiry $inquiry)
    {
        $data = $request->validate([
            'answer' => ['required', 'string', 'max:5000'],
        ]);
        $inquiry->update([
            'answer'      => $data['answer'],
            'status'      => 'answered',
            'answered_at' => now(),
        ]);

        return back()->with('ok', '답변이 등록되었습니다.');
    }

    public function destroy(Inquiry $inquiry)
    {
        $inquiry->delete();

        return redirect()->route('admin.inquiries.index')->with('ok', '문의가 삭제되었습니다.');
    }
}
