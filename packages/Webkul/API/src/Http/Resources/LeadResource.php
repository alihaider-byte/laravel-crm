<?php

namespace Webkul\API\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
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
            'id'                  => $this->id,
            'title'               => $this->title,
            'description'         => $this->description,
            'lead_value'          => $this->lead_value,
            'status'              => $this->status,
            'lost_reason'         => $this->lost_reason,
            'expected_close_date' => $this->expected_close_date?->toDateString(),
            'closed_at'           => $this->closed_at?->toIso8601String(),
            'rotten_days'         => $this->rotten_days,
            'person'              => $this->when($this->person, [
                'id'              => $this->person?->id,
                'name'            => $this->person?->name,
                'emails'          => $this->person?->emails,
                'contact_numbers' => $this->person?->contact_numbers,
            ]),
            'user'                => $this->when($this->user, [
                'id'   => $this->user?->id,
                'name' => $this->user?->name,
            ]),
            'source'              => $this->when($this->source, [
                'id'   => $this->source?->id,
                'name' => $this->source?->name,
            ]),
            'type'                => $this->when($this->type, [
                'id'   => $this->type?->id,
                'name' => $this->type?->name,
            ]),
            'pipeline'            => $this->when($this->pipeline, [
                'id'   => $this->pipeline?->id,
                'name' => $this->pipeline?->name,
            ]),
            'stage'               => $this->when($this->stage, [
                'id'   => $this->stage?->id,
                'name' => $this->stage?->name,
                'code' => $this->stage?->code,
            ]),
            'tags'                => $this->whenLoaded('tags', function () {
                return $this->tags->map(fn ($tag) => [
                    'id'    => $tag->id,
                    'name'  => $tag->name,
                    'color' => $tag->color,
                ]);
            }),
            'created_at'          => $this->created_at?->toIso8601String(),
            'updated_at'          => $this->updated_at?->toIso8601String(),
        ];
    }
}
