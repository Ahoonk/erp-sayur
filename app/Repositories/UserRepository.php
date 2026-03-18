<?php

namespace App\Repositories;

use App\Helpers\ResponseHelper;
use App\Http\Resources\PaginateResource;
use App\Http\Resources\UserResource;
use App\Interfaces\UserRepositoryInterface;
use App\Models\SalesRep;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRepository implements UserRepositoryInterface
{
    /**
     * Get all users
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function all($role = null)
    {
        $query = User::with('roles')->orderBy('name');

        if ($role) {
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        $users = $query->get();
        return ResponseHelper::success(UserResource::collection($users), 'Users retrieved successfully');
    }

    /**
     * Get all users with pagination
     *
     * @param int $perPage
     * @param string $search
     * @return \Illuminate\Http\JsonResponse
     */
    public function index($perPage, $search, $role = null)
    {
        $query = User::with('roles')
            ->search($search);

        if ($role) {
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        $users = $query->paginate($perPage);

        return ResponseHelper::success(
            UserResource::collection($users),
            'Users retrieved successfully'
        );
    }

    /**
     * Get all users with pagination (different format)
     *
     * @param int $perPage
     * @param string $search
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllPaginated($perPage, $search, $role = null)
    {
        $query = User::with('roles')
            ->search($search);

        if ($role) {
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        $users = $query->paginate($perPage);

        return ResponseHelper::success(
            new PaginateResource($users, UserResource::class),
            'Users retrieved successfully'
        );
    }

    /**
     * Store new user
     *
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(array $data)
    {
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        // Assign role if provided
        $role = $data['role'] ?? 'user';
        $user->assignRole($role);

        // Auto-create SalesRep for non super-admin users
        if ($role !== 'super-admin') {
            $salesRep = SalesRep::withTrashed()->find($user->id);
            if (!$salesRep) {
                $salesRep = new SalesRep();
                $salesRep->id = $user->id;
            }
            $salesRep->nama = $user->name;
            $salesRep->save();
        }

        return ResponseHelper::success(
            new UserResource($user->load('roles')),
            'User created successfully',
            201
        );
    }

    /**
     * Show user detail
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = User::with('roles')->findOrFail($id);

        return ResponseHelper::success(
            new UserResource($user),
            'User retrieved successfully'
        );
    }

    /**
     * Update user
     *
     * @param array $data
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(array $data, $id)
    {
        $user = User::findOrFail($id);

        // Remove password if empty
        if (isset($data['password']) && empty($data['password'])) {
            unset($data['password']);
        }

        // Hash password if provided
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        // Update role if provided
        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return ResponseHelper::success(
            new UserResource($user->load('roles')),
            'User updated successfully'
        );
    }

    /**
     * Delete user
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        // Also soft-delete the linked SalesRep (same ID) so it disappears from filters
        $salesRep = SalesRep::find($id);
        if ($salesRep) {
            $salesRep->delete();
        }

        return ResponseHelper::success(
            null,
            'User deleted successfully'
        );
    }

    /**
     * Update user password
     *
     * @param array $data
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePassword(array $data, $id)
    {
        $user = User::findOrFail($id);

        // Verify current password
        if (!Hash::check($data['current_password'], $user->password)) {
            return ResponseHelper::error('Current password is incorrect', null, 422);
        }

        $user->update([
            'password' => Hash::make($data['new_password']),
        ]);

        return ResponseHelper::success(
            null,
            'Password updated successfully'
        );
    }
}
