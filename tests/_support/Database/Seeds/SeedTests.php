<?php

namespace Tests\Support\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;
use ReflectionException;
use Tests\Support\Models\UserModel;

class SeedTests extends Seeder
{
    /**
     * @throws ReflectionException
     */
    public function run()
    {
        $data = [
            [
                'username' => 'Test User 1',
                'profile'  => [
                    'country' => 'United States',
                ],
                'posts' => [
                    [
                        'title'   => 'Title 1',
                        'content' => 'Content 1',
                        'rating'  => 4,
                    ],
                    [
                        'title'   => 'Title 2',
                        'content' => 'Content 2',
                        'rating'  => 1,
                    ],
                    [
                        'title'   => 'Title 3',
                        'content' => 'Content 3',
                        'rating'  => 5,
                    ],
                ],
            ],
            [
                'username' => 'Test User 2',
                'profile'  => [
                    'country' => 'Spain',
                ],
                'posts' => [
                    [
                        'title'   => 'Title 4',
                        'content' => 'Content 4',
                        'rating'  => 2,
                    ],
                    [
                        'title'   => 'Title 5',
                        'content' => 'Content 5',
                        'rating'  => 5,
                    ],
                    [
                        'title'    => 'Title 6',
                        'content'  => 'Content 6',
                        'rating'   => 1,
                        'comments' => [
                            [
                                'user_id' => 1,
                                'body'    => 'lorem ipsum',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $model = model(UserModel::class);

        foreach ($data as $item) {
            $model->with('profile')->with('posts')->with('posts.comments')->insert($item);
        }

        // Special Posts for User 1
        $now   = Time::now('UTC');
        $posts = [
            [
                'user_id'    => '1',
                'title'      => 'Title oldest',
                'content'    => 'Content oldest',
                'rating'     => 2,
                'created_at' => $now->subDays(14)->toDateTimeString(),
                'updated_at' => $now->subDays(14)->toDateTimeString(),
            ], [
                'user_id'    => '1',
                'title'      => 'Title latest',
                'content'    => 'Content latest',
                'rating'     => 2,
                'created_at' => $now->addDays(14)->toDateTimeString(),
                'updated_at' => $now->addDays(14)->toDateTimeString(),
            ],
        ];

        $this->db->table('posts')->insertBatch($posts);
    }
}
