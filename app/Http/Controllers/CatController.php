<?php


namespace App\Http\Controllers;


use DenDroGram\Controller\AdjacencyList;
use DenDroGram\Controller\DenDroGram;
use Illuminate\Http\Request;

class CatController extends Controller
{
    public function index(Request $request)
    {
        (new DenDroGram(AdjacencyList::class))->operateNode($request->input('action'),$request->input('data'));
    }
}
