<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\formulir; // Import the formulir model

class FormulirController extends Controller
{
    public function index()
    {
        $formulir = formulir::all();
        return view('formulir.index', compact('formulir'));
    }

    public function create()
    {
        return view('formulir.create');
    }

    public function store(Request $request)
    {
        $formulir = new formulir([
            'title' => $request->input('title'),
            'description' => $request->input('description')
        ]);
        $formulir->save();

        return redirect('/formulir')->with('success', 'formulir created successfully!');
    }

    public function show($id)
    {
        $formulir = formulir::find($id);
        return view('formulir.show', compact('formulir'));
    }

    public function edit($id)
    {
        $formulir = formulir::find($id);
        return view('formulir.edit', compact('formulir'));
    }

    public function update(Request $request, $id)
    {
        $formulir = formulir::find($id);
        $formulir->title = $request->input('title');
        $formulir->description = $request->input('description');
        $formulir->save();

        return redirect('/formulir')->with('success', 'formulir updated successfully!');
    }

    public function destroy($id)
    {
        $formulir = formulir::find($id);
        $formulir->delete();

        return redirect('/formulir')->with('success', 'formulir deleted successfully!');
    }
}
