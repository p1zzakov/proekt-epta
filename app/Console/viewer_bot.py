#!/usr/bin/env python3
"""
Viewer Bot v8 — updateUserViewedVideo + usher playlist ping
"""
import subprocess, json, time, sys, threading, signal, random, redis, requests

CHANNEL   = sys.argv[1] if len(sys.argv) > 1 else 'test'
COUNT     = int(sys.argv[2]) if len(sys.argv) > 2 else 30
KEY       = f'viewer_bot:{CHANNEL}'
GQL       = 'https://gql.twitch.tv/gql'
CLIENT_ID = 'kimne78kx3ncx6brgo4mv6wki5h1ko'

rdb = redis.Redis(host='127.0.0.1', port=6379, db=0, decode_responses=True)
stop_flag  = threading.Event()
active_count = 0
lock = threading.Lock()

def get_stream_info():
    r = requests.post(GQL,
        headers={'Client-ID': CLIENT_ID},
        json=[{"query": f'{{user(login:"{CHANNEL}"){{stream{{id}} id}}}}'}],
        timeout=10)
    d = r.json()[0]['data']['user']
    return d['stream']['id'], d['id']  # video_id, channel_id

def get_accounts():
    result = subprocess.run(
        ['docker', 'exec', 'tb_postgres', 'psql', '-U', 'tbuser', '-d', 'twitchboost', '-t', '-c',
         f"SELECT json_agg(json_build_object('username',username,'user_id',twitch_id,'token',access_token)) "
         f"FROM (SELECT username,twitch_id,access_token FROM accounts "
         f"WHERE type='viewer' AND is_active=true AND status='available' "
         f"AND access_token IS NOT NULL AND twitch_id IS NOT NULL LIMIT {COUNT}) t;"],
        capture_output=True, text=True, timeout=20)
    return json.loads(result.stdout.strip())

def get_hls_url(token_data):
    """Получаем URL плейлиста через usher"""
    try:
        sig = token_data['sig']
        tok = token_data['token']
        url = (f'https://usher.twitchapps.com/api/channel/hls/{CHANNEL}.m3u8'
               f'?sig={sig}&token={tok}&allow_source=true&fast_bread=true'
               f'&p={random.randint(1000000,9999999)}&player_backend=mediaplayer'
               f'&playlist_include_framerate=true&reassignments_supported=false'
               f'&rtqos=control&cdm=wv')
        return url
    except:
        return None

def get_stream_token(session, username):
    """Получаем токен для HLS"""
    try:
        r = session.post(GQL,
            json=[{
                "operationName": "PlaybackAccessToken",
                "variables": {
                    "isLive": True,
                    "login": CHANNEL,
                    "isVod": False,
                    "vodID": "",
                    "playerType": "site",
                    "platform": "web"
                },
                "extensions": {
                    "persistedQuery": {
                        "version": 1,
                        "sha256Hash": "0828119ded1c13477966434e15800ff57ddacf13ba1911c129dc2200705b0712"
                    }
                }
            }],
            timeout=10)
        data = r.json()[0]['data']['streamPlaybackAccessToken']
        return data
    except:
        return None

def update_stats(delta):
    global active_count
    with lock:
        active_count = max(0, active_count + delta)
    rdb.hset(KEY, mapping={'active': active_count, 'total': COUNT, 'channel': CHANNEL, 'updated': int(time.time())})
    rdb.expire(KEY, 3600)

def viewer_worker(acc, video_id):
    username = acc['username']
    user_id  = str(acc['user_id'])
    token    = acc['token']
    position = random.randint(60, 600)

    session = requests.Session()
    session.headers.update({
        'Client-ID': CLIENT_ID,
        'Authorization': f'OAuth {token}',
        'Content-Type': 'application/json',
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
        'Referer': f'https://www.twitch.tv/{CHANNEL}',
        'Origin': 'https://www.twitch.tv',
    })

    # Получаем HLS токен и делаем первый запрос к usher
    token_data = get_stream_token(session, username)
    if token_data:
        hls_url = get_hls_url(token_data)
        if hls_url:
            try:
                # GET плейлиста — только заголовки, без тела (~5KB)
                session.head(hls_url, timeout=10)
            except:
                pass

    # updateUserViewedVideo heartbeat
    payload = [{
        "operationName": "updateUserViewedVideo",
        "variables": {"input": {
            "userID": user_id,
            "position": position,
            "videoID": video_id,
            "videoType": "LIVE"
        }},
        "extensions": {"persistedQuery": {
            "version": 1,
            "sha256Hash": "bb58b1bd08a4ca0c61f2b8d323381a5f4cd39d763da8698f680ef1dfaea89ca1"
        }}
    }]

    try:
        r = session.post(GQL, json=payload, timeout=10)
        if r.status_code == 200:
            update_stats(+1)
        else:
            print(f'❌ {username}: {r.status_code}')
            return
    except Exception as e:
        print(f'❌ {username}: {e}')
        return

    # Держим живым каждые 25 секунд
    while not stop_flag.is_set():
        stop_flag.wait(25)
        if stop_flag.is_set():
            break
        position += 25
        payload[0]['variables']['input']['position'] = position
        try:
            session.post(GQL, json=payload, timeout=10)
        except:
            pass

    update_stats(-1)

signal.signal(signal.SIGTERM, lambda s,f: stop_flag.set())
signal.signal(signal.SIGINT,  lambda s,f: stop_flag.set())

print(f'Viewer Bot v8 | #{CHANNEL} | цель: {COUNT}')
video_id, channel_id = get_stream_info()
print(f'Video ID: {video_id}, Channel ID: {channel_id}')

accounts = get_accounts()
if not accounts:
    print('❌ Нет аккаунтов!')
    sys.exit(1)
print(f'✅ Аккаунтов: {len(accounts)}')

rdb.hset(KEY, mapping={'active': 0, 'total': COUNT, 'channel': CHANNEL, 'updated': int(time.time())})

threads = []
for acc in accounts:
    t = threading.Thread(target=viewer_worker, args=(acc, video_id), daemon=True)
    t.start()
    threads.append(t)
    time.sleep(0.1)

print(f'Все {len(threads)} запущены')
try:
    while not stop_flag.is_set():
        time.sleep(5)
        print(f'Активных: {active_count}/{len(threads)}', flush=True)
except:
    stop_flag.set()
for t in threads: t.join(timeout=3)
