<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ViewLab — viewlab.top</title>
<style>
*{box-sizing:border-box;margin:0;padding:0} a{outline:none;box-shadow:none;-webkit-tap-highlight-color:transparent}
body{font-family:'Inter',sans-serif;background:#f7f8fc;color:#1a1a2e}
.nav{display:flex;align-items:center;justify-content:space-between;padding:16px 40px;background:#fff;border-bottom:0.5px solid #e4e4ec;position:sticky;top:0;z-index:100}
.logo-wrap{display:flex;flex-direction:column;align-items:flex-start}
.logo-wrap svg{height:72px;width:auto}
.logo-wrap span{font-size:10px;font-weight:500;color:#888;letter-spacing:0.5px;margin-top:-2px;padding-left:2px}
.nav-r{display:flex;gap:24px;align-items:center}
.nav-r a{font-size:13px;color:#666;text-decoration:none}
.btn-cta{background:#3b2fff;color:#fff!important;border:none;padding:9px 20px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none!important;display:inline-block}
.hero{display:grid;grid-template-columns:1fr 1fr;min-height:540px;background:#0a0a16;position:relative;overflow:hidden}
.orb1{position:absolute;width:700px;height:700px;border-radius:50%;background:radial-gradient(circle,rgba(59,47,255,0.32) 0%,transparent 70%);top:-250px;left:-150px}
.orb2{position:absolute;width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(100,60,255,0.18) 0%,transparent 70%);bottom:-150px;right:150px}
.hero-l{position:relative;z-index:2;padding:64px 32px 64px 48px;display:flex;flex-direction:column;justify-content:center}
.first-badge{display:inline-flex;align-items:center;gap:7px;background:rgba(250,200,0,0.12);border:0.5px solid rgba(250,200,0,0.45);color:#fcd34d;font-size:11px;font-weight:700;border-radius:20px;padding:5px 14px;margin-bottom:20px;width:fit-content;letter-spacing:0.3px}
.hero-badge2{display:inline-flex;align-items:center;gap:7px;background:rgba(59,47,255,0.2);border:0.5px solid rgba(90,70,255,0.45);color:#a89fff;font-size:11px;font-weight:600;border-radius:20px;padding:5px 14px;margin-bottom:16px;width:fit-content}
.bdot{width:7px;height:7px;border-radius:50%;background:#5b45ff;animation:bp 1.5s infinite}
@keyframes bp{0%,100%{box-shadow:0 0 0 3px rgba(91,69,255,0.3)}50%{box-shadow:0 0 0 7px rgba(91,69,255,0.08)}}
.hero-h1{font-size:42px;font-weight:800;line-height:1.1;color:#fff;letter-spacing:-1.5px;margin-bottom:16px}
.hero-h1 .acc{color:#7c5cff}
.hero-sub{font-size:14px;color:#8888b0;line-height:1.7;margin-bottom:32px;max-width:420px}
.hero-sub strong{color:#c4b8ff;font-weight:600}
.hero-btns{display:flex;gap:12px;margin-bottom:36px}
.btn-p{background:#3b2fff;color:#fff;border:none;padding:13px 26px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block}
.btn-s{background:transparent;color:#a89fff;border:1px solid rgba(120,100,255,0.4);padding:13px 26px;border-radius:10px;font-size:14px;font-weight:500;cursor:pointer;text-decoration:none;display:inline-block}
.hero-stats{display:flex;gap:28px}
.stat-n{font-size:22px;font-weight:800;color:#fff}.stat-l{font-size:11px;color:#555577;margin-top:2px}
.hero-r{position:relative;z-index:2;padding:40px 48px 40px 16px;display:flex;align-items:center;justify-content:center}
.scard{background:#12122a;border:0.5px solid rgba(255,255,255,0.09);border-radius:18px;width:100%;max-width:400px;overflow:hidden;box-shadow:0 24px 80px rgba(0,0,0,0.7)}
.splayer{position:relative;height:190px;background:#0a0a16;overflow:hidden}
.splayer canvas{width:100%;height:100%;display:block}
.sover{position:absolute;inset:0;display:flex;flex-direction:column;justify-content:space-between;padding:10px}
.live-b{display:flex;align-items:center;gap:5px;background:rgba(210,30,30,0.9);color:#fff;font-size:10px;font-weight:700;padding:3px 9px;border-radius:5px}
.ldot{width:5px;height:5px;border-radius:50%;background:#fff;animation:bp 1s infinite}
.vb{background:rgba(0,0,0,0.55);color:#fff;font-size:10px;padding:3px 9px;border-radius:5px}
.sinfo{display:flex;align-items:center;gap:8px}
.av{width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,#5b45ff,#a855f7);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;flex-shrink:0}
.sn{font-size:11px;font-weight:600;color:#fff}.sg{font-size:10px;color:#7777aa}
.sbottom{display:grid;grid-template-columns:1fr 180px}
.cpanel{padding:12px 13px;border-right:0.5px solid rgba(255,255,255,0.06)}
.panhead{font-size:10px;font-weight:700;color:#444477;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px}
.cfeed{display:flex;flex-direction:column;gap:6px;min-height:90px}
.cm{font-size:11px;line-height:1.4;color:#9999cc}
.cm .u{font-weight:700}.cm.b .u{color:#7c5cff}.cm.v .u{color:#e06b00}.cm.ai .u{color:#22d3ee}
.mpanel{padding:12px 13px}
.mrows{display:flex;flex-direction:column;gap:7px}
.mrow .ml{font-size:10px;color:#444466}.mrow .mv{font-size:11px;font-weight:700;color:#fff;float:right}
.mrow::after{content:'';display:table;clear:both}
.bw{width:100%;height:3px;background:rgba(255,255,255,0.06);border-radius:2px;margin-top:3px}
.bf{height:3px;border-radius:2px;transition:width 1s}
.ai-section{background:#0f0f1a;padding:64px 48px;border-top:0.5px solid #1a1a30}
.ai-inner{display:grid;grid-template-columns:1fr 1fr;gap:64px;align-items:center}
.ai-tag{font-size:11px;font-weight:700;color:#22d3ee;text-transform:uppercase;letter-spacing:1px;margin-bottom:12px}
.ai-h{font-size:32px;font-weight:800;color:#fff;letter-spacing:-0.5px;line-height:1.15;margin-bottom:16px}
.ai-h .acc{color:#7c5cff}
.ai-desc{font-size:14px;color:#8888b0;line-height:1.7;margin-bottom:28px}
.ai-feats{display:flex;flex-direction:column;gap:14px}
.ai-feat{display:flex;gap:12px;align-items:flex-start}
.feat-icon{width:36px;height:72px;border-radius:9px;background:rgba(34,211,238,0.1);border:0.5px solid rgba(34,211,238,0.25);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:16px}
.feat-t{font-size:13px;font-weight:600;color:#c4c4e0;margin-bottom:3px}
.feat-d{font-size:12px;color:#66669a;line-height:1.5}
.ai-demo{background:#12122a;border:0.5px solid rgba(255,255,255,0.09);border-radius:16px;overflow:hidden}
.ai-demo-head{padding:14px 16px;border-bottom:0.5px solid rgba(255,255,255,0.07);display:flex;align-items:center;gap:8px}
.ai-demo-head span{font-size:12px;font-weight:600;color:#8888b0}
.ai-pill{background:rgba(34,211,238,0.12);border:0.5px solid rgba(34,211,238,0.3);color:#22d3ee;font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;margin-left:auto}
.ai-msgs{padding:14px 16px;display:flex;flex-direction:column;gap:10px;min-height:240px}
.amsg{display:flex;flex-direction:column;gap:3px}
.amsg-who{font-size:10px;font-weight:700}
.amsg-who.str{color:#fbbf24}.amsg-who.ai{color:#22d3ee}
.amsg-text{font-size:12px;line-height:1.5;padding:8px 12px;border-radius:10px;width:fit-content;max-width:90%}
.amsg-text.str{background:rgba(251,191,36,0.1);color:#e0c060;border:0.5px solid rgba(251,191,36,0.2)}
.amsg-text.ai{background:rgba(34,211,238,0.1);color:#7fe8f5;border:0.5px solid rgba(34,211,238,0.2)}
.typing{display:flex;gap:4px;padding:8px 12px;background:rgba(124,92,255,0.08);border-radius:10px;width:fit-content}
.typing span{width:5px;height:5px;border-radius:50%;background:#7c5cff;animation:ty 1s infinite}
.typing span:nth-child(2){animation-delay:.2s}.typing span:nth-child(3){animation-delay:.4s}
@keyframes ty{0%,100%{opacity:.3;transform:translateY(0)}50%{opacity:1;transform:translateY(-3px)}}
.bento{padding:64px 48px;background:#fff}
.sec-tag{font-size:11px;font-weight:700;color:#3b2fff;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px}
.sec-h{font-size:28px;font-weight:800;color:#0f0f1a;letter-spacing:-0.5px;margin-bottom:8px}
.sec-s{font-size:14px;color:#666;margin-bottom:40px}
.bgrid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
.bc{background:#f7f8fc;border:0.5px solid #e4e4ec;border-radius:14px;padding:22px;position:relative;overflow:hidden}
.bc.wide{grid-column:span 2}.bc.dark{background:#0f0f1a;border-color:#1e1e30}
.bctag{font-size:10px;font-weight:700;color:#3b2fff;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px}
.bctag.w{color:#7c5cff}
.bch{font-size:15px;font-weight:700;color:#1a1a2e;margin-bottom:5px}.bch.w{color:#fff}
.bcd{font-size:12px;color:#666;line-height:1.55}.bcd.w{color:#6666a0}
.vc{margin-top:18px;display:flex;align-items:baseline;gap:8px}
.vcn{font-size:40px;font-weight:800;color:#3b2fff;letter-spacing:-2px}
.vcl{font-size:12px;color:#666}.vct{font-size:11px;color:#22c55e;font-weight:600}
.reacts{margin-top:14px;display:flex;gap:7px;flex-wrap:wrap}
.rt{padding:4px 10px;border-radius:8px;font-size:12px}
.pricing{padding:64px 48px;background:#f7f8fc}
.pgrid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;max-width:860px;margin:0 auto}
.pcard{background:#fff;border:0.5px solid #e4e4ec;border-radius:16px;padding:26px 22px;position:relative}
.pcard.hot{border:2px solid #3b2fff}
.hotpill{position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:#3b2fff;color:#fff;font-size:10px;font-weight:700;padding:4px 14px;border-radius:20px;white-space:nowrap}
.pname{font-size:12px;font-weight:700;color:#3b2fff;margin-bottom:5px}
.pprice{font-size:36px;font-weight:800;color:#0f0f1a;letter-spacing:-1px;margin-bottom:4px}
.pprice sup{font-size:16px;vertical-align:super;font-weight:600}
.pprice span{font-size:12px;font-weight:400;color:#999}
.pdesc{font-size:11px;color:#888;margin-bottom:18px}
.pfeats{list-style:none;display:flex;flex-direction:column;gap:8px;margin-bottom:20px}
.pfeats li{display:flex;gap:7px;font-size:12px;color:#444;align-items:flex-start}
.chk{color:#3b2fff;font-weight:700;flex-shrink:0}
.pbtn{display:block;text-align:center;padding:11px;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;border:none;text-decoration:none}
.pbtn.out{background:#f0efff;color:#3b2fff}.pbtn.sol{background:#3b2fff;color:#fff}
.footer{background:#0a0a16;padding:32px 48px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px}
.flinks{display:flex;gap:20px}.flinks a{font-size:12px;color:#666;text-decoration:none}
.fcopy{font-size:11px;color:#444}
</style>
</head>
<body>

<nav class="nav">
  <div class="logo-wrap" style="background:#0a0a16;border-radius:10px;padding:4px 10px;">
    <img src="/viewlab_logo.svg" style="height:72px;width:auto;">
  </div>
  <div class="nav-r">
    <a href="#" style="font-size:13px;color:#fff!important;text-decoration:none!important;padding:9px 16px;border-radius:8px;background:#3b2fff;font-weight:600;display:inline-block">Возможности</a>
    <a href="#" style="font-size:13px;color:#fff!important;text-decoration:none!important;padding:9px 16px;border-radius:8px;background:#3b2fff;font-weight:600;display:inline-block">Тарифы</a>
    <a href="#" style="font-size:13px;color:#fff!important;text-decoration:none!important;padding:9px 16px;border-radius:8px;background:#3b2fff;font-weight:600;display:inline-block">FAQ</a>
    <a href="/login" style="font-size:13px;color:#fff!important;text-decoration:none!important;padding:9px 16px;border-radius:8px;background:#3b2fff;font-weight:600;display:inline-block">Войти</a><a href="/register" class="btn-cta">Начать бесплатно</a>
  </div>
</nav>

<section class="hero">
  <div class="orb1"></div><div class="orb2"></div>
  <div class="hero-l">
    <!-- <div class="first-badge">★ Первые в мире — AI-боты, которые слушают стримера</div> -->
    <div class="hero-badge2"><div class="bdot"></div>Сервис активности для стримеров</div>
    <h1 class="hero-h1">Боты, которые<br><span class="acc">слышат</span><br>ваш стрим</h1>
    <p class="hero-sub">ViewLab — первый сервис, где боты <strong>слушают стримера в реальном времени</strong> и отвечают по теме. Никакого спама — только живой, осмысленный чат.</p>
    <div class="hero-btns">
      <a href="/register" class="btn-p" style="text-decoration:none;">Попробовать бесплатно</a>
      <a href="#ai-demo" class="btn-s" style="text-decoration:none;">Смотреть демо</a>
    </div>
    <div class="hero-stats">
      <div><div class="stat-n">2 мин</div><div class="stat-l">Запуск</div></div>
      <div><div class="stat-n">100%</div><div class="stat-l">Конфиденциально</div></div>
      <div><div class="stat-n">24/7</div><div class="stat-l">Поддержка</div></div>
    </div>
  </div>
  <div class="hero-r">
    <div class="scard">
      <div class="splayer">
        <canvas id="sc"></canvas>
        <div class="sover">
          <div style="display:flex;justify-content:space-between">
            <div class="live-b"><div class="ldot"></div>LIVE</div>
            <div class="vb" id="vc2">👁 143</div>
          </div>
          <div class="sinfo">
            <div class="av">KP</div>
            <div><div class="sn">KirillPro</div><div class="sg">Valorant • Ranked</div></div>
          </div>
        </div>
      </div>
      <div class="sbottom">
        <div class="cpanel">
          <div class="panhead">Live chat</div>
          <div class="cfeed" id="cf">
            <div class="cm v"><span class="u">viewer_12:</span> nice play 🔥</div>
            <div class="cm b"><span class="u">bot_31:</span> good luck!</div>
            <div class="cm ai"><span class="u">AI_bot:</span> ты говорил про rank — сколько матчей сегодня?</div>
            <div class="cm v"><span class="u">viewer_83:</span> what game?</div>
            <div class="cm ai"><span class="u">AI_bot:</span> стрим уже 2 часа — держись! 💪</div>
          </div>
        </div>
        <div class="mpanel">
          <div class="panhead">Метрики</div>
          <div class="mrows">
            <div class="mrow"><span class="ml">Зрители</span><span class="mv" id="mv">143</span><div class="bw"><div class="bf" id="b1" style="width:70%;background:#3b2fff"></div></div></div>
            <div class="mrow"><span class="ml">Чат</span><span class="mv">HIGH</span><div class="bw"><div class="bf" id="b2" style="width:85%;background:#22c55e"></div></div></div>
            <div class="mrow"><span class="ml">AI ответы</span><span class="mv" id="mai">14</span><div class="bw"><div class="bf" id="b3" style="width:55%;background:#22d3ee"></div></div></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="ai-section">
  <div class="ai-inner">
    <div>
      <div class="ai-tag">★ Эксклюзивная технология</div>
      <h2 class="ai-h">Первые боты,<br>которые <span class="acc">слушают</span><br>стримера</h2>
      <p class="ai-desc">Мы первые на рынке, кто сделал это: наши AI-боты анализируют аудио стрима в реальном времени и вступают в диалог по теме — реагируют на игровые моменты, шутки, вопросы стримера. Никакого рандомного спама.</p>
      <div class="ai-feats">
        <div class="ai-feat"><div class="feat-icon">🎙️</div><div><div class="feat-t">Слушают стримера</div><div class="feat-d">AI анализирует речь в реальном времени и понимает контекст</div></div></div>
        <div class="ai-feat"><div class="feat-icon">💬</div><div><div class="feat-t">Отвечают по теме</div><div class="feat-d">Боты реагируют на конкретные фразы, события, вопросы</div></div></div>
        <div class="ai-feat"><div class="feat-icon">🧠</div><div><div class="feat-t">Запоминают контекст</div><div class="feat-d">Бот помнит что было сказано раньше — диалог выглядит живым</div></div></div>
      </div>
    </div>
    <div class="ai-demo">
      <div class="ai-demo-head">
        <span>Демо — AI чат в действии</span>
        <div class="ai-pill">AI СЛУШАЕТ</div>
      </div>
      <div class="ai-msgs" id="ai-msgs">
        <div class="amsg"><div class="amsg-who str">🎮 Стример говорит:</div><div class="amsg-text str">«Ладно чат, пробуем ranked, надеюсь не слетим из Diamond»</div></div>
        <div class="amsg"><div class="amsg-who ai">🤖 AI_bot_01 отвечает:</div><div class="amsg-text ai">Бро, Diamond держится! Не слетай 😤</div></div>
        <div class="amsg"><div class="amsg-who ai">🤖 AI_bot_07 отвечает:</div><div class="amsg-text ai">Diamond ranked — давай, мы верим! 🏆</div></div>
        <div class="amsg" id="typing-msg" style="display:none"><div class="amsg-who ai">🤖 AI_bot печатает...</div><div class="typing"><span></span><span></span><span></span></div></div>
      </div>
    </div>
  </div>
</section>

<section class="bento">
  <div class="sec-tag">Возможности</div>
  <h2 class="sec-h">Всё для живого стрима</h2>
  <p class="sec-s">Зрители, умный чат, реакции — полный пакет в одном сервисе</p>
  <div class="bgrid">
    <div class="bc wide dark">
      <div class="bctag w">Зрители</div>
      <div class="bch w">Стабильный онлайн, который заметен</div>
      <div class="bcd w">Реальное число зрителей создаёт эффект доверия. Новые пользователи охотнее остаются в стриме с активной аудиторией.</div>
      <div class="vc"><div class="vcn" id="vcn">143</div><div><div class="vcl">зрителей онлайн</div><div class="vct" id="vct">▲ +9 за минуту</div></div></div>
    </div>
    <div class="bc"><div class="bctag">AI-чат</div><div class="bch">Осмысленные ответы</div><div class="bcd">Боты не спамят — они слушают и отвечают именно на то, что сказал стример.</div></div>
    <div class="bc"><div class="bctag">Реакции</div><div class="bch">В нужный момент</div><div class="bcd">Эмодзи и реакции срабатывают на ключевые события — убийства, фейлы, смешные моменты.</div><div class="reacts"><span class="rt" style="background:#fff3e0;color:#c05000">🔥 ×38</span><span class="rt" style="background:#ede7f6;color:#6030a0">😮 ×21</span><span class="rt" style="background:#e8f5e9;color:#237030">✅ ×15</span><span class="rt" style="background:#fce4ec;color:#a01030">❤️ ×44</span></div></div>
    <div class="bc"><div class="bctag">Конфиденциальность</div><div class="bch">Никто не знает</div><div class="bcd">Ваш канал инкогнито. Мы не публикуем списки клиентов и не раскрываем данные.</div></div>
    <div class="bc"><div class="bctag">Панель управления</div><div class="bch">Один клик</div><div class="bcd">Запустить, остановить, настроить — прямо во время стрима через удобный дашборд.</div></div>
  </div>
</section>

<section class="pricing">
  <div style="text-align:center;margin-bottom:44px">
    <div class="sec-tag" style="text-align:center">Тарифы</div>
    <h2 class="sec-h" style="text-align:center">Выберите свой план</h2>
    <p class="sec-s" style="text-align:center;max-width:100%;margin-bottom:0">Платите только за стримы, которые хотите усилить</p>
  </div>
    <div class="pgrid">
    @foreach($plans as $plan)
    <div class="pcard {{ $plan->is_popular ? 'hot' : '' }}">
      @if($plan->badge)<div class="hotpill">{{ $plan->badge }}</div>@endif
      <div class="pname">{{ $plan->name }}</div>
      <div class="pprice"><sup>$</sup>{{ number_format($plan->price, 0) }} <span>/ {{ $plan->getPeriodLabel() }}</span></div>
      <div class="pdesc">{{ $plan->description }}</div>
      <ul class="pfeats">
        @foreach($plan->features as $feature)
        <li><span class="chk">✓</span>{{ $feature }}</li>
        @endforeach
      </ul>
      <a href="/register" class="pbtn {{ $plan->is_popular ? 'sol' : 'out' }}" style="text-decoration:none;">{{ $plan->button_text }}</a>
    </div>
    @endforeach
  </div>
</section>

<footer class="footer">
  <svg viewBox="0 0 480 200" xmlns="http://www.w3.org/2000/svg" style="height:32px;width:auto">
    <defs>
      <radialGradient id="eg2" cx="50%" cy="50%" r="50%"><stop offset="0%" style="stop-color:#00f0ff;stop-opacity:0.9"/><stop offset="100%" style="stop-color:#0044aa;stop-opacity:0"/></radialGradient>
      <linearGradient id="sg2" x1="0%" y1="0%" x2="100%" y2="0%"><stop offset="0%" style="stop-color:#00f0ff;stop-opacity:0"/><stop offset="50%" style="stop-color:#00f0ff;stop-opacity:1"/><stop offset="100%" style="stop-color:#aa00ff;stop-opacity:0.8"/></linearGradient>
      <linearGradient id="tg2" x1="0%" y1="0%" x2="100%" y2="0%"><stop offset="0%" style="stop-color:#ffffff"/><stop offset="100%" style="stop-color:#00f0ff"/></linearGradient>
      <linearGradient id="lg2" x1="0%" y1="0%" x2="100%" y2="0%"><stop offset="0%" style="stop-color:#aa00ff"/><stop offset="100%" style="stop-color:#ff44aa"/></linearGradient>
    </defs>
    <g transform="translate(52,100)">
      <circle cx="0" cy="0" r="14" fill="none" stroke="#00f0ff" stroke-width="1.5" opacity="0.8"/>
      <circle cx="0" cy="0" r="6" fill="#00f0ff" opacity="0.95"/>
      <circle cx="0" cy="0" r="2.5" fill="#0a0a12"/>
    </g>
    <text x="110" y="107" font-family="Arial Black,Impact,sans-serif" font-size="52" font-weight="900" fill="url(#tg2)" letter-spacing="-1">VIEW</text>
    <text x="110" y="145" font-family="Arial Black,Impact,sans-serif" font-size="28" font-weight="900" fill="url(#lg2)" letter-spacing="6">LAB</text>
  </svg>
  <div class="flinks"><a href="#">Поддержка</a><a href="#">Политика</a><a href="#">Условия</a><a href="#">Контакты</a></div>
  <div class="fcopy">© 2026 ViewLab. Все права защищены.</div>
</footer>

<script>
const canvas=document.getElementById('sc');
const ctx=canvas.getContext('2d');
let W,H,parts=[],t=0;
function resize(){const r=canvas.parentElement.getBoundingClientRect();canvas.width=W=r.width;canvas.height=H=r.height}
resize();window.addEventListener('resize',resize);
for(let i=0;i<50;i++)parts.push({x:Math.random()*600,y:Math.random()*200,vx:(Math.random()-.5)*.3,vy:(Math.random()-.5)*.3,r:Math.random()*1.4+0.4,a:Math.random()});
function draw(){ctx.clearRect(0,0,W,H);ctx.fillStyle='#0a0a16';ctx.fillRect(0,0,W,H);t+=.005;const gx=W/2+Math.sin(t*.7)*W*.3,gy=H/2+Math.cos(t*.5)*H*.25;const g=ctx.createRadialGradient(gx,gy,0,gx,gy,Math.max(W,H)*.8);g.addColorStop(0,'rgba(59,47,255,0.28)');g.addColorStop(.5,'rgba(100,60,255,0.08)');g.addColorStop(1,'rgba(0,0,0,0)');ctx.fillStyle=g;ctx.fillRect(0,0,W,H);parts.forEach(p=>{p.x+=p.vx;p.y+=p.vy;if(p.x<0)p.x=W;if(p.x>W)p.x=0;if(p.y<0)p.y=H;if(p.y>H)p.y=0;ctx.beginPath();ctx.arc(p.x,p.y,p.r,0,Math.PI*2);ctx.fillStyle=`rgba(180,160,255,${p.a*.5})`;ctx.fill()});parts.forEach((a,i)=>{for(let j=i+1;j<parts.length;j+=5){const b=parts[j];const d=Math.hypot(a.x-b.x,a.y-b.y);if(d<70){ctx.beginPath();ctx.moveTo(a.x,a.y);ctx.lineTo(b.x,b.y);ctx.strokeStyle=`rgba(100,80,255,${(1-d/70)*.12})`;ctx.lineWidth=.5;ctx.stroke()}}});requestAnimationFrame(draw)}
draw();
const chatMsgs=[{c:'v',u:'viewer_77',t:"let's go! 🚀"},{c:'ai',u:'AI_bot',t:'ты говорил про rank — держись!'},{c:'v',u:'viewer_204',t:'first time here!'},{c:'b',u:'bot_22',t:'pog pog pog 🔥'},{c:'ai',u:'AI_bot',t:'стрим уже долго — клатч!'},{c:'v',u:'viewer_55',t:'sub incoming ❤️'},{c:'ai',u:'AI_bot',t:'Diamond — не слетай бро 😤'},{c:'b',u:'bot_07',t:'chat is going crazy'}];
let mi=0,vw=143,aiCnt=14;
const feed=document.getElementById('cf'),vc2=document.getElementById('vc2'),mv=document.getElementById('mv'),vcn=document.getElementById('vcn'),vct=document.getElementById('vct'),maiEl=document.getElementById('mai');
setInterval(()=>{const m=chatMsgs[mi++%chatMsgs.length];const el=document.createElement('div');el.className='cm '+m.c;el.innerHTML=`<span class="u">${m.u}:</span> ${m.t}`;feed.appendChild(el);if(feed.children.length>6)feed.removeChild(feed.children[0]);vw+=Math.floor(Math.random()*5)-1;if(vw<120)vw=143;vc2.textContent='👁 '+vw;mv.textContent=vw;vcn.textContent=vw;vct.textContent='▲ +'+(Math.floor(Math.random()*8)+4)+' за минуту';if(m.c==='ai'){aiCnt++;maiEl.textContent=aiCnt}[document.getElementById('b1'),document.getElementById('b2'),document.getElementById('b3')].forEach((b,i)=>{const w=[55+Math.random()*30,70+Math.random()*25,40+Math.random()*40][i];b.style.width=Math.round(w)+'%'})},2000);
const aiScripts=[[{who:'str',t:'«Ладно чат, пробуем ranked, надеюсь не слетим из Diamond»'},{who:'ai',t:'Бро, Diamond держится! Не слетай 😤'},{who:'ai',t:'Diamond ranked — давай, мы верим! 🏆'}],[{who:'str',t:'«О, первый килл! Наконец-то разогрелся»'},{who:'ai',t:'ПЕРВЫЙ КИЛЛ! Начало положено 🔥'},{who:'ai',t:'разогрелся — теперь на 20+ иди 😈'}],[{who:'str',t:'«Так, сейчас попробую на снайпера перейти»'},{who:'ai',t:'Снайпер — рискованно, но уважаю 🎯'},{who:'ai',t:'ждём клатч на снайпере, не подведи!'}],[{who:'str',t:'«Блин, опять тиммейты не помогают»'},{who:'ai',t:'соло рейнджер 💪 всё сам!'},{who:'ai',t:'тиммейты — декорация, ты главный'}]];
let si=0;
const aiMsgs=document.getElementById('ai-msgs'),typingMsg=document.getElementById('typing-msg');
function runAiScript(){const sc=aiScripts[si++%aiScripts.length];aiMsgs.innerHTML='';sc.forEach((line,i)=>{setTimeout(()=>{if(i===sc.length-2)typingMsg.style.display='flex';setTimeout(()=>{typingMsg.style.display='none';const d=document.createElement('div');d.className='amsg';const label=line.who==='str'?'🎮 Стример говорит:':'🤖 AI_bot отвечает:';d.innerHTML=`<div class="amsg-who ${line.who}">${label}</div><div class="amsg-text ${line.who}">${line.t}</div>`;aiMsgs.appendChild(d);aiMsgs.scrollTop=aiMsgs.scrollHeight},900)},i*2000)})}
runAiScript();setInterval(runAiScript,8000);
</script>
</body>
</html>
