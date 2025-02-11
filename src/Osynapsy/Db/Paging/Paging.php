<?php
namespace Osynapsy\Db\Paging;

class Paging
{
    const META_PAGE_DIMENSION = 'pageDimension';
    const META_REQUEST_PAGE_DIMENSION = 'requestPageDimension';
    const META_PAGE_TOTAL = 'pageTotal';
    const META_PACE_CURRENT = 'pageCurrent';
    const META_REQUEST_PAGE = 'requestPage';
    const META_PAGING_QUERY = 'pagingQuery';
    const META_PAGING_COUNT_QUERY = 'pagingCountQuery';

    protected $dbCn;
    protected $errors = [];
    protected $filters = [];
    protected $query;
    protected $queryParameters = [];
    protected $orderBy;
    protected $meta = [
        'numberOfRows' => 0,
        'pageCurrent' => 1,
        'pageCurrentDimension' => 10,
        'pageDimension' => 10,
        'pageTotal' => 0,
        'requestPage' => 1
    ];

    public function __construct($dbCn, $rawQuery, $queryParameters, $pageDimension = 10)
    {
        $this->dbCn = $dbCn;
        $this->query = $rawQuery;
        $this->queryParameters = $queryParameters;
        $this->setPageDimension(empty($pageDimension) ? 10 : $pageDimension);
    }

    public function getDataset()
    {
        $rawQuery = $this->query;
        $queryParameters = $this->queryParameters;
        $requestPage = $this->getMeta(self::META_REQUEST_PAGE);
        $where = empty($this->filters) ? '' : $this->whereClauseFactory($this->filters);
        $numberOfRows = $this->countRows($rawQuery,  $queryParameters, $where);
        $pageDimension = $this->getMeta(self::META_REQUEST_PAGE_DIMENSION) ?: $numberOfRows;
        $this->metaFactory($numberOfRows, $pageDimension, $requestPage);
        $query = $this->pagingQueryFactory($rawQuery, $where, $this->orderBy);
        try {
            $this->setMeta(self::META_PAGING_QUERY, $query, $queryParameters);
            $dataset = $this->dbCn->execQuery($query, $queryParameters, 'ASSOC');
            return empty($dataset) ? [] : $dataset;
        } catch (\Exception $e) {
            die($this->formatSqlErrorMessage($query, $e->getMessage()));
        }
    }

    protected function countRows($rawQuery, $queryParameters, $whereCondition)
    {
        $countQuery = sprintf("SELECT COUNT(*) FROM (%s) a %s", $rawQuery, $whereCondition);
        $this->setMeta(self::META_PAGING_COUNT_QUERY, $countQuery);
        try {
            return $this->dbCn->execUnique($countQuery, $queryParameters);
        } catch(\Exception $e) {
            $this->errors[] = sprintf('<pre>%s\n%s</pre>', $countQuery, $e->getMessage());
        }
        return 0;
    }

    private function metaFactory($numberOfRows, $pageDimension, $requestPage)
    {
        $pageCurrent = $this->getMeta(self::META_PACE_CURRENT);
        $pageTotal = !empty($numberOfRows) ? ceil($numberOfRows / max($pageDimension, 1)) : 0;
        switch ($requestPage) {
            case 'first':
                $pageCurrent = 1;
                break;
            case 'last' :
                $pageCurrent = $pageTotal;
                break;
            default:
                $pageCurrent = min($requestPage, $pageTotal);
                break;
        }
        $this->setMeta('numberOfRows', $numberOfRows);
        $this->setMeta(self::META_PACE_CURRENT, $pageCurrent);
        $this->setMeta(self::META_PAGE_TOTAL, $pageTotal);
    }

    protected function whereConditionFactory($filters)
    {
        $filter = [];
        $i = 0;
        foreach ($filters as $field => $value) {
            if (is_null($value)) {
                $filter[] = $field;
                continue;
            }
            $filter[] = sprintf("%s = %s", $field, $this->dbCn->getType() == 'oracle' ? ':'.$i : '?');
            $this->par[] = $value;
            $i++;
        }
        return sprintf(" WHERE %s", implode(' AND ',$filter));
    }

