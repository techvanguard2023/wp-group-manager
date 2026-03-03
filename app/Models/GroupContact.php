<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroupContact extends Pivot
{
    use HasUuids, SoftDeletes;
    
    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'group_contacts';
    
    protected $fillable = ['whatsapp_group_id', 'contact_id'];
    
    protected $casts = [
        'added_at' => 'datetime',
    ];
}
