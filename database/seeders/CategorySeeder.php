<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $standardDescription = "➡️ Convide amigos para participar do grupo https://www.radardosmarketplaces.com.br\n\n🔥 Ofertas relâmpago! Se gostou do produto e preço, aproveite pois pode acabar rápido\n\n❌ NUNCA enviamos mensagens no privado!";

        $categories = [
            ['name' => 'Beleza & Cuidado Pessoal', 'description' => $standardDescription],
            ['name' => 'Brinquedos & Games', 'description' => $standardDescription],
            ['name' => 'Casa & Decoração', 'description' => $standardDescription],
            ['name' => 'Eletrônicos & Tecnologia', 'description' => $standardDescription],
            ['name' => 'Esportes & Fitness', 'description' => $standardDescription],
            ['name' => 'Moda & Acessórios', 'description' => $standardDescription],
            ['name' => 'Saúde & Bem-Estar', 'description' => $standardDescription],
            ['name' => 'Pet Shop', 'description' => $standardDescription],
            ['name' => 'Promoções Relâmpago', 'description' => $standardDescription],
            ['name' => 'Outros', 'description' => $standardDescription],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
