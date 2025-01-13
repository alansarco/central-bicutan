<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Admin;
use App\Models\App_Info;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Controllers\Utilities\Utils;

class AdminController extends Controller
{
    // Get all the list of admins
    public function index(Request $request) {
        $filter = $request->filter ?? '';
        $genderFilter = $request->gender ?? '';
        $accountStatus = $request->account_status ?? '';

        // Call the stored procedure
        $users = DB::select('CALL GET_USERS_ADMIN(?, ?, ?)', [$filter, $genderFilter, $accountStatus]);

        // Convert the results into a collection
        $usersCollection = collect($users);

        // Set pagination variables
        $perPage = 50; // Number of items per page
        $currentPage = LengthAwarePaginator::resolveCurrentPage(); // Get the current page

        // Slice the collection to get the items for the current page
        $currentPageItems = $usersCollection->slice(($currentPage - 1) * $perPage, $perPage)->values();

        // Create a LengthAwarePaginator instance
        $paginatedUsers = new LengthAwarePaginator($currentPageItems, $usersCollection->count(), $perPage, $currentPage, [
            'path' => $request->url(), // Set the base URL for pagination links
            'query' => $request->query(), // Preserve query parameters in pagination links
        ]);

        // Return the response
        if ($paginatedUsers->count() > 0) {
            return response()->json([
                'status' => 200,
                'users' => $paginatedUsers,
                'message' => 'Admins retrieved!',
            ], 200);
        } else {
            return response()->json([
                'message' => 'No Admin Accounts found!',
                'users' => $paginatedUsers
            ]);
        }
    }

    public function addadmin(Request $request) {
        $authUser = new Utils;
        $authUser = $authUser->getAuthUser();

        if(Auth::user()->role !== "ADMIN" || Auth::user()->role < 10) {
            return response()->json([
                'message' => 'You are not allowed to perform this action!'
            ]);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'username' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
            'gender' => 'required',
            'contact' => 'required|string|regex:/^\+?[0-9]{10,15}$/',
            'birthdate' => 'required',   
            'address' => 'required',   
            'id_picture' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        if($validator->fails()) {
            return response()->json([
                'message' => $validator->messages()->all()
            ]);
        }

        $acountExist = DB::table('users')->where('username', $request->username)->first();
        $emailExist = DB::table('users')->where('email', $request->email)->first();
        $defaultPassword = Hash::make($request->contact);

        if(!$acountExist && !$emailExist) {
            try {
                $pictureData = null; // Initialize the variable to hold the file path
                if ($request->hasFile('id_picture')) {
                    $file = $request->file('id_picture');
                    $pictureData = file_get_contents($file->getRealPath()); // Get the file content as a string
                }
                $add = User::create([
                    'username' => $request->username,
                    'first_name' => strtoupper($request->first_name),
                    'middle_name' => strtoupper($request->middle_name),
                    'last_name' => strtoupper($request->last_name),
                    'password' => $defaultPassword,   
                    'gender' => $request->gender,   
                    'email' => $request->email,   
                    'address' => $request->address,   
                    'contact' => $request->contact,   
                    'role' => 'ADMIN',   
                    'id_picture' => $pictureData,   
                    'access_level' => 999,   
                    'birthdate' => $request->birthdate,  
                    'account_status' => 1,  
                    'created_by' => $authUser->fullname,
                ]);

            if($add) {
                return response()->json([
                    'status' => 200,
                    'message' => 'Admin added successfully!'
                ], 200);
            }
            else {
                return response()->json([
                    'message' => 'Something went wrong!'
                ]);
            }
            } catch (Exception $e) {
                return response()->json([
                    'message' => $e->getMessage()
                ]);
            }
        }
        else if($emailExist) {
            return response()->json([
                'message' => 'Email already taken!'
            ]);
        }
        else {
            return response()->json([
                'message' => 'Username already exist!'
            ]);
        }
    }

    public function updateadmin(Request $request) {
        $authUser = new Utils;
        $authUser = $authUser->getAuthUser();

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'username' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
            'gender' => 'required',
            'contact' => 'required|string|regex:/^\+?[0-9]{10,15}$/',
            'birthdate' => 'required',   
            'address' => 'required',   
            'account_status' => 'required',
        ]);

        if($validator->fails()) {
            return response()->json([
                'message' => $validator->messages()->all()
            ]);
        }
        else {
            $emailExist = DB::table('users')->where('email', $request->email)->whereNot('username', $request->username)->first();
            if($emailExist) {
                return response()->json([
                    'message' => 'Email already exist!'
                ]);        
            }

            $user = User::where('username', $request->username)->first();

            if($user) {
                try {
                    $updateData = [
                        'username' => $request->username,
                        'first_name' => strtoupper($request->first_name),
                        'middle_name' => strtoupper($request->middle_name),
                        'last_name' => strtoupper($request->last_name),
                        'gender' => $request->gender,
                        'email' => $request->email,
                        'address' => $request->address,
                        'contact' => $request->contact,
                        'birthdate' => $request->birthdate,
                        'account_status' => $request->account_status,
                        'updated_by' => $authUser->fullname,
                    ];
                    
                    // Check if id_picture is provided and add it to the update array
                    if ($request->hasFile('id_picture')) {
                        $file = $request->file('id_picture');
                        $pictureData = file_get_contents($file->getRealPath()); // Get the file content as a string
                        $updateData['id_picture'] = $pictureData;
                    }
                    $update = User::where('username', $request->username)->update($updateData);
                    
                    if($update) {
                        return response()->json([
                            'status' => 200,
                            'message' => 'Admin updated successfully!'
                        ], 200);
                    }
                    else {
                        return response()->json([
                            'message' => 'Something went wrong!'
                        ]);
                    }
                } catch (Exception $e) {
                    return response()->json([
                        'message' => $e->getMessage()
                    ]);
                }
            }
            else {
                return response()->json([
                    'message' => 'Admin not found!'
                ]);
            }
        }
    }
}
