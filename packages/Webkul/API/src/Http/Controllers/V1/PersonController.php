<?php

namespace Webkul\API\Http\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Webkul\API\Http\Resources\PersonResource;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Tag\Repositories\TagRepository;

class PersonController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected PersonRepository $personRepository,
        protected TagRepository $tagRepository
    ) {}

    /**
     * Display a listing of the persons.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $persons = $this->personRepository
            ->with(['organization', 'tags'])
            ->paginate($request->get('per_page', 15));

        return PersonResource::collection($persons);
    }

    /**
     * Store a newly created person.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'emails'          => 'nullable|array',
            'emails.*.value'  => 'required_with:emails|email',
            'emails.*.label'  => 'nullable|string',
            'contact_numbers' => 'nullable|array',
            'contact_numbers.*.value' => 'required_with:contact_numbers|string',
            'contact_numbers.*.label' => 'nullable|string',
            'job_title'       => 'nullable|string|max:255',
            'organization_id' => 'nullable|exists:organizations,id',
        ]);

        $data = [
            'name'            => $request->name,
            'job_title'       => $request->job_title,
            'organization_id' => $request->organization_id,
            'user_id'         => auth()->id(),
            'entity_type'     => 'persons',
        ];

        if (! empty($request->emails)) {
            $data['emails'] = $request->emails;
        }

        if (! empty($request->contact_numbers)) {
            $data['contact_numbers'] = $request->contact_numbers;
        }

        $person = $this->personRepository->create($data);

        return response()->json([
            'message' => 'Person created successfully.',
            'data'    => new PersonResource($person),
        ], 201);
    }

    /**
     * Display the specified person.
     */
    public function show(int $id): JsonResponse
    {
        $person = $this->personRepository
            ->with(['organization', 'tags', 'leads'])
            ->findOrFail($id);

        return response()->json([
            'data' => new PersonResource($person),
        ]);
    }

    /**
     * Update the specified person.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name'            => 'sometimes|required|string|max:255',
            'emails'          => 'nullable|array',
            'emails.*.value'  => 'required_with:emails|email',
            'emails.*.label'  => 'nullable|string',
            'contact_numbers' => 'nullable|array',
            'contact_numbers.*.value' => 'required_with:contact_numbers|string',
            'contact_numbers.*.label' => 'nullable|string',
            'job_title'       => 'nullable|string|max:255',
            'organization_id' => 'nullable|exists:organizations,id',
        ]);

        $person = $this->personRepository->findOrFail($id);
        
        $data = $request->only(['name', 'job_title', 'organization_id']);
        
        if ($request->has('emails')) {
            $data['emails'] = $request->emails ?: [];
            // If empty, remove it so repositories don't crash, UNLESS we want to clear them?
            // If user sends empty list, they likely want to delete all.
            // But PersonRepository crashes on empty list.
            // If request has 'emails' but it's empty, we should probably unset it to avoid crash, 
            // BUT that means we can't clear emails. 
            // However, given the bug in PersonRepository, we can't send empty array.
            // For now, let's omit if empty to be safe.
             if (empty($data['emails'])) {
                unset($data['emails']);
            }
        }

        if ($request->has('contact_numbers')) {
            $data['contact_numbers'] = $request->contact_numbers ?: [];
             if (empty($data['contact_numbers'])) {
                unset($data['contact_numbers']);
            }
        }
        
        $data['entity_type'] = 'persons';

        $person = $this->personRepository->update($data, $id);

        return response()->json([
            'message' => 'Person updated successfully.',
            'data'    => new PersonResource($person),
        ]);
    }

    /**
     * Remove the specified person.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->personRepository->findOrFail($id);
        $this->personRepository->delete($id);

        return response()->json([
            'message' => 'Person deleted successfully.',
        ]);
    }

    /**
     * Search persons by name or email.
     */
    public function search(Request $request): AnonymousResourceCollection
    {
        $query = $request->get('q', '');

        $persons = $this->personRepository
            ->scopeQuery(function ($q) use ($query) {
                return $q->where('name', 'like', "%{$query}%")
                    ->orWhereJsonContains('emails', [['value' => $query]]);
            })
            ->with(['organization'])
            ->paginate($request->get('per_page', 15));

        return PersonResource::collection($persons);
    }

    /**
     * Get activities for a person.
     */
    public function activities(int $id): JsonResponse
    {
        $person = $this->personRepository->with(['activities'])->findOrFail($id);

        $activities = $person->activities->map(fn ($activity) => [
            'id'          => $activity->id,
            'title'       => $activity->title,
            'type'        => $activity->type,
            'comment'     => $activity->comment,
            'schedule_to' => $activity->schedule_to,
            'schedule_from' => $activity->schedule_from,
            'is_done'     => $activity->is_done,
            'created_at'  => $activity->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'data' => $activities,
        ]);
    }

    /**
     * Attach tags to a person.
     */
    public function attachTags(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'tags'   => 'required|array',
            'tags.*' => 'exists:tags,id',
        ]);

        $person = $this->personRepository->findOrFail($id);
        $person->tags()->syncWithoutDetaching($request->tags);

        return response()->json([
            'message' => 'Tags attached successfully.',
            'data'    => new PersonResource($person->load('tags')),
        ]);
    }

    /**
     * Detach tags from a person.
     */
    public function detachTags(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'tags'   => 'required|array',
            'tags.*' => 'exists:tags,id',
        ]);

        $person = $this->personRepository->findOrFail($id);
        $person->tags()->detach($request->tags);

        return response()->json([
            'message' => 'Tags detached successfully.',
            'data'    => new PersonResource($person->load('tags')),
        ]);
    }
}
