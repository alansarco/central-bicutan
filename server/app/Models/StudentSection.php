<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentSection extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = "students_section";

    protected $fillable = [
        'section_id',
        'clientid',
        'section_name',
        'status',
        'created_by',
        'updated_by'
    ];
}
