<?php

return [
    'source' => 'migration_source',
    'state_root' => storage_path('app/private/migration'),
    'backup_root' => '/backups',
    'backup_password' => env('REHEARSAL_BACKUP_PASSWORD'),
    'loader_password' => env('MIGRATION_LOADER_PASSWORD'),
];
