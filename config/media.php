<?php

return [

    'max_upload_size_kb' => 3072,

    'max_original_width' => 2000,

    'quality_original' => 85,

    'quality_variant' => 80,

    'collections' => [

        'avatar' => [
            'force_ratio' => [1, 1],
            'variants' => [150, 400, 800],
        ],

        'user_avatar' => [
            'force_ratio' => [1, 1],
            'variants' => [150, 400],
        ],

        'rider_background' => [
            'keep_ratio' => true,
            'variants' => [400, 800, 1600],
        ],

        'photo_rider' => [
            'force_ratio' => [1, 1],
            'variants' => [150, 400, 800],
        ],

        'photo_kia' => [
            'keep_ratio' => true,
            'variants' => [400, 800],
        ],

        'post_cover' => [
            'force_ratio' => [16, 9],
            'variants' => [400, 800, 1600],
        ],

        'gallery' => [
            'keep_ratio' => true,
            'variants' => [400, 800],
        ],

    ],
];
