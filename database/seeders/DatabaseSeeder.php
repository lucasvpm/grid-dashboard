<?php

namespace Database\Seeders;

use App\Models\Site;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Site::query()->firstOrCreate([
            'url' => 'https://meusite.com.br',
        ], [
            'name' => 'Portal Exemplo',
            'token' => 'cole-o-token-do-plugin-aqui',
        ]);
    }
}
