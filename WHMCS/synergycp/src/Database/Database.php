<?php

namespace Scp\Whmcs\Database;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Query\Builder;

class Database
{
    public function update($table, array $updates, array $where)
    {
        return update_query($table, $updates, $where);
    }

    /**
     * @param  string $table
     *
     * @return Builder
     */
    public function table($table)
    {
        return Capsule::table($table);
    }
}
