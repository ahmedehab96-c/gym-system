<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Consistent JSON envelope for every API response, matching the shapes
 * defined in the Backend Blueprint §25 so controllers built in later
 * phases only ever return one of the four shapes below.
 */
class ApiResponse
{
    /**
     * Single-resource response: {"data": {...}}
     */
    public static function item(JsonResource|array $data, int $status = 200): JsonResponse
    {
        return response()->json(['data' => $data], $status);
    }

    /**
     * Paginated list response: {"data": [...], "meta": {page, perPage, total, totalPages}}
     */
    public static function paginated(LengthAwarePaginator|AnonymousResourceCollection $paginator): JsonResponse
    {
        $resource = $paginator instanceof AnonymousResourceCollection ? $paginator->resource : $paginator;

        return response()->json([
            'data' => $paginator instanceof AnonymousResourceCollection ? $paginator : $paginator->items(),
            'meta' => [
                'page' => $resource->currentPage(),
                'perPage' => $resource->perPage(),
                'total' => $resource->total(),
                'totalPages' => $resource->lastPage(),
            ],
        ]);
    }

    /**
     * Action acknowledgement response: {"message": "...", "data": {...}}
     */
    public static function message(string $message, array $data = [], int $status = 200): JsonResponse
    {
        $payload = ['message' => $message];

        if (! empty($data)) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status);
    }

    /**
     * Error response: {"message": "...", "errors": {...}}
     */
    public static function error(string $message, array $errors = [], int $status = 422): JsonResponse
    {
        $payload = ['message' => $message];

        if (! empty($errors)) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
