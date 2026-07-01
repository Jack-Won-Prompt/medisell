<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\User;
use App\Models\UserCoupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    /** 발행 화면 */
    public function issueForm(Coupon $coupon)
    {
        $issued = $coupon->userCoupons()->with('user')->latest()->get()
            ->filter(fn ($uc) => $uc->user !== null);

        return view('admin.coupons.issue', compact('coupon', 'issued'));
    }

    /** 발행 실행 */
    public function issue(Request $request, Coupon $coupon)
    {
        $data = $request->validate([
            'target' => ['required', 'in:all,business,general,emails'],
            'emails' => ['required_if:target,emails', 'nullable', 'string'],
        ]);

        $users = match ($data['target']) {
            'all'      => User::where('is_admin', false)->get(),
            'business' => User::where('member_type', 'business')->get(),
            'general'  => User::where('member_type', 'general')->where('is_admin', false)->get(),
            'emails'   => User::whereIn('email', $this->parseEmails($data['emails'] ?? ''))->get(),
        };

        $new = 0;
        foreach ($users as $u) {
            $uc = UserCoupon::firstOrCreate(['coupon_id' => $coupon->id, 'user_id' => $u->id]);
            if ($uc->wasRecentlyCreated) {
                $new++;
            }
        }

        // 발행형 쿠폰이 아니면, 발행 시 발행형(비공개)로 전환할지 안내만
        return back()->with('ok', "{$new}명에게 새로 발행했습니다. (총 대상 {$users->count()}명)");
    }

    /** 발행 회수(미사용분) */
    public function revoke(Coupon $coupon, UserCoupon $userCoupon)
    {
        abort_unless($userCoupon->coupon_id === $coupon->id, 404);
        if ($userCoupon->used_at) {
            return back()->with('error', '이미 사용된 쿠폰은 회수할 수 없습니다.');
        }
        $userCoupon->delete();

        return back()->with('ok', '발행을 회수했습니다.');
    }

    private function parseEmails(string $raw): array
    {
        return collect(preg_split('/[\s,]+/', $raw))->map(fn ($e) => trim($e))->filter()->unique()->all();
    }
}
