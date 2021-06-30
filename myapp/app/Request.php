<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    public function requestor()
    {
        return $this->belongsTo('App\User', 'requestor_id');
    }
}
