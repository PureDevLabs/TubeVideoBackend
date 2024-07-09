<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Extractor;

class ExtractorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'name' => 'youtube',
                'formal_name' => 'YouTube',
                'formal_fullname' => 'YouTube',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'tiktok',
                'formal_name' => 'TikTok',
                'formal_fullname' => 'TikTok',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'instagram',
                'formal_name' => 'Instagram',
                'formal_fullname' => 'Instagram',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'facebook',
                'formal_name' => 'Facebook',
                'formal_fullname' => 'Facebook',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'twitter',
                'formal_name' => 'Twitter',
                'formal_fullname' => 'Twitter',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'twitch',
                'formal_name' => 'Twitch',
                'formal_fullname' => 'Twitch VOD',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'vimeo',
                'formal_name' => 'Vimeo',
                'formal_fullname' => 'Vimeo',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'soundcloud',
                'formal_name' => 'SoundCloud',
                'formal_fullname' => 'SoundCloud',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'vk',
                'formal_name' => 'VK',
                'formal_fullname' => 'VK',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'dailymotion',
                'formal_name' => 'Dailymotion',
                'formal_fullname' => 'Dailymotion',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'aol',
                'formal_name' => 'AOL',
                'formal_fullname' => 'AOL',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];
        Extractor::insert($data);
    }
}
