<?php

namespace Scp\Whmcs\Database;

class Database
{
    public function update($table, array $updates, array $where)
    {
        return update_query($table, $updates, $where);
    }
}
