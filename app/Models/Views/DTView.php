<?php

namespace App\Models\Views;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DTView extends Model
{
    public $table = "drug_types_";

    /**
     * SEARCH DATA By KEYWORD
     * @var STRING Keyword
     */
    public static function scopeSearchByWord($keyword, $isActive, $isDeleted)
    {

        $userAccess = User::select('users.*', 'ur.x_roles_id as user_role')
        ->leftJoin('users_roles as ur', 'ur.user_id', '=', 'users.id')
        ->with('access')
        ->where('users.id', \Auth::user()->id)->first();

        $data = self::query();
       
        if ($userAccess && ($userAccess->user_role == 1 || $userAccess->user_role == 2)) {
            $data = $data->where([
                ['full_name', 'LIKE', '%' . $keyword . '%'],
                ['is_active', $isActive],
                ['is_deleted', $isDeleted]
            ]);
        }
        else {
            $idList = [];
            foreach ($userAccess->access as $item) {
                $idList[] = $item->type_id;
            }
            $data = $data->whereIn('id', $idList)
            ->where([
                ['full_name', 'LIKE', '%' . $keyword . '%'],
                ['is_active', $isActive],
                ['is_deleted', $isDeleted]
            ]);
        }
        return $data->get()->take(30);;
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
