<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Osynapsy\Db\Driver\DbOci;

final class DboOracleTest extends TestCase
{
    protected $cn;

    private function getConnection()
    {
        if (empty($this->cn)) {
            $this->cn = new DbOci('oracle:192.168.1.8:XE:pizzone:abile2008:1521');
        }
        return $this->cn;
    }

    public function testConnection()
    {
        $handle = $this->getConnection();
        $this->assertIsObject($handle);
    }

    public function testSelect()
    {
        $res = $this->getConnection()->select('TBL_UTENTE', ['ID'], ['LOGIN' => 'sup']);
        $this->assertNotEmpty($res);
    }
}