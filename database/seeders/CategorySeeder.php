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
        $categories = [
            [
                'name' => 'Beleza & Cuidado Pessoal',
                'description' => 'Produtos de beleza e cuidados pessoais',
            ],
            [
                'name' => 'Brinquedos & Games',
                'description' => 'Brinquedos e games',
            ],
            [
                'name' => 'Casa & Decoração',
                'description' => 'Produtos de casa e decoração',
            ],
            [
                'name' => 'Eletrônicos & Tecnologia',
                'description' => 'Produtos de eletrônicos, informática e tecnologia',
            ],
            [
                'name' => 'Esportes & Fitness',
                'description' => 'Produtos de esportes e fitness',
            ],
            [
                'name' => 'Moda & Acessórios',
                'description' => 'Produtos de moda e acessórios',
            ],
            [
                'name' => 'Saúde & Bem-Estar',
                'description' => 'Produtos de saúde e bem-estar',
            ],
            [
                'name' => 'Pet Shop',
                'description' => 'Produtos de pet shop',
            ],
            [
                'name' => 'Promoções Relâmpago',
                'description' => 'Promoções relâmpago',
            ],
            [
                'name' => 'Outros',
                'description' => 'Outros',
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
