<?php
/**
 * This file is part of {@see arabcoders\session} package.
 *
 * (c) 2013-2016 Abdul.Mohsen B. A. A.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace arabcoders\session;

/**
 * Session Manager
 *
 * @package arabcoders\session
 * @author  Abdul.Mohsen B. A. A. <admin@arabcoders.org>
 */
class Session implements Interfaces\Session
{
    /**
     * @var Interfaces\Adapter
     */
    private $adapter;

    /**
     * Constructor
     *
     * @param Interfaces\Adapter $adapter
     * @param array              $options
     */
    public function __construct( Interfaces\Adapter $adapter, array $options = [ ] )
    {
        $this->adapter = &$adapter;

        session_set_save_handler( $this, true );
    }

    public function open( $path, $id )
    {
        return $this->adapter->open( $path, $id );
    }

    public function close()
    {
        return $this->adapter->close();
    }

    public function read( $id )
    {
        return $this->adapter->read( $id );
    }

    public function write( $id, $data )
    {
        return $this->adapter->write( $id, $data );
    }

    public function destroy( $id )
    {
        return $this->adapter->destroy( $id );
    }

    public function gc( $maxlifetime )
    {
        return $this->adapter->gc( $maxlifetime );
    }
}