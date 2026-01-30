<?php

namespace Webkul\API\Http\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Lead\Repositories\PipelineRepository;
use Webkul\Lead\Repositories\SourceRepository;
use Webkul\Lead\Repositories\StageRepository;
use Webkul\Lead\Repositories\TypeRepository;
use Webkul\Tag\Repositories\TagRepository;
use Webkul\User\Repositories\UserRepository;

class LookupController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected SourceRepository $sourceRepository,
        protected TypeRepository $typeRepository,
        protected PipelineRepository $pipelineRepository,
        protected StageRepository $stageRepository,
        protected TagRepository $tagRepository,
        protected UserRepository $userRepository
    ) {}

    /**
     * Get all lead sources.
     */
    public function sources(): JsonResponse
    {
        $sources = $this->sourceRepository->all(['id', 'name']);

        return response()->json([
            'data' => $sources,
        ]);
    }

    /**
     * Get all lead types.
     */
    public function types(): JsonResponse
    {
        $types = $this->typeRepository->all(['id', 'name']);

        return response()->json([
            'data' => $types,
        ]);
    }

    /**
     * Get all pipelines with their stages.
     */
    public function pipelines(): JsonResponse
    {
        $pipelines = $this->pipelineRepository->with('stages')->all();

        $data = $pipelines->map(fn ($pipeline) => [
            'id'          => $pipeline->id,
            'name'        => $pipeline->name,
            'is_default'  => $pipeline->is_default,
            'rotten_days' => $pipeline->rotten_days,
            'stages'      => $pipeline->stages->map(fn ($stage) => [
                'id'        => $stage->id,
                'name'      => $stage->name,
                'code'      => $stage->code,
                'sort_order' => $stage->sort_order,
            ]),
        ]);

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get stages, optionally filtered by pipeline.
     */
    public function stages(?int $pipelineId = null): JsonResponse
    {
        if ($pipelineId) {
            $stages = $this->stageRepository
                ->findWhere(['lead_pipeline_id' => $pipelineId])
                ->sortBy('sort_order')
                ->values();
        } else {
            $stages = $this->stageRepository->all();
        }

        $data = $stages->map(fn ($stage) => [
            'id'               => $stage->id,
            'name'             => $stage->name,
            'code'             => $stage->code,
            'sort_order'       => $stage->sort_order,
            'lead_pipeline_id' => $stage->lead_pipeline_id,
        ]);

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Get all tags.
     */
    public function tags(): JsonResponse
    {
        $tags = $this->tagRepository->all(['id', 'name', 'color']);

        return response()->json([
            'data' => $tags,
        ]);
    }

    /**
     * Store a newly created tag.
     */
    public function storeTag(Request $request): JsonResponse
    {
        $request->validate([
            'name'  => 'required|unique:tags,name|max:50',
            'color' => 'nullable|string',
        ]);

        $tag = $this->tagRepository->create(array_merge($request->only([
            'name',
            'color',
        ]), [
            'user_id' => auth()->id(),
        ]));

        return response()->json([
            'message' => 'Tag created successfully.',
            'data'    => $tag,
        ], 201);
    }

    /**
     * Get all users (sales owners).
     */
    public function users(): JsonResponse
    {
        $users = $this->userRepository->all(['id', 'name', 'email']);

        return response()->json([
            'data' => $users,
        ]);
    }
}
