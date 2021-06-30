<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReqResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'requestor' => $this->requestor->email,
            'status' => $this->status == 1 ? 'accepted' : ($this->status == 2 ? 'rejected' : 'pending')
        ];
    }
}
