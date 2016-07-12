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
 * Adapter: Filesystem
 *
 * @package arabcoders\session
 * @author  Abdul.Mohsen B. A. A. <admin@arabcoders.org>
 */
class FileSystem implements AdapterInterface
{
    /**
     * @var string Session path.
     */
    private $path;

    /**
     * @var string Session id.
     */
    private $id;

    /**
     * @var string Prefix session id.
     */
    private $prefix = 'sess_';

    /**
     * Constructor
     *
     * @param string $path session Path.
     * @param array  $options
     */
    public function __construct( $path = null, array $options = [ ] )
    {
        if ( !is_dir( $path ) )
        {
            throw new \InvalidArgumentException( '%s is not directory and/or doesn\'t exists.', $path );
        }

        $this->path = $path;

        if ( array_key_exists( 'prefix', $options ) )
        {
            $this->prefix = $options['prefix'];
        }
    }

    public function open( $path, $id )
    {
        if ( !is_dir( $path ) )
        {
            return false;
        }

        $this->id   = $id;
        $this->path = $path;

        return true;
    }

    public function close()
    {
        return true;
    }

    public function read( $id )
    {
        $file = sprintf( '%s/%s%s', $this->path, $this->prefix, $id );

        return ( is_readable( $file ) ) ? (string) file_get_contents( $file ) : '';
    }

    public function write( $id, $data )
    {
        $file = sprintf( '%s/%s%s', $this->path, $this->prefix, $id );

        return ( file_put_contents( $file, $data ) === false ) ? false : true;
    }

    public function destroy( $id )
    {
        $file = sprintf( '%s/%s%s', $this->path, $this->prefix, $id );

        return ( is_readable( $file ) ) ? unlink( $file ) : false;
    }

    public function gc( $maxLifeTime )
    {
        $files = sprintf( '%s/%s*', $this->path, $this->prefix );

        foreach ( glob( $files ) as $file )
        {
            if ( is_readable( $file ) && ( filemtime( $file ) + $maxLifeTime ) < time() )
            {
                unlink( $file );
            }
        }

        return true;
    }
}