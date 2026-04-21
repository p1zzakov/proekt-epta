<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ViewLab — Техническое обслуживание</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Inter', sans-serif;
    background: #0a0a16;
    color: #fff;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.wrap { text-align: center; padding: 40px 24px; max-width: 480px; }
.logo { margin-bottom: 32px; }
.logo img { height: 80px; width: auto; }
.badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(250,200,0,0.12); border: 0.5px solid rgba(250,200,0,0.4);
    color: #fcd34d; font-size: 11px; font-weight: 700; border-radius: 20px;
    padding: 5px 14px; margin-bottom: 24px; letter-spacing: 0.5px; text-transform: uppercase;
}
.dot { width: 7px; height: 7px; border-radius: 50%; background: #fcd34d; animation: pulse 1.5s infinite; }
@keyframes pulse {
    0%,100% { box-shadow: 0 0 0 3px rgba(252,211,77,0.3); }
    50%      { box-shadow: 0 0 0 7px rgba(252,211,77,0.08); }
}
h1 { font-size: 32px; font-weight: 800; letter-spacing: -1px; margin-bottom: 12px; }
h1 span { color: #7c5cff; }
p { font-size: 14px; color: #6666a0; line-height: 1.7; margin-bottom: 40px; }
.orb1 { position: fixed; width: 600px; height: 600px; border-radius: 50%; background: radial-gradient(circle, rgba(59,47,255,0.2) 0%, transparent 70%); top: -200px; left: -150px; pointer-events: none; }
.orb2 { position: fixed; width: 400px; height: 400px; border-radius: 50%; background: radial-gradient(circle, rgba(100,60,255,0.12) 0%, transparent 70%); bottom: -100px; right: 100px; pointer-events: none; }
.footer { position: fixed; bottom: 24px; font-size: 11px; color: #333360; }
</style>
</head>
<body>
<div class="orb1"></div>
<div class="orb2"></div>
<div class="wrap">
    <div class="logo">
        <img src="/viewlab_logo.svg" alt="ViewLab" onerror="this.style.display='none'">
    </div>
    <div class="badge"><span class="dot"></span>В разработке</div>
    <h1>Скоро здесь будет <span>кое-что</span> крутое</h1>
    <p>Платформа для продвижения Twitch-каналов.<br>Работаем над запуском — совсем скоро.</p>
    <div style="font-size:12px;color:#333360;">viewlab.top</div>
</div>
<div class="footer">© 2026 ViewLab</div>
</body>
</html>
