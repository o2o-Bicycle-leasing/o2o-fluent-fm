<?php

namespace o2o\FluentFM\Connection;

use o2o\FluentFM\Contract\FluentFM;
use function count;

/**
 * Trait FluentQuery.
 */
trait FluentQuery
{

    /** @var array */
    protected $query;

    /** @var bool */
    protected $with_portals = false;

    /** @var bool */
    protected $with_deleted = true;

    public function limit(int $limit) : FluentFM
    {
        $this->query[ 'limit' ] = $limit;

        return $this;
    }

    public function offset(int $offset) : FluentFM
    {
        $this->query[ 'offset' ] = $offset;

        return $this;
    }

    public function sortAsc(string $field) : FluentFM
    {
        $this->sort($field);

        return $this;
    }

    public function sort(string $field, bool $ascending = true) : FluentFM
    {
        $this->query[ 'sort' ] = [
            [
                'fieldName' => $field,
                'sortOrder' => $ascending ? 'ascend' : 'descend',
            ],
        ];

        return $this;
    }

    public function sortDesc(string $field) : FluentFM
    {
        $this->sort($field, false);

        return $this;
    }

    public function withPortals() : FluentFM
    {
        $this->with_portals = true;

        return $this;
    }

    public function withoutPortals() : FluentFM
    {
        $this->with_portals = false;

        return $this;
    }

    public function whereEmpty($field) : FluentFM
    {
        return $this->where($field, '');
    }

    public function where($field, ...$params) : FluentFM
    {
        switch (count($params)) {
            case 1:
                $value = '='.$params[ 0 ];
                break;
            case 2:
                $value = $params[ 0 ].$params[ 1 ];
                break;
            default:
                $value = '*';
        }

        $this->query[ 'query' ][ 0 ][ $field ] = $value;

        return $this;
    }

    public function whereCriteria($criteria) : FluentFM
    {
        $this->query['query'] = $criteria;

        return $this;
    }

    public function whereNotEmpty(string $field) : FluentFM
    {
        return $this->has($field);
    }

    public function has(string $field) : FluentFM
    {
        return $this->where($field, '*');
    }

    public function queryString() : array
    {
        $output = [];

        foreach ($this->query as $param => $value) {
            if (strpos($param, 'script') !== 0) {
                $param = '_'.$param;
            }

            $output[ $param ] = $value;
        }

        $output[ '_query' ] = null;

        return $output;
    }

    public function prerequest(string $script, $param = null) : FluentFM
    {
        return $this->script($script, $param, 'prerequest');
    }

    public function script(string $script, $param = null, string $type = null) : FluentFM
    {
        $base = 'script';

        if ($type) {
            $base .= '.'.$type;
        }

        $this->query[ $base ]          = $script;
        $this->query[ $base.'.param' ] = $param;

        return $this;
    }

    public function presort(string $script, $param = null) : FluentFM
    {
        return $this->script($script, $param, 'presort');
    }

    public function withoutDeleted() : FluentFM
    {
        $this->with_deleted = false;

        return $this;
    }

    public function withDeleted() : FluentFM
    {
        $this->with_deleted = true;

        return $this;
    }

    protected function clearQuery() : FluentFM
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
