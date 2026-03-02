<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'N8n user',
                'email' => 'n8n@teste.com',
                'password' => 'diehcxtlanac0409',
            ]
        ];

        foreach ($users as $user) {
            // Usamos create() para que o Eloquent gere o UUID e o Hash da senha automaticamente
            User::create($user);
        }
    }
}
