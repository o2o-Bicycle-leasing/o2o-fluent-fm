<?php

namespace o2o\FluentFM\Contract;

use o2o\FluentFM\Exception\FilemakerException;
use o2o\FluentFM\Utils\PaginatedCollection;

/**
 * Interface FluentFM.
 */
interface FluentFM
{
    /**
     * Get record by record id.
     *
     * @param     string   $layout
     * @param null|mixed   $id
     */
    public function record($layout, $id): FluentFM;

    /**
     * Get records from filemaker table.
     *
     * @param     string   $layout
     * @param null|mixed   $id
     */
    public function records($layout, $id = null): FluentFM;

    /**
     * Find records matching current query parameters.
     *
     * @return mixed
     */
    public function find(string $layout);

    /** @return FluentFM */
    public function findPaginated(string $layout, int $page = 1, int $perPage = 10);

    /**
     * Creates new filemaker record on table.
     *
     * @param array<string, string> $fields
     *
     * @return int|mixed
     *
     * @throws FilemakerException
     */
    public function create(string $layout, array $fields = []);

    /**
     * @param array<string, string> $fields
     *
     * @throws FilemakerException
     */
    public function globals(string $layout, array $fields = []): bool;

    /**
     * Update record with given recordId. If recordId is not given
     * updates will be applied to all records matching the current
     * query parameters.
     *
     * @param array<string, string> $fields
     *
     * @return mixed
     */
    public function update(string $layout, array $fields = [], ?int $recordId = null);

    /**
     * Upload a file to a container in recordId, if no record id specified
     * file will be added to all records matching current query.
     *
     * @return mixed
     */
    public function upload(string $layout, string $field, string $filename, ?int $recordId = null);

    /**
     * Download contents of container field to directory.
     * If no record id is specified the file will be downloaded for all records matching current query.
     * Files will be named using the id field of the record and the original file extension.
     *
     * @return mixed|self
     */
    public function download(string $layout, string $field, string $output_dir = './', ?int $recordId = null);

    /**
     * Delete record from table. If record id not provided all records matching
     * current query will be removed.
     *
     * @return mixed
     */
    public function delete(string $layout, ?int $recordId = null);

    /**
     * Sets deleted_at field on table for recordId. If no recordId
     * specified all matching the current query will be set.
     * This won't update records that have already been soft deleted.
     *
     * @return mixed
     */
    public function softDelete(string $layout, ?int $recordId = null);

    /**
     * Clears deleted_at field on table for recordId. If no recordId
     * specified all matching the current query will be cleared.
     *
     * @return mixed
     */
    public function undelete(string $layout, ?int $recordId = null);

    /**
     * Get fields for Filemaker table.
     *
     * @return array<int, string|int>
     *
     * @throws FilemakerException
     */
    public function fields(string $layout): array;

    public function logout(): void;

    /**
     * @return mixed
     *
     * @throws FilemakerException
     */
    public function exec();

    /**
     * Execute the command chain.
     *
     * @return mixed
     *
     * @throws FilemakerException
     */
    public function get();

    /**
     * Get the most recently created record in table.
     *
     * @return mixed
     *
     * @throws FilemakerException
     */
    public function latest(string $layout, string $field = 'created_at');

    /**
     * Get the most recently updated record in table.
     *
     * @return mixed
     *
     * @throws FilemakerException
     */
    public function lastUpdate(string $layout, string $field = 'updated_at');

    /**
     * Get the oldest record in table.
     *
     * @return mixed
     *
     * @throws FilemakerException
     */
    public function oldest(string $layout, string $field = 'created_at');

    /**
     * Execute the command chain.
     *
     * @return mixed
     *
     * @throws FilemakerException
     */
    public function first();

    /**
     * Execute the command chain.
     *
     * @return mixed
     *
     * @throws FilemakerException
     */
    public function last();

    /**
     * Limit the number of results returned.
     *
     * @return FluentFM
     */
    public function limit(int $limit): FluentFM;

    /**
     * Begin result set at the given record id.
     *
     * @return FluentFM
     */
    public function offset(int $offset): FluentFM;

    /**
     * Sort results by field.
     *
     * @return FluentFM
     */
    public function sort(string $field, bool $ascending = true): FluentFM;

    /**
     * Add field to sort results.
     *
     * @return FluentFM
     */
    public function andSort(string $field, bool $ascending = true): FluentFM;

    /**
     * Sort results by field and ValueList.
     *
     * @return FluentFM
     */
    public function sortByValueList(string $field, string $valueList): FluentFM;

    /**
     * And sort results by field and ValueList.
     *
     * @return FluentFM
     */
    public function andSortByValueList(string $field, string $valueList): FluentFM;

    /**
     * Sort results ascending by field.
     *
     * @return FluentFM
     */
    public function sortAsc(string $field): FluentFM;

    /**
     * Sort results descending by field.
     *
     * @return FluentFM
     */
    public function sortDesc(string $field): FluentFM;

    /**
     * Include portal data in results.
     *
     * @return FluentFM
     */
    public function withPortals(): FluentFM;

    /**
     * Don't include portal data in results.
     *
     * @return FluentFM
     */
    public function withoutPortals(): FluentFM;

    /**
     * @param       string $field
     * @param   int|string ...$params
     *
     * @return FluentFM
     */
    public function where($field, ...$params): FluentFM;

    /**
     * @param array<string|int,mixed|array> $criteria
     */
    public function whereCriteria($criteria): FluentFM;

    /**
     * @param string $field
     */
    public function whereEmpty($field): FluentFM;

    public function has(string $field): FluentFM;

    public function whereNotEmpty(string $field): FluentFM;

    /**
     * Include records that have their deleted_at field set.
     */
    public function withDeleted(): FluentFM;

    /**
     * Exclude records that have their deleted_at field set.
     */
    public function withoutDeleted(): FluentFM;

    /**
     * Run FileMaker script with param. If no type specified script will run
     * after requested action and sorting is complete.
     *
     * @param null|mixed $param
     */
    public function script(string $script, $param = null, ?string $type = null): FluentFM;

    /**
     * Run FileMaker script with param before requested action.
     *
     * @param null|mixed $param
     */
    public function prerequest(string $script, $param = null): FluentFM;

    /**
     * Run FileMaker script with param after requested action but before sort.
     *
     * @param null|mixed $param
     */
    public function presort(string $script, $param = null): FluentFM;
}
