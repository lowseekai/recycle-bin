<?php

use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->getConnection()->table('group_permission')
            ->where('permission', 'recycleBin.deleteOwnForever')
            ->where('group_id', 1)
            ->delete();
    },
    'down' => function (Builder $schema) {
        // The permission is registered by the admin frontend and does not need a seeded group row.
    },
];
