<?php
/**
 * This file is part of {@see arabcoders\session} package.
 *
 * (c) 2013-2016 Abdul.Mohsen B. A. A.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace arabcoders\session\Adapters;

use arabcoders\session\Interfaces\Adapter as AdapterInterface;

/**
 * Adapter: PDO
 *
 * @package arabcoders\session
 * @author  Abdul.Mohsen B. A. A. <admin@arabcoders.org>
 */
class Mysql implements AdapterInterface
{
    /**
     * @var \PDO
     */
    protected $pdo;

    /**
     * @var string Table schema
     */
    private $schema = [
        'id'   => 'id',
        'data' => 'data',
        'time' => 'time',
    ];

    /**
     * @var string Table name
     */
    CONST TABLE = 'sessions';

    /**
     * @var string
     */
    private $table = self::TABLE;

    /**
     * Class Constructor
     *
     * @param \PDO   $pdo PDO Instance.
     * @param string $table
     * @param array  $options
     */
    public function __construct( \PDO $pdo, string $table = self::TABLE, array $options = [ ] )
    {
        $this->pdo = $pdo;

        $this->table = $table;

        if ( array_key_exists( 'schema', $options ) )
        {
            $this->schema = $options['schema'];
        }
    }

    public function open( $path, $variableName )
    {
        return true;
    }

    public function close()
    {
        return true;
    }

    public function read( $id )
    {
        $dataFieldName = $this->schema['data'];

        $sql = "SELECT 
                    {$dataFieldName} as data 
                FROM 
                    {$this->table} 
                WHERE 
                    " . $this->schema['id'] . " = :id
        ";

        $stmt = $this->pdo->prepare( $sql );
        $stmt->execute( [ 'id' => $id ] );
        $result = $stmt->fetch()['data'];

        return (string) ( !empty( $result ) ) ? base64_decode( $result ) : '';
    }

    public function write( $id, $data )
    {
        $sql = "INSERT INTO 
                    {$this->table}
                    (" . $this->schema['id'] . ", " . $this->schema['data'] . ", " . $this->schema['time'] . ")
                    VALUES (:id, :data, :time) 
                ON DUPLICATE KEY UPDATE
                    " . $this->schema['data'] . " = VALUES(" . $this->schema['data'] . "),
                    " . $this->schema['time'] . " = VALUES(" . $this->schema['time'] . ")
        ";

        try
        {
            $stmt = $this->pdo->prepare( $sql );

            $status = $stmt->execute(
                [
                    'id'   => $id,
                    'time' => time(),
                    'data' => base64_encode( $data )
                ] );
        }
        catch ( \PDOException $e )
        {
            $msg = sprintf( 'PDOException was thrown when trying to write the session data: %s', $e->getMessage() );

            throw new \RuntimeException( $msg, $e->getCode(), $e );
        }

        return ( $status ) ? true : false;
    }

    public function destroy( $id )
    {
        $sql = "DELETE FROM 
                    {$this->table} 
                WHERE 
                    " . $this->schema['id'] . " = :id
        ";

        try
        {
            $stmt = $this->pdo->prepare( $sql );
            $stmt->execute(
                [
                    'id' => $id,
                ] );
        }
        catch ( \PDOException $e )
        {
            $msg = sprintf( 'PDOException was thrown when trying to write the session data: %s', $e->getMessage() );

            throw new \RuntimeException( $msg, $e->getCode(), $e );
        }

        return true;
    }

    public function gc( $maxLifeTime )
    {
        $sql = "DELETE FROM 
                    {$this->table} 
                WHERE 
                    " . $this->schema['time'] . " < :time
        ";

        try
        {
            $stmt = $this->pdo->prepare( $sql );
            $stmt->execute(
                [
                    'time' => ( time() - intval( $maxLifeTime ) )
                ] );
        }
        catch ( \PDOException $e )
        {
            $msg = sprintf( 'PDOException was thrown when trying to write the session data: %s', $e->getMessage() );

            throw new \RuntimeException( $msg, $e->getCode(), $e );
        }
    }
}
