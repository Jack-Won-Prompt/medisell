@extends('layouts.admin')
@section('title', '실시간 상담')
@section('heading', '실시간 상담')

@section('content')
<div class="chat-console">
    {{-- 대화방 목록 --}}
    <div class="cc-rooms adm-card" style="margin:0">
        <div class="h">대화방 <span id="ccCount" class="pill pill-b">{{ $rooms->total() }}</span></div>
        <div class="cc-room-list" id="ccRooms">
            @forelse($rooms as $room)
                <button class="cc-room" data-id="{{ $room->id }}" data-token="{{ $room->token }}" data-name="{{ $room->displayName() }}">
                    <div class="cc-row1">
                        <strong>{{ $room->displayName() }}</strong>
                        @if($room->unread_admin > 0)<span class="cc-badge">{{ $room->unread_admin }}</span>@endif
                    </div>
                    <div class="cc-row2">
                        <span>{{ $room->displayPhone() ?? ($room->user_id ? '회원' : '비회원') }}</span>
                        <span>{{ optional($room->last_message_at)->format('m/d H:i') }}</span>
                    </div>
                </button>
            @empty
                <div style="padding:30px;text-align:center;color:#97a0b8">대화가 없습니다.</div>
            @endforelse
        </div>
    </div>

    {{-- 대화 영역 --}}
    <div class="cc-thread adm-card" style="margin:0">
        <div class="h" id="ccTitle">대화방을 선택하세요</div>
        <div class="cc-messages" id="ccMessages">
            <div class="cc-empty">왼쪽에서 대화방을 선택하면 메시지가 표시됩니다.</div>
        </div>
        <form class="cc-reply" id="ccReply" style="display:none">
            <textarea id="ccText" rows="1" placeholder="답장을 입력하세요 (Enter 전송)"></textarea>
            <button type="submit" class="abtn abtn-pri">전송</button>
        </form>
    </div>
</div>

