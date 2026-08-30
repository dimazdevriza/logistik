<?php

use Laravel\Jetstream\Features;

return [
    'stack' => 'livewire',
    'middleware' => ['web'],
    'auth_session' => null,
    'guard' => null,
    'features' => [
        Features::accountDeletion(),
    ],
    'profile_photo_disk' => 'public',
];
