<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ClientAuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:64',
            'email'          => 'required|email|unique:clients,email',
            'password'       => 'required|string|min:8|confirmed',
            'twitch_channel' => 'nullable|string|max:64',
            'telegram'       => 'nullable|string|max:64',
        ]);

        Client::create([
            'name'           => $data['name'],
            'email'          => $data['email'],
            'password'       => Hash::make($data['password']),
            'twitch_channel' => $data['twitch_channel'] ?? null,
            'telegram'       => $data['telegram'] ?? null,
            'plan'           => 'free',
        ]);

        return redirect()->route('client.login')
            ->with('success', 'Аккаунт создан! Теперь можешь войти.');
    }

    public function showLogin()
    {
        return view('auth.client-login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $client = Client::where('email', $data['email'])->first();

        if (!$client || !Hash::check($data['password'], $client->password)) {
            return back()->withErrors(['email' => 'Неверный email или пароль'])->withInput();
        }

        if ($client->status === 'banned') {
            return back()->withErrors(['email' => 'Аккаунт заблокирован. Свяжитесь с поддержкой.']);
        }

        $client->last_login_at = now();
        $client->last_login_ip = $request->ip();
        $client->save();

        Auth::guard('client')->login($client, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->route('client.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('client')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('client.login');
    }
}
