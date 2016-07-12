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
 * Adapter: APC
 *
 * @package arabcoders\session
 * @author  Abdul.Mohsen B. A. A. <admin@arabcoders.org>
 */
class Apc implements AdapterInterface
{
    /**
     * @var string Prefix Session Paramater.
     */
    private $prefix = 'sess_';

    /**
     * Class Constructor
     *
     * @param array $options
     */
    public function __construct( array $options = [ ] )
    {
        if ( array_key_exists( 'prefix', $options ) )
        {
            $this->prefix = $options['prefix'];
        }
    }

    public function open( $path, $id )
    {
        return true;
    }

    public function close()
    {
        return true;
    }

    public function read( $id )
    {
        return (string) apc_fetch( sprintf( '%s%s', $this->prefix, $id ) );
    }

    public function write( $id, $data )
    {
        return apc_store( sprintf( '%s%s', $this->prefix, $id ), $data, ini_get( 'session.gc_maxlifetime' ) );
    }

    public function destroy( $id )
    {

        return apc_delete( sprintf( '%s%s', $this->prefix, $id ) );
    }

    public function gc( $maxLifeTime )
    {
        return true;
    }
}