<style>
.chat-console{display:grid;grid-template-columns:320px 1fr;gap:16px;height:calc(100vh - 150px)}
.cc-rooms{display:flex;flex-direction:column;overflow:hidden}
.cc-room-list{flex:1;overflow-y:auto}
.cc-room{width:100%;text-align:left;background:none;border:0;border-bottom:1px solid var(--a-line);padding:13px 16px;cursor:pointer;display:block}
.cc-room:hover{background:#f7f9fc}
.cc-room.on{background:#e5edff}
.cc-row1{display:flex;justify-content:space-between;align-items:center}
.cc-row1 strong{font-size:14px}
.cc-badge{background:#e0322d;color:#fff;font-size:11px;font-weight:700;min-width:18px;height:18px;border-radius:999px;display:flex;align-items:center;justify-content:center;padding:0 5px}
.cc-row2{display:flex;justify-content:space-between;font-size:11.5px;color:#97a0b8;margin-top:3px}
.cc-thread{display:flex;flex-direction:column;overflow:hidden}
.cc-messages{flex:1;overflow-y:auto;padding:18px;background:#f4f6fb;display:flex;flex-direction:column;gap:10px}
.cc-empty{margin:auto;color:#97a0b8}
.cc-msg{max-width:70%;display:flex;flex-direction:column}
.cc-msg .b{padding:10px 13px;border-radius:14px;font-size:14px;line-height:1.5;word-break:break-word}
.cc-msg .tm{font-size:10.5px;color:#97a0b8;margin-top:3px}
.cc-msg.user{align-self:flex-start}
.cc-msg.user .b{background:#fff;border:1px solid var(--a-line);border-bottom-left-radius:4px}
.cc-msg.admin{align-self:flex-end;align-items:flex-end}
.cc-msg.admin .b{background:var(--a-navy);color:#fff;border-bottom-right-radius:4px}
.cc-reply{display:flex;gap:10px;padding:12px;border-top:1px solid var(--a-line);background:#fff}
.cc-reply textarea{flex:1;border:1.5px solid #c7cedd;border-radius:10px;padding:10px 13px;font-size:14px;font-family:inherit;resize:none;outline:0;max-height:100px}
.cc-reply textarea:focus{border-color:var(--a-navy)}
</style>

<script src="https://js.pusher.com/8.4/pusher.min.js"></script>
<script>
(function () {
    var csrf = document.querySelector('meta[name=csrf-token]').content;
    var pusherKey = document.querySelector('meta[name=pusher-key]').content;
    var pusherCluster = document.querySelector('meta[name=pusher-cluster]').content;
    var rooms = document.getElementById('ccRooms');
    var msgs = document.getElementById('ccMessages');
    var title = document.getElementById('ccTitle');
    var replyForm = document.getElementById('ccReply');
    var replyText = document.getElementById('ccText');
    var current = null, roomChannel = null, lastId = 0, pusher = null;

    if (pusherKey) pusher = new Pusher(pusherKey, { cluster: pusherCluster, forceTLS: true });

    function esc(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
    function down(){ msgs.scrollTop = msgs.scrollHeight; }

    function render(m) {
        if (m.id && m.id <= lastId) return;
        if (m.id) lastId = m.id;
        var el = document.createElement('div');
        el.className = 'cc-msg ' + (m.sender === 'admin' ? 'admin' : 'user');
        el.innerHTML = '<div class="b">' + esc(m.body).replace(/\n/g,'<br>') + '</div><span class="tm">' + m.time + '</span>';
        msgs.appendChild(el); down();
    }

    function openRoom(btn) {
        document.querySelectorAll('.cc-room').forEach(function(b){ b.classList.remove('on'); });
        btn.classList.add('on');
        var badge = btn.querySelector('.cc-badge'); if (badge) badge.remove();
        current = { id: btn.dataset.id, token: btn.dataset.token };
        title.textContent = btn.dataset.name + ' 님과의 대화';
        msgs.innerHTML = ''; lastId = 0;
        replyForm.style.display = 'flex';

        fetch('{{ url('admin/chat') }}/' + current.id, { headers:{'Accept':'application/json'} })
            .then(function(r){ return r.json(); })
            .then(function(d){
                title.textContent = d.name + (d.phone ? ' (' + d.phone + ')' : '') + ' 님과의 대화';
                (d.messages||[]).forEach(render);
            });

        if (roomChannel) pusher.unsubscribe(roomChannel.name);
        if (pusher) {
            roomChannel = pusher.subscribe('chat-' + current.token);
            roomChannel.bind('message', function(data){ if (data.message && data.message.sender==='user') render(data.message); });
        }
    }

    rooms.addEventListener('click', function(e){
        var btn = e.target.closest('.cc-room'); if (btn) openRoom(btn);
    });

    replyText.addEventListener('keydown', function(e){
        if (e.key==='Enter' && !e.shiftKey){ e.preventDefault(); replyForm.requestSubmit(); }
    });
    replyForm.addEventListener('submit', function(e){
        e.preventDefault();
        var b = replyText.value.trim(); if (!b || !current) return;
        replyText.value = '';
        fetch('{{ url('admin/chat') }}/' + current.id + '/reply', {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
            body: JSON.stringify({ body: b })
        }).then(function(r){ return r.json(); }).then(function(d){ if (d.message) render(d.message); });
    });

    // 관리자 콘솔: 새 메시지 도착 시 알림(목록 갱신은 새로고침으로)
    if (pusher) {
        pusher.subscribe('chat-admin').bind('message', function(data){
            if (data.message && data.message.sender === 'user') {
                // 현재 열려있지 않은 방이면 해당 방 버튼에 뱃지 표시
                var btn = document.querySelector('.cc-room[data-id="' + data.roomId + '"]');
                if (btn && (!current || current.id != data.roomId)) {
                    var bd = btn.querySelector('.cc-badge');
                    if (!bd) { bd = document.createElement('span'); bd.className='cc-badge'; bd.textContent='0'; btn.querySelector('.cc-row1').appendChild(bd); }
                    bd.textContent = (parseInt(bd.textContent,10)||0) + 1;
                    rooms.prepend(btn);
                }
            }
        });
    }
})();
</script>
@endsection
