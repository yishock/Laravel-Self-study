<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminUserListPreference extends Model
{
    protected $fillable = [
        'admin_user_id',
        'page_key',
        'preferences',
    ];

    protected $casts = [
        'preferences' => 'array',
    ];

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id');
    }
}
