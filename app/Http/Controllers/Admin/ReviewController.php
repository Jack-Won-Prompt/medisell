<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with('product')->latest();
        if ($request->get('filter') === 'hidden') {
            $query->where('is_hidden', true);
        } elseif ($request->get('filter') === 'visible') {
            $query->where('is_hidden', false);
        }
        if ($q = $request->get('q')) {
            $query->where(fn ($w) => $w->where('title', 'like', "%{$q}%")
                ->orWhere('body', 'like', "%{$q}%")
                ->orWhere('author_name', 'like', "%{$q}%"));
        }
        $reviews = $query->paginate(20)->withQueryString();

        return view('admin.reviews.index', compact('reviews'));
    }

    /** 노출/숨김 토글 */
    public function toggle(Review $review)
    {
        $review->update(['is_hidden' => ! $review->is_hidden]);

        return back()->with('ok', $review->is_hidden ? '후기를 숨겼습니다.' : '후기를 노출했습니다.');
    }

    public function destroy(Review $review)
    {
        $review->delete();

        return back()->with('ok', '후기를 삭제했습니다.');
    }
}
