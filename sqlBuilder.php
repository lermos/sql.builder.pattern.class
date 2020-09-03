<?php

namespace Lermos\Builder\SQLBuilder;


/*
 * The Builder interface with the set of methods.
 * It assembles an SQL query.
 *
 * Any construction step returns the current builder object to
 * allow this style of callback: $builder->select(...)->where(...)
 */
interface SQLBuilder
{
    public function select(string $table, array $fields): SQLBuilder;

    public function where(string $field, string $value, string $operator = '='): SQLBuilder;

    public function limit(int $start, int $offset): SQLBuilder;

    // And other SQL syntax methods can be added here

    public function getSQL(): string;
}

/*
 * Each type of Builder matchs to a relative SQL syntax and may implement
 * the builder steps differently from the others.
 *
 * Below Builder builds SQL queries for MySQL engine.
 */
class MySQLBuilder implements SQLBuilder
{
    protected $query;

    protected function reset(): void
    {
        $this->query = new \stdClass;
    }

    /*
      Basic SELECT query.
     */
    public function select(string $table, array $fields): SQLBuilder
    {
        $this->reset();
        $this->query->base = "SELECT " . implode(", ", $fields) . " FROM " . $table;
        $this->query->type = 'select';

        return $this;
    }

    /**
     * A WHERE condition.
     */
    public function where(string $field, string $value, string $operator = '='): SQLBuilder
    {
        if (!in_array($this->query->type, ['select', 'update', 'delete'])) {
            throw new \Exception("WHERE can be added to SELECT, UPDATE OR DELETE only!");
        }
        $this->query->where[] = "$field $operator '$value'";

        return $this;
    }

    /**
     * A LIMIT constraint.
     */
    public function limit(int $start, int $offset): SQLBuilder
    {
        if (!in_array($this->query->type, ['select'])) {
            throw new \Exception("LIMIT can be added to SELECT only!");
        }
        $this->query->limit = " LIMIT " . $start . ", " . $offset;

        return $this;
    }

    /**
     * Get the overall query string.
     */
    public function getSQL(): string
    {
        $query = $this->query;
        $sql = $query->base;
        if (!empty($query->where)) {
            $sql .= " WHERE " . implode(' AND ', $query->where);
        }
        if (isset($query->limit)) {
            $sql .= $query->limit;
        }
        $sql .= ";";

        return $sql;
    }
}

/**
 * This Builder is compatible with PostgreSQL. Still different from Mysql.
 */
class PostgresQueryBuilder extends MySQLBuilder
{
    public function limit(int $start, int $offset): SQLBuilder
    {
        parent::limit($start, $offset);

        $this->query->limit = " LIMIT " . $start . " OFFSET " . $offset;

        return $this;
    }

}

function clientSide(SQLBuilder $queryBuilder)
{
    $query = $queryBuilder
        ->select("users", ["login", "password", "gender", "phone"])
        ->where("zipcode", "78005", ">")
        ->where("zipcode", "94203", "<")
        ->limit(3, 8)
        ->getSQL();

    echo $query;

}

/**
 * Btw query builder type depends on a current php configuration and
 * the environment settings.
 */
// if ($_ENV['db_engine'] == 'postgresql') {
//     $builder = new PostgresQueryBuilder(); } else {
//     $builder = new MySQLBuilder; }
//
// clientSide($builder);

//test echo
echo "MySQL query builder:\n";
clientSide(new MySQLBuilder);

echo "\n\n";

echo "PostgresSQL query builder:\n";
clientSide(new PostgresQueryBuilder);


