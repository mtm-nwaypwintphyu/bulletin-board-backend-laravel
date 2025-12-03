<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostImportHistory extends Model
{

    protected $table = 'post_import_history';

    protected $fillable = [
        'import_file',
        'import_timestamp',
        'records_imported',
        'status',
        'error_message',
        'user_id',
        'created_at',
        'updated_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
