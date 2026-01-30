<?php

namespace Webkul\API\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PersonResource extends JsonResource
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
            'id'              => $this->id,
            'name'            => $this->name,
            'emails'          => $this->emails,
            'contact_numbers' => $this->contact_numbers,
            'job_title'       => $this->job_title,
            'unique_id'       => $this->unique_id,
            'organization'    => $this->when($this->organization, [
                'id'   => $this->organization?->id,
                'name' => $this->organization?->name,
            ]),
            'user'            => $this->when($this->user, [
                'id'   => $this->user?->id,
                'name' => $this->user?->name,
            ]),
            'tags'            => $this->whenLoaded('tags', function () {
                return $this->tags->map(fn ($tag) => [
                    'id'    => $tag->id,
                    'name'  => $tag->name,
                    'color' => $tag->color,
                ]);
            }),
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }
}
