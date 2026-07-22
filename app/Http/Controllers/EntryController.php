<?php

namespace App\Http\Controllers;

use App\Http\Requests\EntryRequest;
use App\Http\Resources\EntryResource;
use App\Services\EntryService;
use Essa\APIToolKit\Api\ApiResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class EntryController extends Controller
{
    use ApiResponse;
    private EntryService $entryService;

    public function __construct(EntryService $entryService) {
        $this->entryService = $entryService;
    }

    public function index() {
        $entries =  $this->entryService->getEntry();

        if ($entries->isEmpty()) {
            return $this->responseNotFound('No Entries found.');
        }

        return $entries instanceof LengthAwarePaginator
            ? $entries->through(fn($item) => new EntryResource($item))
            : $this->responseSuccess('Entries fetched successfully', EntryResource::collection($entries));
    }

    public function store(EntryRequest $request) {
        $data = $request->validated();

        return $this->responseCreated('Entry created successfully', new EntryResource($this->entryService->createEntry($data)));
    }

    public function show($id) {
        if (!$this->entryService->getEntryById($id)) {
            return $this->responseNotFound('Entry not found');
        }

        return $this->responseSuccess('Entry fetched successfully', new EntryResource($this->entryService->getEntryById($id)));
    }

    public function update(EntryRequest $request, $id) {
        $data = $request->validated();
        $entry = $this->entryService->getEntryById($id);
        if (!$entry) {
            return $this->responseNotFound('Entry not found');
        }
        $updatedEntry = $this->entryService->updateEntry($entry, $data);
        return $this->responseSuccess('Entry updated successfully', new EntryResource($updatedEntry));
    }

    public function destroy($id) {
        $entry = $this->entryService->changeStatus($id);
        if (!$entry) {
            return $this->responseNotFound('Entry not found');
        }

        return $this->responseSuccess('Entry status changed successfully', $entry);
    }
}
