<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Command extends Model
{

    protected $table = 'command';

    protected $fillable = [
        'user_id',
        'command',
    ];


}