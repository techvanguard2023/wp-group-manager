<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Contact extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['phone', 'name', 'email', 'notes', 'phone_hash', 'email_hash'];

    protected $hidden = ['phone_hash', 'email_hash'];

    protected $casts = [
        'phone' => 'encrypted',
        'name' => 'encrypted',
        'email' => 'encrypted',
        'notes' => 'encrypted',
    ];

    protected static function booted()
    {
        static::saving(function ($contact) {
            if ($contact->isDirty('phone')) {
                $contact->phone_hash = hash_hmac('sha256', $contact->phone, config('app.key'));
            }
            if ($contact->isDirty('email') && $contact->email) {
                $contact->email_hash = hash_hmac('sha256', $contact->email, config('app.key'));
            }
        });
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(WhatsappGroup::class, 'group_contacts')
                    ->withPivot('added_at')
                    ->withTimestamps();
    }
}
