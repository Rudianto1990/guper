<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = User::OrderBy('roles', 'asc')->get();
        
        return view('pages.admin.user.index', compact('user'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        abort(404);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email|unique:users',
            'password' => 'required',
            'roles' => 'required'
        ], [
            'email.unique' => 'Email sudah terdaftar',
        ]);

        DB::beginTransaction();

      }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit()
    {
        
        $admin = User::findOrFail(Auth::user()->id);
       

        return view('pages.profile', compact('admin'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();

            

            // Save to user table
            $user = Auth::user();
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->update($data);

            DB::commit();
            return redirect()->route('profile')->with('success', 'Data berhasil diubah');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        User::destroy($id);
        return redirect()->route('user.index')->with('success', 'Data user berhasil dihapus');
    }

    public function editPassword()
    {
     
        $admin = User::findOrFail(Auth::user()->id);

        return view('pages.ubah-password', compact('admin'));
    }

    public function updatePassword(Request $request)
    {

        // dd($request->all());

        if (!(Hash::check($request->get('current-password'), Auth::user()->password))) {
            return redirect()->back()->with("error", "Password lama tidak sesuai");
        }

        if (strcmp($request->get('current-password'), $request->get('new-password')) == 0) {
            return redirect()->back()->with("error", "Password baru tidak boleh sama dengan password lama");
        }

        $this->validate($request, [
            'current-password' => 'required',
            'new-password' => 'required|string|min:6',
        ], [
            'new-password.min' => 'Password baru minimal 6 karakter',
        ]);

        // Change Password
        $user = Auth::user();
        $user->password = bcrypt($request->get('new-password'));
        $user->save();


        return redirect()->route('profile')->with('success', 'Password berhasil diubah');
    }
}
