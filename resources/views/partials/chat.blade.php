{{-- 1:1 실시간 상담 위젯 (모든 화면 우하단 · 아이콘) --}}
<div class="chat-widget" id="chatWidget">
    <button class="chat-fab" id="chatFab" aria-label="상담하기" title="상담하기">
        <x-icon name="headset" :size="28"/>
    </button>

    <div class="chat-panel" id="chatPanel" hidden>
        <div class="chat-head">
            <div>
                <strong>메디셀 실시간 상담</strong>
                <span class="chat-sub">관리자와 1:1 대화</span>
            </div>
            <button class="chat-close" id="chatClose" aria-label="닫기"><x-icon name="close" :size="20"/></button>
        </div>

        {{-- 1단계: 연락처 입력 --}}
        <div class="chat-intro" id="chatIntro">
            <div class="chat-intro-msg">
                <x-icon name="headset" :size="30"/>
                <p>상담을 시작합니다.<br>이름과 연락처를 남겨주시면<br>관리자가 실시간으로 답변드립니다.</p>
            </div>
            <form id="chatStartForm">
                <div class="field"><label>이름 <span class="req">*</span></label><input type="text" id="chatName" class="input" maxlength="30" required></div>
                <div class="field"><label>전화번호 <span class="req">*</span></label><input type="text" id="chatPhone" class="input" maxlength="30" placeholder="010-0000-0000" required></div>
                <button type="submit" class="btn btn-primary btn-block">상담 시작하기</button>
                <p class="chat-err" id="chatErr" hidden></p>
            </form>
        </div>

        {{-- 2단계: 대화 --}}
        <div class="chat-conv" id="chatConv" hidden>
            <div class="chat-body" id="chatBody">
                <div class="chat-greet">안녕하세요! 메디셀입니다. 😊<br>무엇을 도와드릴까요?</div>
            </div>
            <form class="chat-input" id="chatForm">
                <textarea id="chatText" placeholder="메시지를 입력하세요" rows="1" maxlength="1000"></textarea>
                <button type="submit" aria-label="전송"><x-icon name="arrow-right" :size="20"/></button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://js.pusher.com/8.4/pusher.min.js"></script>
<script>
(function () {
    var fab = document.getElementById('chatFab');
    var panel = document.getElementById('chatPanel');
    var closeBtn = document.getElementById('chatClose');
    var intro = document.getElementById('chatIntro');
    var conv = document.getElementById('chatConv');
    var startForm = document.getElementById('chatStartForm');
    var nameI = document.getElementById('chatName');
    var phoneI = document.getElementById('chatPhone');
    var errEl = document.getElementById('chatErr');
    var body = document.getElementById('chatBody');
    var form = document.getElementById('chatForm');
    var text = document.getElementById('chatText');
    var csrf = document.querySelector('meta[name=csrf-token]').content;
    var pusherKey = document.querySelector('meta[name=pusher-key]').content;
    var pusherCluster = document.querySelector('meta[name=pusher-cluster]').content;
    var loaded = false, channel = null, lastId = 0;

    function esc(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
    function down(){ body.scrollTop = body.scrollHeight; }

    function append(m) {
        if (m.id && m.id <= lastId) return;
        if (m.id) lastId = m.id;
        var el = document.createElement('div');
        el.className = 'chat-msg ' + (m.sender === 'admin' ? 'from-admin' : 'from-user');
        el.innerHTML = '<div class="bubble">' + esc(m.body).replace(/\n/g,'<br>') + '</div><span class="t">' + m.time + '</span>';
        body.appendChild(el); down();
    }

    function subscribe(token) {
        if (!pusherKey || channel) return;
        var pusher = new Pusher(pusherKey, { cluster: pusherCluster, forceTLS: true });
        channel = pusher.subscribe('chat-' + token);
        channel.bind('message', function (data) {
            if (data.message && data.message.sender === 'admin') append(data.message);
        });
    }

    function showChat(token, messages) {
        intro.hidden = true; conv.hidden = false;
        (messages || []).forEach(append);
        subscribe(token);
        setTimeout(function(){ text.focus(); down(); }, 50);
    }

    function openPanel() {
        panel.hidden = false; fab.classList.add('hide');
        if (loaded) return;
        loaded = true;
        fetch('{{ route('chat.open') }}', { headers:{'Accept':'application/json'} })
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (d.name) nameI.value = d.name;
                if (d.phone) phoneI.value = d.phone;
                if (d.hasContact) { showChat(d.roomToken, d.messages); }
                else { intro.hidden = false; conv.hidden = true; }
            });
    }
    function closePanel(){ panel.hidden = true; fab.classList.remove('hide'); }

    fab.addEventListener('click', openPanel);
    closeBtn.addEventListener('click', closePanel);

    // 상담 시작 (이름/전화 저장)
    startForm.addEventListener('submit', function(e){
        e.preventDefault();
        errEl.hidden = true;
        var name = nameI.value.trim(), phone = phoneI.value.trim();
        if (!name || !phone) { errEl.textContent = '이름과 전화번호를 입력해 주세요.'; errEl.hidden = false; return; }
        fetch('{{ route('chat.start') }}', {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
            body: JSON.stringify({ name: name, phone: phone })
        }).then(function(r){ return r.json().then(function(j){ return {ok:r.ok, j:j}; }); })
          .then(function(res){
              if (!res.ok) { errEl.textContent = res.j.message || '시작에 실패했습니다.'; errEl.hidden = false; return; }
              showChat(res.j.roomToken, res.j.messages);
          });
    });

    text.addEventListener('keydown', function(e){
        if (e.key==='Enter' && !e.shiftKey){ e.preventDefault(); form.requestSubmit(); }
    });
    form.addEventListener('submit', function(e){
        e.preventDefault();
        var b = text.value.trim(); if (!b) return;
        text.value = '';
        fetch('{{ route('chat.send') }}', {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
            body: JSON.stringify({ body: b })
        }).then(function(r){ return r.json(); })
          .then(function(d){ if (d.message) { append(d.message); subscribe(d.roomToken); } });
    });
})();
</script>
@endpush
