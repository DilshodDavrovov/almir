<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class GetDataCollection extends ResourceCollection
{
    private $pagination;

    public function __construct($resource)
    {
        $this->pagination = [
            'total' => $resource->total(),
            'count' => $resource->count(),
            'per_page' => $resource->perPage(),
            'current_page' => $resource->currentPage(),
            'total_pages' => $resource->lastPage()
        ];
        $resource = $resource->getCollection();
        parent::__construct($resource);
    }

    public function toArray($request)
    {
        $result = [
            'status' => 201,
            'success' => true,
            'data' => $this->collection,
            'pagination' => $this->pagination
        ];
        return $result;
    }

}
