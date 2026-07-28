<?php

namespace App\Observers;

use App\Models\DeviceToken;
use App\Models\Notice;
use App\Services\FcmService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * 공지사항 푸시 발송.
 *
 * 관리자가 "푸시 발송"을 체크한 공지가 실제로 게시되는 시점에 한 번만 보낸다.
 * 예약 게시(미래 published_at)라면 그 시점이 지난 뒤 저장될 때 나간다.
 * 발송 여부는 pushed_at 으로 표시해 재저장·오탈자 수정에는 다시 나가지 않는다.
 */
class NoticeObserver
{
    public function __construct(private FcmService $fcm) {}

    public function saved(Notice $notice): void
    {
        if (! $notice->shouldPush()) {
            return;
        }
        // FCM 미설정 상태에서 발송됨으로 표시하면, 나중에 키를 넣어도 이 공지는 영영 안 나간다
        if (! $this->fcm->enabled()) {
            return;
        }

        $tokens = DeviceToken::pluck('token');
        if ($tokens->isEmpty()) {
            return;
        }

        // 본문 HTML 을 알림에 그대로 넣으면 태그가 보이므로 텍스트만 뽑는다.
        // 블록 태그는 공백으로 바꿔야 "…있습니다.9/28" 처럼 문장이 붙지 않는다.
        $body = preg_replace('#<(br|/p|/div|/li|/h[1-6]|/tr|/td)[^>]*>#i', ' ', (string) $notice->body);
        $body = html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $body = trim(preg_replace('/\s+/u', ' ', $body));

        $this->fcm->sendToTokens(
            $tokens,
            Str::limit($notice->title, 55),
            Str::limit($body, 110) ?: '새 공지사항이 등록되었습니다.',
            ['type' => 'notice', 'notice_id' => $notice->id, 'link' => '/community/notice/'.$notice->id],
        );

        // 옵저버 재진입을 피하려고 모델을 거치지 않고 직접 기록한다
        DB::table('notices')->where('id', $notice->id)->update(['pushed_at' => now()]);
    }
}
