<?php

namespace Webkul\API\Http\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Webkul\API\Http\Resources\OrganizationResource;
use Webkul\Contact\Repositories\OrganizationRepository;

class OrganizationController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected OrganizationRepository $organizationRepository
    ) {}

    /**
     * Display a listing of the organizations.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $organizations = $this->organizationRepository
            ->withCount('persons')
            ->paginate($request->get('per_page', 15));

        return OrganizationResource::collection($organizations);
    }

    /**
     * Store a newly created organization.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'    => 'required|string|max:255|unique:organizations,name',
            'address' => 'nullable|array',
        ]);

        $data = [
            'name'        => $request->name,
            'user_id'     => auth()->id(),
            'entity_type' => 'organizations',
        ];

        if ($request->has('address')) {
            $data['address'] = $request->address;
        }

        $organization = $this->organizationRepository->create($data);

        return response()->json([
            'message' => 'Organization created successfully.',
            'data'    => new OrganizationResource($organization),
        ], 201);
    }

    /**
     * Display the specified organization.
     */
    public function show(int $id): JsonResponse
    {
        $organization = $this->organizationRepository
            ->withCount('persons')
            ->findOrFail($id);

        return response()->json([
            'data' => new OrganizationResource($organization),
        ]);
    }

    /**
     * Update the specified organization.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name'    => 'sometimes|required|string|max:255|unique:organizations,name,'.$id,
            'address' => 'nullable|array',
        ]);

        $this->organizationRepository->findOrFail($id);
        
        $data = $request->only(['name', 'address']);
        $data['entity_type'] = 'organizations';

        $organization = $this->organizationRepository->update($data, $id);

        return response()->json([
            'message' => 'Organization updated successfully.',
            'data'    => new OrganizationResource($organization),
        ]);
    }

    /**
     * Remove the specified organization.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->organizationRepository->findOrFail($id);
        $this->organizationRepository->delete($id);

        return response()->json([
            'message' => 'Organization deleted successfully.',
        ]);
    }
}
