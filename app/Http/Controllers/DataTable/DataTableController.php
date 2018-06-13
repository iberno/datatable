<?php

namespace App\Http\Controllers\DataTable;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;

abstract class DataTableController extends Controller
{
    protected $builder;
    
    abstract public function builder();

    public function __construct()
    {
        $builder = $this->builder();

        if (!$builder instanceof Builder) {
            throw new Exception('Entity builder not instance of Builder');
        }
        
        $this->builder = $builder;
    }

    /**
     * Get Records
     *
     * @return Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        return response()->json([
            'data' => [
                'table' => $this->builder->getModel()->getTable(),
                'displayable' => array_values($this->getDisplayableColumns()),
                'updatable' => array_values($this->getUpdatableColumns()),
                'records' => $this->getRecords($request),
            ]
        ]);
    }
    /**
     * Update Only the columns previously declared
     *
     * @param [type] $id
     * @param Request $request
     * @return void
     */
    public function update($id, Request $request)
    {
        $this->builder->find($id)->update($request->only($this->getUpdatableColumns()));
    }
    /**
     * Get Column Hidden to Show
     *
     * @return void
     */
    public function getDisplayableColumns()
    {
        return array_diff($this->getDatabaseColumnNames(), $this->builder->getModel()->getHidden());
    }

    /**
     * Change State to Update Records
     *
     * @return void
     */
    public function getUpdatableColumns()
    {
        return $this->getDisplayableColumns();
    }
    /**
     * Get All Column Names from tables
     *
     * @return void
     */
    protected function getDatabaseColumnNames()
    {
        return Schema::getColumnListing($this->builder->getModel()->getTable());
    }

    /**
     * Filtering Hidden Columns from Any Controller
     *
     * @return Datatable\ANYController\getDisplayableColumns
     */
    protected function getRecords(Request $request)
    {
        return $this->builder->limit($request->limit)->orderBy('id', 'asc')->get($this->getDisplayableColumns()); 
    }
}
