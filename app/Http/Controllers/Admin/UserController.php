<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HospitalPrice;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::latest();
        if ($filter = $request->get('filter')) {
            match ($filter) {
                'business' => $query->where('member_type', 'business'),
                'pending'  => $query->where('biz_status', 'pending'),
                default    => null,
            };
        }
        if ($q = $request->get('q')) {
            $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->orWhere('company_name', 'like', "%{$q}%"));
        }
        $users = $query->paginate(15)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $user->loadCount('orders');
        $prices = $user->hospitalPrices()->with('product')->get()
            ->filter(fn ($hp) => $hp->product !== null)
            ->sortBy(fn ($hp) => $hp->product->name);

        return view('admin.users.show', [
            'user'     => $user,
            'prices'   => $prices,
            'products' => Product::orderBy('name')->get(['id', 'name', 'price', 'member_price', 'code']),
            'accounts' => \App\Models\Account::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function approve(Request $request, User $user)
    {
        $data = $request->validate([
            'biz_status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
            'grade'      => ['required', Rule::in(['basic', 'silver', 'gold'])],
        ]);
        $user->update($data);

        return back()->with('ok', '회원 정보가 갱신되었습니다.');
    }

    /** 병원 전용가 등록/수정 (제품당 1개, upsert) */
    public function storePrice(Request $request, User $user)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'price'      => ['required', 'integer', 'min:0'],
        ]);

        HospitalPrice::updateOrCreate(
            ['user_id' => $user->id, 'product_id' => $data['product_id']],
            ['price' => $data['price']]
        );

        return back()->with('ok', '병원 전용가가 저장되었습니다.');
    }

    public function destroyPrice(User $user, HospitalPrice $price)
    {
        abort_unless($price->user_id === $user->id, 404);
        $price->delete();

        return back()->with('ok', '병원 전용가가 삭제되었습니다.');
    }

    /** 병원 전용가 양식(전체 상품 + 현재 전용가) CSV 다운로드 */
    public function exportPrices(User $user)
    {
        $map = $user->hospitalPrices()->pluck('price', 'product_id');
        $products = Product::with('category')->orderBy('name')->get();

        $filename = 'hospital_prices_'.$user->id.'_'.now()->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($products, $map) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['상품ID', '상품코드', '상품명', '정가', '전용가(이 열만 수정)']);
            foreach ($products as $p) {
                fputcsv($out, [$p->id, $p->code, $p->name, $p->price, $map[$p->id] ?? '']);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** 병원 전용가 CSV 일괄 업로드 (상품ID + 전용가) */
    public function importPrices(Request $request, User $user)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt']]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        if (! $handle) {
            return back()->with('error', '파일을 읽을 수 없습니다.');
        }

        $applied = 0;
        $cleared = 0;
        $line = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            // BOM 제거
            if ($line === 1 && isset($row[0])) {
                $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', $row[0]);
            }
            // 헤더 또는 빈 줄 스킵
            if (! isset($row[0]) || ! is_numeric($row[0])) {
                continue;
            }
            $productId = (int) $row[0];
            $price = isset($row[4]) ? trim((string) $row[4]) : '';

            if (! Product::whereKey($productId)->exists()) {
                continue;
            }

            if ($price === '') {
                // 전용가 비움 → 매핑 삭제
                $cleared += HospitalPrice::where('user_id', $user->id)->where('product_id', $productId)->delete();

                continue;
            }
            if (! is_numeric($price) || (int) $price < 0) {
                continue;
            }

            HospitalPrice::updateOrCreate(
                ['user_id' => $user->id, 'product_id' => $productId],
                ['price' => (int) $price]
            );
            $applied++;
        }
        fclose($handle);

        return back()->with('ok', "전용가 일괄 적용 완료: {$applied}건 등록/수정, {$cleared}건 해제.");
    }

    /** 회원 정보 수정 */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:50'],
            'email'        => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone'        => ['nullable', 'string', 'max:30'],
            'member_type'  => ['required', Rule::in(['general', 'business'])],
            'company_name' => ['nullable', 'string', 'max:100'],
            'biz_no'       => ['nullable', 'string', 'max:20'],
            'biz_type'     => ['nullable', 'string', 'max:50'],
            'biz_ceo'      => ['nullable', 'string', 'max:50'],
            'is_agent'     => ['nullable', 'boolean'],
            'cashback_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'account_id'   => ['nullable', 'exists:accounts,id'],
        ]);
        $data['is_agent'] = $request->boolean('is_agent');
        $data['cashback_rate'] = $data['is_agent'] ? (float) ($data['cashback_rate'] ?? 0) : 0;
        $data['account_id'] = $data['account_id'] ?: null;
        $user->update($data);

        return back()->with('ok', '회원 정보가 수정되었습니다.');
    }

    /** 비밀번호 초기화 */
    public function resetPassword(Request $request, User $user)
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);
        $user->update(['password' => Hash::make($data['password'])]);

        return back()->with('ok', '비밀번호가 초기화되었습니다.');
    }

    /** 관리자 권한 지정/해제 */
    public function toggleAdmin(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', '본인의 관리자 권한은 변경할 수 없습니다.');
        }
        if ($user->is_admin && User::where('is_admin', true)->count() <= 1) {
            return back()->with('error', '마지막 관리자는 해제할 수 없습니다.');
        }
        $user->update(['is_admin' => ! $user->is_admin]);

        return back()->with('ok', $user->is_admin ? '관리자로 지정했습니다.' : '관리자 권한을 해제했습니다.');
    }

    /** 회원 강제 탈퇴 */
    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', '본인 계정은 삭제할 수 없습니다.');
        }
        if ($user->is_admin) {
            return back()->with('error', '관리자 계정은 권한 해제 후 삭제할 수 있습니다.');
        }
        $user->delete();

        return redirect()->route('admin.users.index')->with('ok', '회원이 삭제되었습니다.');
    }

    /** 적립금 수동 가감 */
    public function adjustPoint(Request $request, User $user)
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', 'string', 'max:100'],
        ]);

        if ($data['amount'] < 0 && $user->point + $data['amount'] < 0) {
            return back()->with('error', '차감액이 보유 적립금을 초과합니다. (보유 '.number_format($user->point).'원)');
        }

        $user->adjustPoint($data['amount'], '[관리자] '.$data['reason']);

        return back()->with('ok', '적립금이 조정되었습니다. (현재 '.number_format($user->fresh()->point).'원)');
    }
}
