<?php

use Flarum\Database\Migration;
use Flarum\Group\Group;

return Migration::addPermissions([
    // Seed the permission for administrators so it appears in the permissions UI.
    // Other groups must be enabled explicitly by the forum administrator.
    'recycleBin.deleteOwnForever' => Group::ADMINISTRATOR_ID,
]);
