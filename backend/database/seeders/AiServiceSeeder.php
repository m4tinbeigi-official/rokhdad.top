<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AiServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\AiService::updateOrCreate(
            ['name' => 'Bynara'],
            [
                'base_url' => 'https://router.bynara.id/v1',
                'api_key' => env('BYNARA_API_KEY', 'your_api_key_here'),
                'model_name' => 'gpt-3.5-turbo',
                'is_active' => true,
            ]
        );

        \App\Models\AiService::updateOrCreate(
            ['name' => 'Gemini'],
            [
                'base_url' => 'https://generativelanguage.googleapis.com/v1beta/openai',
                'api_key' => env('GEMINI_API_KEY', 'your_api_key_here'),
                'model_name' => 'gemini-1.5-flash',
                'is_active' => false,
            ]
        );
    }
}
