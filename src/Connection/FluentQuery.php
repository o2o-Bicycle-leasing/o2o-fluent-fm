<?php

namespace o2o\FluentFM\Connection;

use o2o\FluentFM\Contract\FluentFM;

use function count;
use function strpos;

/**
 * Trait FluentQuery.
 */
trait FluentQuery
{
    /** @var array<string,array|mixed> */
    protected $query;

    /** @var bool */
    protected $with_portals = false;

    /** @var bool */
    protected $with_deleted = true;

    /**
     * Limit the number of results returned.
     */
    public function limit(int $limit): FluentFM
    {
        $this->query['limit'] = $limit;

        return $this;
    }
    
    public function limitPortal(string $portal, int $limit): FluentFM
    {
        $this->query['limit.' . $portal] = $limit;

        return $this;
    }

    /**
     * Begin result set at the given record id.
     */
    public function offset(int $offset): FluentFM
    {
        $this->query['offset'] = $offset;

        return $this;
    }

    /**
     * Sort results ascending by field.
     */
    public function sortAsc(string $field): FluentFM
    {
        $this->sort($field);

        return $this;
    }

    /**
     * Sort results by field.
     */
    public function sort(string $field, bool $ascending = true): FluentFM
    {
        $this->query['sort'] = [
            [
                'fieldName' => $field,
                'sortOrder' => $ascending ? 'ascend' : 'descend',
            ],
        ];

        return $this;
    }

    /**
     * Add field to sort results.
     */
    public function andSort(string $field, bool $ascending = true): FluentFM
    {
        $this->query['sort'] = [
            ...$this->query['sort'],
            [
                'fieldName' => $field,
                'sortOrder' => $ascending ? 'ascend' : 'descend',
            ],
        ];

        return $this;
    }

    /**
     * Sort results by field and value list.
     */
    public function sortByValueList(string $field, string $valueList): FluentFM
    {
        $this->query['sort'] = [
            [
                'fieldName' => $field,
                'sortOrder' => $valueList,
            ],
        ];
        return $this;
    }

    /**
     * and sort results by field and value list.
     */
    public function andSortByValueList(string $field, string $valueList): FluentFM
    {
        $this->query['sort'] = [
            ...$this->query['sort'],
            [
                'fieldName' => $field,
                'sortOrder' => $valueList,
            ],
        ];
        return $this;
    }

    /**
     * Sort results descending by field.
     */
    public function sortDesc(string $field): FluentFM
    {
        $this->sort($field, false);

        return $this;
    }

    /**
     * Include portal data in results.
     */
    public function withPortals(): FluentFM
    {
        $this->with_portals = true;

        return $this;
    }

    /**
     * Don't include portal data in results.
     */
    public function withoutPortals(): FluentFM
    {
        $this->with_portals = false;

        return $this;
    }

    /**
     * @param string $field
     */
    public function whereEmpty($field): FluentFM
    {
        return $this->where($field, '');
    }

    /**
     * @param       string $field
     * @param   int|string ...$params
     */
    public function where($field, ...$params): FluentFM
    {
        switch (count($params)) {
            case 1:
                $value = '=' . $params[0];
                break;
            case 2:
                $value = $params[0] . $params[1];
                break;
            default:
                $value = '*';
        }

        $this->query['query'][0][$field] = $value;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function whereCriteria($criteria): FluentFM
    {
        $this->query['query'] = $criteria;

        return $this;
    }

    public function whereNotEmpty(string $field): FluentFM
    {
        return $this->has($field);
    }

    public function has(string $field): FluentFM
    {
        return $this->where($field, '*');
    }

    /**
     * @return array<string,mixed>
     */
    public function queryString(): array
    {
        $output = [];

        foreach ($this->query as $param => $value) {
            if (strpos($param, 'script') !== 0) {
                $param = '_' . $param;
            }

            $output[$param] = $value;
        }

        $output['_query'] = null;

        return $output;
    }

    /**
     * Run FileMaker script with param before requested action.
     *
     * @param null|mixed $param
     */
    public function prerequest(string $script, $param = null): FluentFM
    {
        return $this->script($script, $param, 'prerequest');
    }

    /**
     * Run FileMaker script with param. If no type specified script will run
     * after requested action and sorting is complete.
     *
     * @param null|mixed $param
     */
    public function script(string $script, $param = null, ?string $type = null): FluentFM
    {
        $base = 'script';

        if ($type) {
            $base .= '.' . $type;
        }

        $this->query[$base]            = $script;
        $this->query[$base . '.param'] = $param;

        return $this;
    }

    /**
     * Run FileMaker script with param after requested action but before sort.
     *
     * @param null|mixed $param
     */
    public function presort(string $script, $param = null): FluentFM
    {
        return $this->script($script, $param, 'presort');
    }

    /**
     * Exclude records that have their deleted_at field set.
     */
    public function withoutDeleted(): FluentFM
    {
        $this->with_deleted = false;

        return $this;
    }

    /**
     * Include records that have their deleted_at field set.
     */
    public function withDeleted(): FluentFM
    {
        $this->with_deleted = true;

        return $this;
    }

    /**
     * Clear query parameters.
     */
    protected function clearQuery(): FluentFM
    {
        $this->query = [
            'limit'                   => null,
            'offset'                  => null,
            'sort'                    => null,
            'query'                   => null,
            'script'                  => null,
            'script.param'            => null,
            'script.prerequest'       => null,
            'script.prerequest.param' => null,
            'script.presort'          => null,
            'script.presort.param'    => null,
        ];

        $this->with_portals = false;
        $this->with_deleted = true;

        return $this;
    }
}
