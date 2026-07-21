<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PeriodsPagination extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($data, $count, $per_page)
    {
        return [
        
            'status' => 201,
            'data' => $data,
            'total_pages' => $count,
            'current_page' => 3,
            'per_page' => $per_page

        ];
    }
}
