<?php


namespace o2o\FluentFM\Connection;


class UrlGenerator
{
    /** @var string */
    private $layout;

    public function __construct(string $layout)
    {
        $this->layout = $layout;
    }

    public function records(): string
    {
        return 'layouts/' . $this->layout . '/records';
    }
    
    public function record(int $id): string
    {
        return 'layouts/' . $this->layout . '/records/' . $id;
    }

    public function find(): string
    {
        return 'layouts/' . $this->layout . '/_find';
    }

    public function globals(): string
    {
        return 'globals';
    }

    public function container(string $field, int $recordId): string
    {
        return sprintf(
            'layouts/%s/records/%s/containers/%s/1',
            $this->layout,
            $recordId,
            $field
        );
    }
}
