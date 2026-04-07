<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->groupBy('group');
        return view('admin.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->except(['_token', '_method']);

        foreach ($data as $key => $value) {
            $setting = Setting::where('key', $key)->first();
            if (!$setting) continue;

            // Для boolean — чекбокс не отправляет значение если unchecked
            if ($setting->type === 'boolean') {
                $value = isset($data[$key]) ? '1' : '0';
            }

            $setting->value = $value;
            $setting->save();
            Cache::forget("setting:{$key}");
        }

        // Отдельно обрабатываем boolean которые не пришли (unchecked)
        Setting::where('type', 'boolean')->each(function ($setting) use ($data) {
            if (!isset($data[$setting->key])) {
                $setting->value = '0';
                $setting->save();
                Cache::forget("setting:{$setting->key}");
            }
        });

        return redirect()->route('admin.settings')
            ->with('success', 'Настройки сохранены!');
    }
}
