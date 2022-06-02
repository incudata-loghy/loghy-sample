<?php

namespace App\Http\Resources;

use App\Models\SocialIdentity;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $identities = [];
        foreach ($this->socialIdentities as $identity) {
            $identities[] = [
                $identity->type => $identity->raw,
            ];
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'identities' => $identities,
        ];
    }
}
