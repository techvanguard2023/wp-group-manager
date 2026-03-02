<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhatsappGroup extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name', 'wa_group_id', 'wa_group_id_hash', 'invite_link', 'current_members',
        'max_members', 'is_active', 'category_id'
    ];

    protected $hidden = ['wa_group_id_hash'];

    protected $casts = [
        'name' => 'encrypted',
        'wa_group_id' => 'encrypted',
        'invite_link' => 'encrypted',
        'is_active' => 'boolean',
    ];

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    protected static function booted()
    {
        static::saving(function ($group) {
            if ($group->isDirty('wa_group_id')) {
                $group->wa_group_id_hash = hash_hmac('sha256', $group->wa_group_id, config('app.key'));
            }
        });
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'group_contacts')
                    ->withTimestamps()
                    ->using(GroupContact::class);
    }

    public function getHasSpaceAttribute(): bool
    {
        return $this->is_active && $this->current_members < $this->max_members;
    }
}
