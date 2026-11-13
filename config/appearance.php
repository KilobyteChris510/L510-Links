<?php

/*
 * Public page appearance options. This is the single source of truth for themes,
 * colors, and button shapes; the model, validation, Blade views, and the editor's
 * JavaScript all read from here.
 */

return [

    'default_theme' => 'light',

    // Customizable color settings and their editor labels. Values are #rrggbb.
    'colors' => [
        'background_color' => 'Background',
        'text_color' => 'Text',
        'button_background_color' => 'Button background',
        'button_text_color' => 'Button text',
    ],

    'button_shapes' => [
        'rounded' => ['label' => 'Rounded', 'radius' => '0.875rem'],
        'pill' => ['label' => 'Pill', 'radius' => '9999px'],
        'square' => ['label' => 'Square', 'radius' => '0.125rem'],
    ],

    'themes' => [
        'light' => [
            'label' => 'Light',
            'background_color' => '#f1f5f9',
            'text_color' => '#0f172a',
            'button_background_color' => '#ffffff',
            'button_text_color' => '#0f172a',
            'button_shape' => 'rounded',
        ],
        'dark' => [
            'label' => 'Dark',
            'background_color' => '#0b1120',
            'text_color' => '#f1f5f9',
            'button_background_color' => '#1e293b',
            'button_text_color' => '#f8fafc',
            'button_shape' => 'rounded',
        ],
        'warm' => [
            'label' => 'Warm',
            'background_color' => '#faf3e6',
            'text_color' => '#3b2416',
            'button_background_color' => '#e8d6bd',
            'button_text_color' => '#3b2416',
            'button_shape' => 'pill',
        ],
    ],

];
