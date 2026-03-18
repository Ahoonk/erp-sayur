<?php

namespace App\Interfaces;

interface MasterDataRepositoryInterface
{
    /**
     * Get all records with pagination and search
     */
    public function index($perPage, $search);

    /**
     * Get all records without pagination (for dropdowns)
     */
    public function all();

    /**
     * Store new record
     */
    public function store(array $data);

    /**
     * Quick create (only nama field)
     */
    public function quickStore(array $data);

    /**
     * Show record detail
     */
    public function show($id);

    /**
     * Update record
     */
    public function update(array $data, $id);

    /**
     * Delete record
     */
    public function destroy($id);
}
