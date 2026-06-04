<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Renders admin user management screens without embedding user query logic in Blade.
 */
class UserManagementPageController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.users.index');
    }

    public function create(Request $request)
    {
        return view('admin.users.create');
    }

    public function edit(Request $request, User $user)
    {
        return view('admin.users.edit', [
            'userId' => $user->id,
        ]);
    }
}