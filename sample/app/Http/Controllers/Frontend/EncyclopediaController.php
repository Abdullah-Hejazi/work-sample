<?php

namespace App\Http\Controllers\Frontend;
use App\Http\Controllers\Controller;

use App\Encyclopedia;
use App\Law;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Legislation;
use App\Repositories\LawRepository\ILawRepository;

class EncyclopediaController extends Controller{
    protected $lawRepository;

    public function __construct(ILawRepository $lawRepository) {
        $this->lawRepository = $lawRepository;
    }

    public function index(Request $request, Encyclopedia $encyclopedia){
        if($encyclopedia->is_parent == true){
            $encyclopedia_childs = \Cache::remember('frontend_encyclopedia_'.$encyclopedia->id.'_children', 604800, function () use($encyclopedia) {
                return $encyclopedia->children()->where('published', true)->get();
            });

            return response()->json([
                'status'    => 'success',
                'data'      =>  [
                    'parent'        =>      true,
                    'payload'       =>      $encyclopedia_childs,
                    'encyclopedia'  =>      $encyclopedia
                ]
            ], 200);
        }

        $page = $request->page ? $request->page : 1;

        $laws = $this->lawRepository->index($encyclopedia->id, $page, ['id', 'law_number', 'law_text'], 15);

        return response()->json([
            'status'    => 'success',
            'data'      =>  [
                'parent'        =>      false,
                'payload'       =>      $laws,
                'encyclopedia'  =>      $encyclopedia
            ]
        ], 200);
    }

    public function law(Encyclopedia $encyclopedia, Law $law){
        $lawData = $this->lawRepository->find($law->id, ['id', 'law_number', 'law_text', 'law_note', 'law_judgements', 'law_explanation', 'law_jurisprudence'])[0];

        return response()->json([
            'status'    => 'success',
            'data'      =>  [
                'law'           =>      $lawData,
                'encyclopedia'  =>      $encyclopedia
            ]
        ], 200);
    }
}
