<?php

namespace MeekroDB;

/**
 * Class WhereClause
 *
 * @author Sergey Tsalkov https://github.com/SergeyTsalkov
 * @author Michal Stefanak
 * @package MeekroDB
 * @see https://meekro.com/docs/whereclause.html
 * @see https://github.com/SergeyTsalkov/meekrodb
 * @see https://github.com/stefanak-michal/meekrodb
 */
class WhereClause
{
    public $type = 'and'; //AND or OR
    public $negate = false;
    public $clauses = array();

    function __construct($type)
    {
        $type = strtolower($type);
        if ($type !== 'or' && $type !== 'and') throw new MeekroDBException('you must use either WhereClause(and) or WhereClause(or)');
        $this->type = $type;
    }

    function add()
    {
        $args = func_get_args();
        $sql = array_shift($args);

        if ($sql instanceof WhereClause) {
            $this->clauses[] = $sql;
        } else {
            $this->clauses[] = array('sql' => $sql, 'args' => $args);
        }
    }

    function negateLast()
    {
        $i = count($this->clauses) - 1;
        if (!isset($this->clauses[$i])) return;

        if ($this->clauses[$i] instanceof WhereClause) {
            $this->clauses[$i]->negate();
        } else {
            $this->clauses[$i]['sql'] = 'NOT (' . $this->clauses[$i]['sql'] . ')';
        }
    }

    function negate()
    {
        $this->negate = !$this->negate;
    }

    function addClause($type)
    {
        $r = new WhereClause($type);
        $this->add($r);
        return $r;
    }

    function count()
    {
        return count($this->clauses);
    }

    function textAndArgs()
    {
        $sql = array();
        $args = array();

        if (count($this->clauses) == 0) return array('(1)', $args);

        foreach ($this->clauses as $clause) {
            if ($clause instanceof WhereClause) {
                list($clause_sql, $clause_args) = $clause->textAndArgs();
            } else {
                $clause_sql = $clause['sql'];
                $clause_args = $clause['args'];
            }

            $sql[] = "($clause_sql)";
            $args = array_merge($args, $clause_args);
        }

        if ($this->type == 'and') $sql = sprintf('(%s)', implode(' AND ', $sql));
        else $sql = sprintf('(%s)', implode(' OR ', $sql));

        if ($this->negate) $sql = '(NOT ' . $sql . ')';
        return array($sql, $args);
    }
}
