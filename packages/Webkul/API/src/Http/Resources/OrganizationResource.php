<?php

namespace Webkul\API\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'address'    => $this->address,
            'user'       => $this->when($this->user, [
                'id'   => $this->user?->id,
                'name' => $this->user?->name,
            ]),
            'persons_count' => $this->whenCounted('persons'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
