<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountPrice;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 관리자 — 거래처(병원·기업) 관리.
 * 거래처 단위로 소속 회원, 등급별 일괄 할인율, 제품 전용 단가를 관리.
 */
class AccountController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $query = Account::withCount(['users', 'prices'])->latest();
        if ($q !== '') {
            $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%"));
        }

        return view('admin.accounts.index', ['accounts' => $query->paginate(20)->withQueryString(), 'q' => $q]);
    }

    public function create()
    {
        return view('admin.accounts.form', ['account' => new Account(['is_active' => true, 'discount_rate' => 0])]);
    }

    public function store(Request $request)
    {
        $account = Account::create($this->validated($request));

        return redirect()->route('admin.accounts.edit', $account)->with('ok', '거래처가 등록되었습니다.');
    }

    public function edit(Account $account)
    {
        $prices = $account->prices()->with('product')->get()
            ->filter(fn ($ap) => $ap->product !== null)
            ->sortBy(fn ($ap) => $ap->product->name);

        return view('admin.accounts.form', [
            'account'  => $account,
            'prices'   => $prices,
            'members'  => $account->users()->orderBy('name')->get(),
            'products' => Product::orderBy('name')->get(['id', 'name', 'price', 'code']),
        ]);
    }

    public function update(Request $request, Account $account)
    {
        $account->update($this->validated($request, $account));

        return back()->with('ok', '거래처 정보가 수정되었습니다.');
    }

    public function destroy(Account $account)
    {
        // 소속 회원의 연결 해제(계정 자체는 유지) 후 거래처 삭제 (account_prices는 cascade)
        $account->users()->update(['account_id' => null]);
        $account->delete();

        return redirect()->route('admin.accounts.index')->with('ok', '거래처가 삭제되었습니다.');
    }

    // ===== 거래처 제품 전용가 =====

    public function storePrice(Request $request, Account $account)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'price'      => ['required', 'integer', 'min:0'],
        ]);
        AccountPrice::updateOrCreate(
            ['account_id' => $account->id, 'product_id' => $data['product_id']],
            ['price' => $data['price']]
        );

        return back()->with('ok', '거래처 전용가가 저장되었습니다.');
    }

    public function destroyPrice(Account $account, AccountPrice $price)
    {
        abort_unless($price->account_id === $account->id, 404);
        $price->delete();

        return back()->with('ok', '거래처 전용가가 삭제되었습니다.');
    }

    public function exportPrices(Account $account)
    {
        $map = $account->prices()->pluck('price', 'product_id');
        $products = Product::orderBy('name')->get();
        $filename = 'account_prices_'.$account->id.'_'.now()->format('Ymd').'.csv';

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

    public function importPrices(Request $request, Account $account)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt']]);
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        if (! $handle) {
            return back()->with('error', '파일을 읽을 수 없습니다.');
        }
        $applied = 0; $cleared = 0; $line = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            if ($line === 1 && isset($row[0])) {
                $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', $row[0]);
            }
            if (! isset($row[0]) || ! is_numeric($row[0])) {
                continue;
            }
            $productId = (int) $row[0];
            $price = isset($row[4]) ? trim((string) $row[4]) : '';
            if (! Product::whereKey($productId)->exists()) {
                continue;
            }
            if ($price === '') {
                $cleared += AccountPrice::where('account_id', $account->id)->where('product_id', $productId)->delete();

                continue;
            }
            if (! is_numeric($price) || (int) $price < 0) {
                continue;
            }
            AccountPrice::updateOrCreate(
                ['account_id' => $account->id, 'product_id' => $productId],
                ['price' => (int) $price]
            );
            $applied++;
        }
        fclose($handle);

        return back()->with('ok', "거래처 전용가 일괄 적용: {$applied}건 등록/수정, {$cleared}건 해제.");
    }

    // ===== 소속 회원 배정 =====

    public function attachMember(Request $request, Account $account)
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $user = User::where('email', $data['email'])->first();
        if (! $user) {
            return back()->with('error', '해당 이메일의 회원을 찾을 수 없습니다.');
        }
        $user->update(['account_id' => $account->id]);

        return back()->with('ok', "{$user->name}({$user->email}) 회원을 거래처에 배정했습니다.");
    }

    public function detachMember(Account $account, User $user)
    {
        abort_unless($user->account_id === $account->id, 404);
        $user->update(['account_id' => null]);

        return back()->with('ok', '회원 배정을 해제했습니다.');
    }

    private function validated(Request $request, ?Account $account = null): array
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'code'          => ['nullable', 'string', 'max:50', Rule::unique('accounts', 'code')->ignore($account?->id)],
            'discount_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active'     => ['nullable', 'boolean'],
            'memo'          => ['nullable', 'string', 'max:255'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['code'] = $data['code'] ?: null;

        return $data;
    }
}
