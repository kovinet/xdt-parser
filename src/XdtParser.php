<?php

namespace Kovinet\XdtParser;

class XdtParser
{
    /**
     * Holds the content unparsed rows
     * @var array
     */
    private $xdtRows = [];

    /** @var array */
    private $parsedRows = [];

    /**
     * @param string $content
     * @param array $fieldsMap
     * @return XdtParser
     */
    public static function make(string $content)
    {
        return new static($content);
    }

    /**
     * XdtParser constructor.
     * @param string $content
     * @param array $fieldsMap
     */
    private function __construct(string $content)
    {
        $this->xdtRows = explode(PHP_EOL, $content);
        $this->parseXdtRows();
    }

    private function parseXdtRows()
    {
        foreach ($this->xdtRows as $row) {
            if ($row === '') {
                continue;
            }
            $this->parsedRows[] = $this->parseSingle($row);
        }
    }

    /**
     * @param string $string
     * @return array
     */
    public function parseSingle(string $string)
    {
        $matched = preg_match('/^\\r?\\n?(\\d{3})(\\d{4})(.*?)\\r?\\n?$/', $string, $matches);

        if (!$matched) {
            throw new CorruptedXdt;
        }

        return [
            'length' => $matches[1] ? intval($matches[1]) : null,
            'key' => $matches[2] ?? null,
            'value' => $matches[3] ?? null
        ];
    }

    /**
     * @param string $field
     * @return array|mixed|null
     */
    public function find(string $field)
    {
        $result = [];

        foreach ($this->parsedRows as $row) {
            if ($row['key'] === $this->getKey($field)) {
                $result[] = $row['value'];
            }
        }

        switch (count($result)) {
            case 0:
                return null;
            case 1:
                return $result[0];
            default:
                return $result;
        }
    }

    /**
     * @return array
     */
    public function getGrouped()
    {
        $result = [];

        $groupIdentifier = '8000';
        $i = -1;
        foreach ($this->parsedRows as $row) {
            if ($row['key'] === '8000') {
                $i++;
            }

            $result[$i][] = $row;
//            $result[$i][] = $row;
//

        }

        return $result;
    }

    /**
     * @return array
     */
    public function all()
    {
        $result = [];

        foreach ($this->parsedRows as $row) {
            $field = array_search($row['key'], $this->fieldsMap) ?: $row['key'];
            $result[$field] = $this->find($field);
        }

        return $result;
    }

    /**
     * @param array $fieldsMap
     * @return XdtParser
     */
    public function setFieldsMap(array $fieldsMap)
    {
        $this->fieldsMap = $fieldsMap;
        return $this;
    }

    /**
     * @return array
     */
    public function getFieldsMap()
    {
        return $this->fieldsMap;
    }

    /**
     * @param array $fields
     */
    public function addFieldsMap(array $fields)
    {
        foreach ($fields as $field => $key) {
            $this->fieldsMap[$field] = $key;
        }
    }

    /**
     * @param array $fields
     */
    public function removeFields(array $fields)
    {
        foreach ($fields as $field) {
            unset($this->fieldsMap[$field]);
        }
    }

    /**
     * @return array
     */
    public function getXdtRows(): array
    {
        return $this->xdtRows;
    }
}
