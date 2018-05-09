<?php
namespace phpbu\App\Backup\Collector;

use Aws\S3\S3Client;
use phpbu\App\Backup\Collector;
use phpbu\App\Backup\File\AmazonS3v3 as AwsFile;
use phpbu\App\Backup\Target;

/**
 * AmazonS3v3 class.
 *
 * @package    phpbu
 * @subpackage Backup
 * @author     Sebastian Feldmann <sebastian@phpbu.de>
 * @author     Vitaly Baev <hello@vitalybaev.ru>
 * @copyright  Sebastian Feldmann <sebastian@phpbu.de>
 * @license    https://opensource.org/licenses/MIT The MIT License (MIT)
 * @link       http://phpbu.de/
 * @since      Class available since Release 5.1.0
 */
class AmazonS3v3 extends Collector
{
    /**
     * @var \Aws\S3\S3Client
     */
    protected $client;

    /**
     * AmazonS3 bucket name
     *
     * @var string
     */
    protected $bucket;

    /**
     * AmazonS3 remote path
     *
     * @var string
     */
    protected $path;

    /**
     * OpenStack constructor.
     *
     * @param \phpbu\App\Backup\Target $target
     * @param S3Client                 $client
     * @param string                   $bucket
     * @param string                   $path
     */
    public function __construct(Target $target, S3Client $client, string $bucket, string $path)
    {
        $this->client = $client;
        $this->bucket = $bucket;
        $this->path   = ltrim($path, '/');
        $this->setUp($target);
    }

    /**
     * Get all created backups.
     *
     * @return \phpbu\App\Backup\File[]
     */
    public function getBackupFiles() : array
    {
        $result = $this->client->listObjects([
            'Bucket'    => $this->bucket,
            'Prefix'    => $this->path,
            'Delimiter' => '/',
        ]);

        if (!isset($result['Contents']) || !$result['Contents'] || !is_array($result['Contents'])) {
            return [];
        }

        foreach ($result['Contents'] as $object) {
            // skip currently created backup
            if ($object['Key'] == $this->path . $this->target->getFilename()) {
                continue;
            }
            if ($this->isFilenameMatch(basename($object['Key']))) {
                $this->files[] = new AwsFile($this->client, $this->bucket, $object);
            }
        }

        return $this->files;
    }
}