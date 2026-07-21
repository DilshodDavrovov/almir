<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DFGController extends Controller
{
    public function store(Request $req)
    {
        $inputs = $req->all();
        // Req ichida category va category translation data lar qaytadi.
        // Avval Category yozib olinadi
        $cat = new CategoryModel;
        $cat->is_active = $inputs['is_active'];
        $cat->is_deleted = $inputs['is_deleted'];
        $cat->save();

        $removeDataList = [];
        //$inputs['data'] bu dinamik input dagi datalar ro'yxati
        foreach ($inputs['data'] as $key => $value) {
            //while editing
            // Agar massivda category id qaytayotgan bo'lsa demak bu qiymat oldin mavjud bo'lgan.
            // Shu uchun oldin qo'shilgan qiymatlarni tizimdan topib ularni edit qilib qo'yish kerak bo'ladi
            $catTrans = CategoryTranslation::where(['category_id' => $cat->id, 'lang_id' => $value->lang_id])->first();
            if (empty($catTrans)) {
                $catTrans = new CategoryTranslation;
            }
            $catTrans->cat_id = $cat->id;
            $catTrans->lang_id = $value->lang_id;
            $catTrans->name = $value->name;
            $catTrans->save();
            array_push($removeDataList, $catTrans->lang_id);
        }
        if (!empty($RemoveList)) {
             $removed =  CategoryTranslation::whereNotIn('lang_id', $RemoveList)->where('category_id', $cat->id);
             $removed->delete();
        }
    }

}
