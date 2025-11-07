<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $res['title'] = 'List of Faq';
        $res['items'] = Faq::orderBy('id', 'DESC')->get();
        if ($request->expectsJson()) {
            return response()->json(['data' => $res['items'], 'success' => '1']);
        }
        return view('admin-views.faq.index', $res);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $res['title'] = 'Create FAQ';
        $res['faq'] = new Faq();
        $res['url'] = route('admin.faq.store');
        $res['method'] = 'POST';
        return view('admin-views.faq.create', $res);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'faq' => 'required',
            'explain' => 'required'
        ]);
        $data = [
            'faq' => $request->faq,
            'explain' => $request->explain,
            'created_at' => date('Y-m-d H:i:s')
        ];
        if (Faq::insert($data)) {
            return redirect()->back()->with('success', 'Faq saved');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Faq  $faq
     * @return \Illuminate\Http\Response
     */
    public function show(Faq $faq)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Faq  $faq
     * @return \Illuminate\Http\Response
     */
    public function edit(Faq $faq)
    {
        $res['faq'] = $faq;
        $res['title'] = $faq['faq'] . ' Edit';
        $res['url'] = route('admin.faq.update', $faq['id']);
        $res['method'] = 'PUT';
        return view('admin-views.faq.create', $res);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Faq  $faq
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Faq $faq)
    {
        $request->validate([
            'faq' => 'required',
            'explain' => 'required'
        ]);
        $data = [
            'faq' => $request->faq,
            'explain' => $request->explain,
            'created_at' => date('Y-m-d H:i:s')
        ];
        if (Faq::where('id', $faq['id'])->update($data)) {
            return redirect()->back()->with('success', 'Faq saved');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Faq  $faq
     * @return \Illuminate\Http\Response
     */
    public function destroy(Faq $faq)
    {
        $faq->delete();
        return redirect()->back()->with('success', 'Deleted successfully');
    }
}
