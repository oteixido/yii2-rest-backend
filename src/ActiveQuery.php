<?php
namespace oteixido\rest;

use Yii;
use yii\helpers\ArrayHelper;
use yii\db\QueryInterface;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;

use oteixido\rest\http\HttpResponse;

/**
 * ActiveQuery represents a HTTP REST query associated with an Active Record class.
 *
 * Rest server must implement a query interface similar to https://github.com/typicode/json-server does.
 *
 * Filter
 *
 *   GET /posts?title=json-server&author=typicode
 *   GET /posts?id=1&id=2
 *
 * Paginate
 *   Use _page and optionally _limit to paginate returned data.
 *   In the Link header you'll get first, prev, next and last links.
 *
 *   GET /posts?_page=7
 *   GET /posts?_page=7&_limit=20
 *
 *   10 items are returned by default
 *
 * Sort
 *   Add _sort and _order (ascending order by default)
 *
 *   GET /posts?_sort=views&_order=asc
 *   GET /posts/1/comments?_sort=votes&_order=asc
 *
 *   For multiple fields, use the following format:
 *                                                                                                                                                                                                                                                      GET /posts?_sort=user,views&_order=desc,asc
 * Slice
 *   Add _start and _end or _limit (an X-Total-Count header is included in the response)
 *
 *   GET /posts?_start=20&_end=30
 *   GET /posts/1/comments?_start=20&_end=30
 *   GET /posts/1/comments?_start=20&_limit=10
 *
 *   Works exactly as Array.slice (i.e. _start is inclusive and _end exclusive)
 *
 * Operators
 *   Add _gte or _lte for getting a range
 *
 *   GET /posts?views_gte=10&views_lte=20
 *
 *   Add _ne to exclude a value
 *
 *   GET /posts?id_ne=1
 *
 * @author Oriol Teixidó <oriol.teixido@gmail.com>
 */
class ActiveQuery extends Component implements QueryInterface
{
    public static $DEFAULT_LIMIT = 10;

    /**
     * @var string the model class name. This property must be set.
     */
    public $modelClass;

    /**
     * @var int maximum number of records to be returned. If not set or less than 0, it means no limit.
    */
    private $_limit = 0;

    /**
     * @var int zero-based offset from where the records are to be returned.
     * If not set or less than 0, it means starting from the beginning.
     */
    private $_offset = 0;

    /**
     * @var array how to sort the query results.
     * The array keys are the columns to be sorted by, and the array values are
     * the corresponding sort directions which can be either 'asc' or 'desc'.
     */
    private $_sort = [];

    /**
     * @var array conditions for query. Each condition is specified as an array
     * with the following format:
     *
     * [operator, column, value]`
     */
    private $_condition = [];

    /**
    * @var bool whether to make a new REST request to repopulate models.
    */
    private $_populate = true;

    /**
     * @var array models returned on last get request.
     */
    private $_models = [];

    /**
     * @var int total number of records.
    */
    private $_total = 0;

    /**
     * @var string|callable the name of the column by which the query results should be indexed by.
     * This can also be a callable (e.g. anonymous function) that returns the index value based on the given
     * row data.
     */
    private $_indexBy = null;

    /**
    * @var bool whether to emulate query execution, preventing any interaction with data storage.
    */
    private $_emulateExecution = false;

    public $uri = '';
    public $multiple = true;

    public function __construct($modelClass, $config = [])
    {
        if (empty($modelClass)) {
            throw new InvalidConfigException('ModelClass can not be empty.');
        }
        $this->modelClass = $modelClass;
        $this->uri = $modelClass::getListUri();
        parent::__construct($config);
    }

