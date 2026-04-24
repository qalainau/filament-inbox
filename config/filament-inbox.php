<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The model class used for message participants. By default, the model
    | configured in your auth provider is used. Override this to use a
    | custom model (e.g., a separate Admin or Staff model).
    |
    */
    'user_model' => null,

    /*
    |--------------------------------------------------------------------------
    | Users Table
    |--------------------------------------------------------------------------
    |
    | The database table name for the user model. This is used in migrations
    | for foreign key constraints. Set to null to auto-detect from the model.
    |
    */
    'users_table' => null,

    /*
    |--------------------------------------------------------------------------
    | Tenant Users Relationship
    |--------------------------------------------------------------------------
    |
    | When Filament multi-tenancy is active, this defines the relationship
    | method name on the tenant model that returns the users belonging to
    | that tenant (e.g., 'members', 'users', 'employees').
    | Set to null to auto-detect ('members' then 'users').
    |
    */
    'tenant_users_relationship' => null,
];
