<?php

namespace Webkul\API\Http\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Webkul\Activity\Repositories\ActivityRepository;
use Webkul\API\Http\Resources\LeadResource;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Lead\Repositories\StageRepository;
use Webkul\Tag\Repositories\TagRepository;

class LeadController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected LeadRepository $leadRepository,
        protected StageRepository $stageRepository,
        protected TagRepository $tagRepository,
        protected ActivityRepository $activityRepository
    ) {}

    /**
     * Display a listing of the leads.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $leads = $this->leadRepository
            ->with(['person', 'source', 'type', 'pipeline', 'stage', 'user', 'tags'])
            ->paginate($request->get('per_page', 15));

        return LeadResource::collection($leads);
    }

    /**
     * Store a newly created lead.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title'                  => 'required|string|max:255',
            'description'            => 'nullable|string',
            'lead_value'             => 'nullable|numeric|min:0',
            'person_id'              => 'nullable|exists:persons,id',
            'lead_source_id'         => 'nullable|exists:lead_sources,id',
            'lead_type_id'           => 'nullable|exists:lead_types,id',
            'lead_pipeline_id'       => 'required|exists:lead_pipelines,id',
            'lead_pipeline_stage_id' => 'required|exists:lead_pipeline_stages,id',
            'expected_close_date'    => 'nullable|date',
        ]);

        $lead = $this->leadRepository->create([
            'title'                  => $request->title,
            'description'            => $request->description,
            'lead_value'             => $request->lead_value ?? 0,
            'status'                 => 1,
            'person_id'              => $request->person_id,
            'lead_source_id'         => $request->lead_source_id,
            'lead_type_id'           => $request->lead_type_id,
            'lead_pipeline_id'       => $request->lead_pipeline_id,
            'lead_pipeline_stage_id' => $request->lead_pipeline_stage_id,
            'expected_close_date'    => $request->expected_close_date,
            'user_id'                => auth()->id(),
            'entity_type'            => 'leads',
        ]);

        return response()->json([
            'message' => 'Lead created successfully.',
            'data'    => new LeadResource($lead->load(['person', 'source', 'type', 'pipeline', 'stage'])),
        ], 201);
    }

    /**
     * Display the specified lead.
     */
    public function show(int $id): JsonResponse
    {
        $lead = $this->leadRepository
            ->with(['person', 'source', 'type', 'pipeline', 'stage', 'user', 'tags', 'activities', 'products'])
            ->findOrFail($id);

        return response()->json([
            'data' => new LeadResource($lead),
        ]);
    }

    /**
     * Update the specified lead.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'title'                  => 'sometimes|required|string|max:255',
            'description'            => 'nullable|string',
            'lead_value'             => 'nullable|numeric|min:0',
            'status'                 => 'nullable|integer',
            'lost_reason'            => 'nullable|string',
            'person_id'              => 'nullable|exists:persons,id',
            'lead_source_id'         => 'nullable|exists:lead_sources,id',
            'lead_type_id'           => 'nullable|exists:lead_types,id',
            'lead_pipeline_id'       => 'nullable|exists:lead_pipelines,id',
            'lead_pipeline_stage_id' => 'nullable|exists:lead_pipeline_stages,id',
            'expected_close_date'    => 'nullable|date',
        ]);

        $this->leadRepository->findOrFail($id);

        $data = $request->only([
            'title', 'description', 'lead_value', 'status', 'lost_reason',
            'person_id', 'lead_source_id', 'lead_type_id',
            'lead_pipeline_id', 'lead_pipeline_stage_id', 'expected_close_date'
        ]);
        $data['entity_type'] = 'leads';

        $lead = $this->leadRepository->update($data, $id);

        return response()->json([
            'message' => 'Lead updated successfully.',
            'data'    => new LeadResource($lead->load(['person', 'source', 'type', 'pipeline', 'stage'])),
        ]);
    }

    /**
     * Remove the specified lead.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->leadRepository->findOrFail($id);
        $this->leadRepository->delete($id);

        return response()->json([
            'message' => 'Lead deleted successfully.',
        ]);
    }

    /**
     * Update the lead stage.
     */
    public function updateStage(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'lead_pipeline_stage_id' => 'required|exists:lead_pipeline_stages,id',
        ]);

        $lead = $this->leadRepository->findOrFail($id);
        $stage = $this->stageRepository->findOrFail($request->lead_pipeline_stage_id);

        $updateData = ['lead_pipeline_stage_id' => $stage->id];

        // If stage is 'won' or 'lost', set closed_at
        if (in_array($stage->code, ['won', 'lost'])) {
            $updateData['closed_at'] = now();
            if ($stage->code === 'lost' && $request->has('lost_reason')) {
                $updateData['lost_reason'] = $request->lost_reason;
            }
        }

        $updateData['entity_type'] = 'leads';

    $lead = $this->leadRepository->update($updateData, $id);

        return response()->json([
            'message' => 'Lead stage updated successfully.',
            'data'    => new LeadResource($lead->load(['stage'])),
        ]);
    }

    /**
     * Search leads.
     */
    public function search(Request $request): AnonymousResourceCollection
    {
        $query = $request->get('q', '');

        $leads = $this->leadRepository
            ->scopeQuery(function ($q) use ($query) {
                return $q->where('title', 'like', "%{$query}%")
                    ->orWhereHas('person', function ($personQuery) use ($query) {
                        $personQuery->where('name', 'like', "%{$query}%");
                    });
            })
            ->with(['person', 'pipeline', 'stage'])
            ->paginate($request->get('per_page', 15));

        return LeadResource::collection($leads);
    }

    /**
     * Get activities for a lead.
     */
    public function activities(int $id): JsonResponse
    {
        $lead = $this->leadRepository->with(['activities'])->findOrFail($id);

        $activities = $lead->activities->map(fn ($activity) => [
            'id'            => $activity->id,
            'title'         => $activity->title,
            'type'          => $activity->type,
            'comment'       => $activity->comment,
            'location'      => $activity->location,
            'schedule_to'   => $activity->schedule_to,
            'schedule_from' => $activity->schedule_from,
            'is_done'       => $activity->is_done,
            'created_at'    => $activity->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'data' => $activities,
        ]);
    }

    /**
     * Store a new activity for a lead.
     */
    public function storeActivity(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'type'          => 'required|in:call,meeting,lunch,note',
            'title'         => 'required|string|max:255',
            'comment'       => 'nullable|string',
            'location'      => 'nullable|string',
            'schedule_from' => 'nullable|date',
            'schedule_to'   => 'nullable|date|after_or_equal:schedule_from',
            'is_done'       => 'nullable|boolean',
        ]);

        $lead = $this->leadRepository->findOrFail($id);

        $activity = $this->activityRepository->create([
            'type'          => $request->type,
            'title'         => $request->title,
            'comment'       => $request->comment,
            'location'      => $request->location,
            'schedule_from' => $request->schedule_from,
            'schedule_to'   => $request->schedule_to,
            'is_done'       => $request->is_done ?? 0,
            'user_id'       => auth()->id(),
        ]);

        $lead->activities()->attach($activity->id);

        return response()->json([
            'message' => 'Activity created successfully.',
            'data'    => [
                'id'            => $activity->id,
                'title'         => $activity->title,
                'type'          => $activity->type,
                'comment'       => $activity->comment,
                'location'      => $activity->location,
                'schedule_from' => $activity->schedule_from,
                'schedule_to'   => $activity->schedule_to,
                'is_done'       => $activity->is_done,
                'created_at'    => $activity->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Attach tags to a lead.
     */
    public function attachTags(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'tags'   => 'required|array',
            'tags.*' => 'exists:tags,id',
        ]);

        $lead = $this->leadRepository->findOrFail($id);
        $lead->tags()->syncWithoutDetaching($request->tags);

        return response()->json([
            'message' => 'Tags attached successfully.',
            'data'    => new LeadResource($lead->load('tags')),
        ]);
    }

    /**
     * Detach tags from a lead.
     */
    public function detachTags(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'tags'   => 'required|array',
            'tags.*' => 'exists:tags,id',
        ]);

        $lead = $this->leadRepository->findOrFail($id);
        $lead->tags()->detach($request->tags);

        return response()->json([
            'message' => 'Tags detached successfully.',
            'data'    => new LeadResource($lead->load('tags')),
        ]);
    }
}
