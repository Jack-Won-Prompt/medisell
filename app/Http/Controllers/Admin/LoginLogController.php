<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use Illuminate\Http\Request;

/**
 * 관리자 — 로그인 이력 조회.
 */
class LoginLogController extends Controller
{
    public function index(Request $request)
    {
        $result = $request->query('result', 'all'); // all | success | fail
        $q = trim((string) $request->query('q', ''));

        $query = LoginLog::with('user')->latest('created_at');
        if ($result === 'success') {
            $query->where('success', true);
        } elseif ($result === 'fail') {
            $query->where('success', false);
        }
        if ($q !== '') {
            $query->where(fn ($w) => $w->where('email', 'like', "%{$q}%")->orWhere('ip', 'like', "%{$q}%"));
        }

        return view('admin.login-logs.index', [
            'logs'   => $query->paginate(30)->withQueryString(),
            'result' => $result,
            'q'      => $q,
            'stats'  => [
                'total'   => LoginLog::count(),
                'success' => LoginLog::where('success', true)->count(),
                'fail'    => LoginLog::where('success', false)->count(),
                'today'   => LoginLog::whereDate('created_at', now()->toDateString())->count(),
            ],
        ]);
    }
}
