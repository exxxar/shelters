<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class AidCenter extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'city',
        'region',
        'address',
        'phone',
        'required',
        'description',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
    ];
}