    protected function pagingQueryFactory($sql, $where, $orderBySequence)
    {
        $orderByClause = sprintf('ORDER BY %s', $orderBySequence ?: '1 DESC');
        $pageCurrent = $this->getMeta(self::META_PACE_CURRENT);
        $pageDimension = $this->getMeta(self::META_REQUEST_PAGE_DIMENSION);
        switch ($this->dbCn->getType()) {
            case 'oracle':
                return $this->pagingQueryOracleFactory($sql, $where, $orderByClause, $pageCurrent, $pageDimension);
            case 'pgsql':
                return $this->pagingQueryPgSqlFactory($sql, $where, $orderByClause, $pageCurrent, $pageDimension);
            case 'dblib':
            case 'sqlsrv':
                return $this->pagingQuerySqlSrvFactory($sql, $where, $orderBySequence, $pageCurrent, $pageDimension);
            default:
                return $this->pagingQueryMySqlFactory($sql, $where, $orderByClause, $pageCurrent, $pageDimension);
        }
    }

    protected function pagingQueryMySqlFactory($query, $where, $orderBy, $pageCurrent, $pageDimension)
    {
        $sql = sprintf("SELECT a.* FROM (%s) a %s %s", $query, $where, $orderBy);
        if (empty($pageDimension)) {
            return $sql;
        }
        $startFrom = max(0, ($pageCurrent - 1) * $pageDimension);
        $sql .= sprintf("\nLIMIT %s, %s", $startFrom, $pageDimension);
        return $sql;
    }

    protected function pagingQueryPgSqlFactory($rawQuery, $where, $orderBy, $pageCurrent, $pageDimension)
    {
        $query = sprintf("SELECT a.* FROM (%s) a %s %s", $rawQuery, $where, $orderBy);
        if (!empty($pageDimension)) {
            $startFrom = max(0, ($pageCurrent - 1) * $pageDimension);
            $query .= sprintf("\nLIMIT %s OFFSET %s", $pageDimension, $startFrom);
        }
        return $query;
    }

    protected function pagingQuerySqlSrvFactory($rawQuery, $where, $orderBy, $pageCurrent, $pageDimension)
    {
        $query = sprintf("SELECT a.* FROM (%s) a %s Order By %s", $rawQuery, $where, $orderBy ?? '1');
        if (!empty($pageDimension)) {
            $startFrom = max(0, ($pageCurrent - 1) * $pageDimension);
            $query .= sprintf("\nOFFSET %s ROWS FETCH NEXT %s ROWS ONLY;", $startFrom, $pageDimension);
        }
        return $query;
    }

    protected function pagingQueryOracleFactory($rawQuery, $where, $orderBy, $pageCurrent, $pageDimension)
    {
        $query = sprintf(
            'SELECT a.*
                FROM (
                    SELECT b.*,rownum as "_rnum"
                    FROM (
                        SELECT a.* FROM (%s) a
                        %s
                        %s
                    ) b
                ) a ',
            $rawQuery,
            $where,
            $orderBy
        );
        if (!empty($pageDimension)) {
            $startFrom = (($pageCurrent - 1) * $pageDimension) + 1 ;
            $endTo = ($pageCurrent * $pageDimension);
            $query .=  sprintf('WHERE "_rnum" BETWEEN %s AND %s', $startFrom, $endTo);
        }
        return $query;
    }

    private function formatSqlErrorMessage($sql, $rawerror)
    {
        $error = str_replace($sql, '', $rawerror);
        return sprintf('Query error :<pre style="background-color: #fefefe; border: 1px solid #ddd; padding: 5px;">%s</pre>Error message: <div>%s</div>', $sql, $error);
    }

    public function getMeta($key = null)
    {
        return empty($key) ? $this->meta : $this->meta[$key];
    }

    public function getOrderBy()
    {
        return $this->orderBy;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function setMeta($key, $value, array $parameters = [])
    {
        foreach ($parameters as $parId => $par) {
            $value = str_replace([':'.$parId, $parId], ["'$par'"], $value);
        }
        $this->meta[$key] = $value;
    }

    public function setOrderBy($field)
    {
        $this->orderBy = str_replace(['][', '[', ']'], [',' ,'' ,''], $field);
        return $this;
    }

    public function setPageCurrent($pageCurrent)
    {
        $this->meta[self::META_PACE_CURRENT] = min((int) $pageCurrent, 1);
    }

    public function setPageDimension($pageDimension)
    {
        $this->setRequestPageDimension($pageDimension);
        $this->setMeta(self::META_PAGE_DIMENSION, (int) $pageDimension);
    }

    public function setRequestPageDimension($requestPageDimension)
    {
         $this->setMeta(self::META_REQUEST_PAGE_DIMENSION, (int) $requestPageDimension);
    }

    public function setRequestPage($requestPage)
    {
        $this->setMeta(self::META_REQUEST_PAGE, $requestPage);
    }

    public function formatOrderBy($field)
    {
        return str_replace(['][', '[', ']'], [',' ,'' ,''], $field);
    }
}