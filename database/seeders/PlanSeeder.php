<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug'            => 'starter_hour',
                'name'            => 'Starter',
                'price'           => 2.00,
                'billing_period'  => 'hour',
                'min_units'       => 1,
                'max_units'       => 12,
                'description'     => 'Оплата по часам',
                'features'        => ['50 зрителей', 'Без чат-ботов', 'До 12 часов', '1 стрим'],
                'bot_mode'        => 'viewers',
                'max_viewers'     => 50,
                'max_bots'        => 0,
                'max_streams'     => 1,
                'stream_duration' => 0,
                'is_popular'      => false,
                'is_active'       => true,
                'sort_order'      => 1,
                'button_text'     => 'Выбрать',
                'badge'           => null,
            ],
            [
                'slug'            => 'growth_stream',
                'name'            => 'Growth',
                'price'           => 29.00,
                'billing_period'  => 'stream',
                'min_units'       => 1,
                'max_units'       => 10,
                'description'     => 'За стрим',
                'features'        => ['150 зрителей', '20 ботов в чате', 'Ручное управление', 'До 8 часов стрима'],
                'bot_mode'        => 'manual',
                'max_viewers'     => 150,
                'max_bots'        => 20,
                'max_streams'     => 1,
                'stream_duration' => 8,
                'is_popular'      => true,
                'is_active'       => true,
                'sort_order'      => 2,
                'button_text'     => 'Выбрать',
                'badge'           => 'Популярный',
            ],
            [
                'slug'            => 'pro_month',
                'name'            => 'Pro',
                'price'           => 79.00,
                'billing_period'  => 'month',
                'min_units'       => 1,
                'max_units'       => 12,
                'description'     => 'Подписка на месяц',
                'features'        => ['500 зрителей', '50 AI-ботов', 'AI слушает стримера', 'До 3 стримов одновременно', 'Без лимита по времени'],
                'bot_mode'        => 'ai',
                'max_viewers'     => 500,
                'max_bots'        => 50,
                'max_streams'     => 3,
                'stream_duration' => 0,
                'is_popular'      => false,
                'is_active'       => true,
                'sort_order'      => 3,
                'button_text'     => 'Выбрать',
                'badge'           => null,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }

        $this->command->info('✅ Тарифов создано: ' . count($plans));
    }
}