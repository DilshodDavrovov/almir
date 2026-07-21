<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Model;

class DrugView extends Model
{
    public $table = "drugs_";

    /**
     * SEARCH DATA By KEYWORD
     * @var STRING Keyword
     */
    public static function scopeSearchByWord($keyword, $isActive, $isDeleted)
    {
        return self::query()->where([
            ['full_name', 'LIKE', '%'.$keyword.'%'],
            ['is_active', $isActive],
            ['is_deleted', $isDeleted]
            ])->get()->take(30);
    }

    public static function scopeSearchByID($id, $isActive, $isDeleted)
    {
        return self::query()
        ->whereIn('id', $id)
        ->where([
            ['is_active', $isActive],
            ['is_deleted', $isDeleted]
        ])->get();
    }
}
