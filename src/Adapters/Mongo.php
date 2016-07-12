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
 * Adapter Mongo
 *
 * @package arabcoders\session
 * @author  Abdul.Mohsen B. A. A. <admin@arabcoders.org>
 */
class Mongo implements AdapterInterface
{
    /**
     * @var array schema
     */
    private $schema = [
        'id'   => '_id',
        'date' => 'date',
        'data' => 'data',
        'lock' => 'lock',
    ];

    /**
     * @var \MongoCollection
     */
    private $mongo;

    /**
     * @var int global wait time (15000000 equal to 15 secs).
     */
    private $remaining = 30000000;

    /**
     * @var int timeout in ms (5000 equal to 5ms).
     */
    private $timeOut = 5000;

    /**
     * Constructor.
     *
     * @param \MongoCollection $mongo
     * @param array            $options
     */
    public function __construct( \MongoCollection $mongo, array $options = [ ] )
    {
        $this->mongo = $mongo;

        if ( array_key_exists( 'schema', $options ) )
        {
            $this->schema = $options['schema'];
        }
    }

    public function open( $savePath, $sessionName )
    {
        return true;
    }

    public function close()
    {
        return true;
    }

    public function destroy( $sessionId )
    {
        $this->mongo->remove( [ $this->schema['id'] => $sessionId ] );

        return true;
    }

    public function gc( $lifetime )
    {
        /* Note: MongoDB 2.2+ supports TTL collections, which may be used in
         * place of this method by indexing the "date" field with an
         * "expireAfterSeconds" option. Regardless of whether TTL collections
         * are used, consider indexing this field to make the remove query more
         * efficient.
         *
         * See: http://docs.mongodb.org/manual/tutorial/expire-data/
         */
        $time = new \MongoDate( time() - $lifetime );

        $this->mongo->remove( [ $this->schema['date'] => [ '$lt' => $time ] ] );

        return true;
    }

    public function write( $sessionId, $data )
    {
        $this->mongo->update(
            [
                $this->schema['id'] => $sessionId
            ],
            [
                '$set' =>
                    [
                        $this->schema['lock'] => 0,
                        $this->schema['data'] => new \MongoBinData( $data, \MongoBinData::BYTE_ARRAY ),
                        $this->schema['date'] => new \MongoDate(),
                    ]
            ],
            [
                'upsert'   => true,
                'multiple' => false
            ]
        );

        return true;
    }

    public function read( $sessionId )
    {
        $this->lock( $sessionId );

        $dbData = $this->mongo->findOne(
            [
                $this->schema['id'] => $sessionId
            ]
        );

        return empty( $dbData[$this->schema['data']] ) ? '' : $dbData[$this->schema['data']]->bin;
    }

    private function lock( $sessionId )
    {
        $timeOut   = $this->timeOut;
        $remaining = $this->remaining;

        do
        {
            try
            {

                $result = $this->mongo->update(
                    [
                        $this->schema['id']   => $sessionId,
                        $this->schema['lock'] => 0
                    ],
                    [
                        '$set' => [
                            $this->schema['lock'] => 1
                        ]
                    ],
                    [
                        'safe'   => true,
                        'upsert' => true
                    ]
                );

                if ( $result['ok'] == 1 )
                {
                    return true;
                }
            }
            catch ( \MongoCursorException $e )
            {
                if ( $e->getCode() !== 11000 ) //-- not a dup key Exception?
                {
                    throw $e;
                }
            }

            usleep( $timeOut );
            $remaining = $remaining - $timeOut;

            // wait a little longer next time, 1 sec max wait
            $timeOut = ( $timeOut < 1000000 ) ? $timeOut * 2 : 1000000;

        }
        while ( $remaining > 0 );

        throw new \RuntimeException( 'Could not get session lock' );
    }
}