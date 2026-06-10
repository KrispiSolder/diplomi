<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * @param  array<int, array{name:string, slug:string, children?:array}>  $nodes
     */
    private function seedTree(array $nodes, ?int $parentId = null): void
    {
        foreach ($nodes as $node) {
            $category = Category::updateOrCreate(
                ['slug' => $node['slug']],
                [
                    'name' => $node['name'],
                    'parent_id' => $parentId,
                    'is_active' => true,
                    'description' => $node['description'] ?? null,
                ]
            );

            if (! empty($node['children'])) {
                $this->seedTree($node['children'], $category->id);
            }
        }
    }

    public function run(): void
    {
        $tree = [
            [
                'name' => 'Комнатные растения',
                'slug' => 'indoor-plants',
                'children' => [
                    [
                        'name' => 'Цветущие',
                        'slug' => 'blooming',
                        'children' => [
                            ['name' => 'Орхидеи', 'slug' => 'orchids'],
                            ['name' => 'Фиалки', 'slug' => 'violets'],
                            ['name' => 'Азалии', 'slug' => 'azaleas'],
                            ['name' => 'Гардении', 'slug' => 'gardenias'],
                        ],
                    ],
                    [
                        'name' => 'Декоративно-лиственные',
                        'slug' => 'decorative-leaf',
                        'children' => [
                            ['name' => 'Монстера', 'slug' => 'monstera'],
                            ['name' => 'Филодендрон', 'slug' => 'philodendron'],
                            ['name' => 'Калатея', 'slug' => 'calathea'],
                            ['name' => 'Алоказия', 'slug' => 'alocasia'],
                        ],
                    ],
                    [
                        'name' => 'Суккуленты и кактусы',
                        'slug' => 'succulents-and-cacti',
                        'children' => [
                            ['name' => 'Кактусы', 'slug' => 'cacti'],
                            ['name' => 'Толстянки', 'slug' => 'crassula'],
                            ['name' => 'Эхеверии', 'slug' => 'echeveria'],
                            ['name' => 'Хавортии', 'slug' => 'haworthia'],
                        ],
                    ],
                    [
                        'name' => 'Пальмы и крупномеры',
                        'slug' => 'palms-large',
                        'children' => [
                            ['name' => 'Ховея', 'slug' => 'howea'],
                            ['name' => 'Юкка', 'slug' => 'yucca'],
                            ['name' => 'Драцена', 'slug' => 'dracaena'],
                            ['name' => 'Фикус', 'slug' => 'ficus'],
                        ],
                    ],
                    [
                        'name' => 'Ампельные',
                        'slug' => 'ampel',
                        'children' => [
                            ['name' => 'Сциндапсус', 'slug' => 'scindapsus'],
                            ['name' => 'Эпипремнум', 'slug' => 'epipremnum'],
                            ['name' => 'Хлорофитум', 'slug' => 'chlorophytum'],
                            ['name' => 'Традесканция', 'slug' => 'tradescantia'],
                        ],
                    ],
                    [
                        'name' => 'Неприхотливые',
                        'slug' => 'easy-care-plants',
                        'children' => [
                            ['name' => 'Сансевиерия', 'slug' => 'sansevieria'],
                            ['name' => 'Замиокулькас', 'slug' => 'zamioculcas'],
                            ['name' => 'Аспидистра', 'slug' => 'aspidistra'],
                            ['name' => 'Спатифиллум', 'slug' => 'spathiphyllum'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Сопутствующие товары',
                'slug' => 'accessories',
                'children' => [
                    ['name' => 'Горшки', 'slug' => 'pots-supplies'],
                    ['name' => 'Грунты', 'slug' => 'soils'],
                    ['name' => 'Удобрения', 'slug' => 'fertilizers'],
                    ['name' => 'Опоры', 'slug' => 'supports'],
                    ['name' => 'Дренаж', 'slug' => 'drainage-supplies'],
                ],
            ],
        ];

        $this->seedTree($tree);
    }
}