    public function setMultiple($multiple)
    {
        $this->multiple = $multiple;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function all($db = null)
    {
        $this->_populate();
        return $this->_models;
    }

    /**
     * @inheritdoc
     */
    public function one($db = null)
    {
        $this->_populate();
        if (count($this->_models) == 0) {
            return null;
        }
        return $this->_models[0];
    }

    /**
     * @inheritdoc
     */
    public function exists($db = null)
    {
        $this->_populate();
        return ($this->_total != 0);

    }

    public function count($q = '*', $db = null)
    {
        $this->_populate();
        return $this->_total;
    }

    /**
     * @inheritdoc
     */
    public function where($condition)
    {
        $this->_condition = [];
        return $this->andWhere($condition);
    }

    /**
     * @inheritdoc
     */
    public function andWhere($condition)
    {
        if (!count($condition))
            return $this;

        $this->_populate = true;
        $this->_condition[] = $this->_assocToCondition($condition);
        return $this;
    }

    public function orWhere($condition)
    {
        throw new NotSupportedException('Method '.__METHOD__.' not supported.');
    }

    public function orFilterWhere(array $condition)
    {
        throw new NotSupportedException('Method '.__METHOD__.' not supported.');
    }

    /**
     * @inheritdoc
     */
    public function filterWhere(array $condition)
    {
        $this->_condition = [];
        if (!count($condition))
            return $this;

        list($operator, $name, $value) = $this->_assocToCondition($condition);
        if (empty($value)) {
            return $this;
        }
        return $this->where($condition);
    }

    /**
     * @inheritdoc
     */
    public function andFilterWhere(array $condition)
    {
        if (empty($condition))
            return $this;

        list($operator, $name, $value) = $this->_assocToCondition($condition);
        if (empty($value)) {
            return $this;
        }
        return $this->andWhere($condition);
    }

    /**
     * @inheritdoc
     */
    public function orderBy($columns)
    {
        $this->_sort = [];
        return $this->addOrderBy($columns);
    }

    /**
     * @inheritdoc
     */
    public function addOrderBy($columns)
    {
        $this->_populate = true;
        if (is_string($columns)) {
            $columns = [ $columns => 'asc' ];
        }
        $this->_sort = array_merge($this->_sort, $columns);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function limit($limit)
    {
        $this->_populate = $this->_populate || ($limit != $this->_limit);
        $this->_limit = $limit;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function offset($offset)
    {
        $this->_populate = $this->_populate || ($offset != $this->_offset);
        $this->_offset = $offset;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function indexBy($column)
    {
        $this->_populate = true;
        $this->_indexBy = $column;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function emulateExecution($value = true)
    {
        $this->_emulateExecution = $value;
    }

    private function _populate()
    {
        if ($this->_emulateExecution) {
            $this->_models = [];
            $this->_total = 0;
            return;
        }

        if (!$this->_populate) {
            return;
        }

        $httpResponse = $this->_request();
        $this->_models = $this->_toModels(json_decode($httpResponse->getContent(), true));
        $this->_total = ($httpResponse->getHeader('X-Total-Count') ? $httpResponse->getHeader('X-Total-Count') : 0);
        $this->_populate = false;
    }

    private function _request()
    {
        $modelClass = $this->modelClass;
        $client = $modelClass::getDb();
        $query = array_merge($this->_sortAsQuery(), $this->_paginateAsQuery(), $this->_conditionAsQuery());
        $url = $this->uri . (count($query) ? '?' . http_build_query($query) : '');
        return $client->get($url, $validCodes = [ HttpResponse::HTTP_OK ]);
    }

    private function _toModels($elems)
    {
        $modelClass = $this->modelClass;
        $models = [];
        foreach($elems as $elem) {
            $model = new $modelClass();
            $modelClass::populateRecord($model, $elem);
            if ($this->_indexBy === null) {
                $models[] = $model;
            }
            else {
                $models[$this->_getKeyOfIndexBy($model)] = $model;
            }
        }
        return $models;
    }

    private function _getKeyOfIndexBy($model)
    {
        $indexBy = $this->_indexBy;
        return (is_string($indexBy) ? $model->$indexBy : call_user_func($indexBy, $model));
    }

    private function _sortAsQuery()
    {
        if (count($this->_sort) == 0) {
            return [];
        }

        $query = [
            '_sort' => [],
            '_order' => [],
        ];
        foreach($this->_sort as $attribute => $order) {
            $query['_sort'][] = $attribute;
            $query['_order'][] = ($order == SORT_DESC ? 'desc' : 'asc');
        }
        $query['_sort'] = join(',', $query['_sort']);
        $query['_order'] = join(',', $query['_order']);
        return $query;
    }

    private function _paginateAsQuery()
    {
        $query = [];
        if ($this->_offset && !$this->_limit) {
            $this->_limit = self::$DEFAULT_LIMIT;
        }
        if ($this->_offset) {
            $query['_page'] = floor($this->_offset / $this->_limit) + 1;;
        }
        if ($this->_limit) {
            $query['_limit'] = $this->_limit;
        }
        return $query;
    }

    private function _conditionAsQuery()
    {
        $query = [];
        foreach($this->_condition as $condition) {
            list($operator, $name, $value) = $condition;
            switch($operator) {
                case '=':
                    $query[$name] = $value;
                    break;
                case '!=':
                    $query[$name.'_ne'] = $value;
                    break;
                case '>=':
                    $query[$name.'_gte'] = $value;
                    break;
                case '<=':
                    $query[$name.'_lte'] = $value;
                    break;
                case 'like':
                    $query[$name.'_like'] = $value;
                    break;
            }
        }
        return $query;
    }

    private function _assocToCondition($condition)
    {
        if (ArrayHelper::isAssociative($condition)) {
            if (count($condition)) {
                return [ '=', array_keys($condition)[0], array_values($condition)[0] ];
            }
        }
        return $condition;
    }
}
